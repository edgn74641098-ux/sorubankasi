<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportError;
use App\Models\QuestionImportRow;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionImportService
{
    private const MAX_ROWS = 1000;

    private const PREVIEW_CACHE_PREFIX = 'import.preview.';

    public function preview(UploadedFile $file, User $actor): QuestionImportBatch
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, ['csv'], true)) {
            throw ValidationException::withMessages([
                'file' => 'Bu surumde sadece CSV import destekleniyor.',
            ]);
        }

        $batch = QuestionImportBatch::query()->create([
            'uploaded_by' => $actor->id,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => strtoupper($extension),
            'status' => 'preview',
            'total_rows' => 0,
            'success_rows' => 0,
            'failed_rows' => 0,
        ]);

        $rows = $this->parseCsv($file);
        $validRows = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($rows as $idx => $row) {
            $rowNumber = $idx + 2; // header + 1-based

            try {
                $normalized = $this->normalizeRow($row);
                $subjectId = $this->resolveSubjectId($normalized['subject']);
                $hash = $this->questionHash($subjectId, $normalized['question_text']);
                $matchedQuestion = Question::query()
                    ->where('subject_id', $subjectId)
                    ->whereRaw('LOWER(TRIM(question_text)) = ?', [mb_strtolower(trim($normalized['question_text']))])
                    ->first();

                $rowPayload = [
                    'subject_id' => $subjectId,
                    'question_text' => $normalized['question_text'],
                    'option_a' => $normalized['option_a'],
                    'option_b' => $normalized['option_b'],
                    'option_c' => $normalized['option_c'],
                    'option_d' => $normalized['option_d'],
                    'option_e' => $normalized['option_e'],
                    'correct_option' => $normalized['correct_option'],
                    'explanation_text' => $normalized['explanation_text'],
                ];

                QuestionImportRow::query()->create([
                    'batch_id' => $batch->id,
                    'question_hash' => $hash,
                    'action' => 'pending',
                    'matched_question_id' => $matchedQuestion?->id,
                    'payload_json' => $rowPayload,
                ]);

                $validRows[] = array_merge([
                    'row_number' => $rowNumber,
                    'question_hash' => $hash,
                    'matched_question_id' => $matchedQuestion?->id,
                ], $rowPayload);

                $successCount++;
            } catch (\Throwable $e) {
                QuestionImportError::query()->create([
                    'batch_id' => $batch->id,
                    'row_number' => $rowNumber,
                    'error_message' => $e->getMessage(),
                    'raw_payload' => $row,
                ]);
                $failedCount++;
            }
        }

        Cache::put($this->previewCacheKey($batch->id), $validRows, now()->addHours(2));

        $batch->update([
            'total_rows' => count($rows),
            'success_rows' => $successCount,
            'failed_rows' => $failedCount,
            'status' => 'preview',
        ]);

        return $batch->fresh(['rows', 'errors']);
    }

    public function confirm(QuestionImportBatch $batch, array $actions, User $actor): array
    {
        $previewRows = Cache::get($this->previewCacheKey($batch->id), []);
        if (empty($previewRows)) {
            $previewRows = $this->loadPreviewRows($batch);
        }

        if (empty($previewRows)) {
            throw ValidationException::withMessages([
                'batch' => 'Onizleme verisi bulunamadi. Lutfen dosyayi tekrar yukleyin.',
            ]);
        }

        $rowsByHash = collect($previewRows)->keyBy('question_hash');
        $inserted = 0;
        $merged = 0;
        $manualReview = 0;
        $skipped = 0;

        DB::transaction(function () use ($batch, $actions, $actor, $rowsByHash, &$inserted, &$merged, &$manualReview, &$skipped): void {
            foreach ($batch->rows as $importRow) {
                $decision = $actions[$importRow->id] ?? ($importRow->matched_question_id ? 'skip' : 'insert');
                $preview = $rowsByHash->get($importRow->question_hash);
                if (! $preview) {
                    continue;
                }

                if ($decision === 'manual_review') {
                    $importRow->update(['action' => 'manual_review']);
                    $manualReview++;
                    continue;
                }

                if ($decision === 'skip') {
                    $importRow->update(['action' => 'skipped']);
                    $skipped++;
                    continue;
                }

                if ($decision === 'merge' && $importRow->matched_question_id) {
                    $question = Question::query()->find($importRow->matched_question_id);
                    if ($question) {
                        $question->update([
                            'question_text' => $preview['question_text'],
                            'option_a' => $preview['option_a'],
                            'option_b' => $preview['option_b'],
                            'option_c' => $preview['option_c'],
                            'option_d' => $preview['option_d'],
                            'option_e' => $preview['option_e'],
                            'correct_option' => $preview['correct_option'],
                            'explanation_text' => $preview['explanation_text'],
                            'current_version' => $question->current_version + 1,
                        ]);
                        $importRow->update(['action' => 'merged']);
                        $merged++;
                        continue;
                    }
                }

                Question::query()->create([
                    'subject_id' => $preview['subject_id'],
                    'created_by' => $actor->id,
                    'approved_by' => $actor->id,
                    'source_type' => 'import',
                    'question_text' => $preview['question_text'],
                    'option_a' => $preview['option_a'],
                    'option_b' => $preview['option_b'],
                    'option_c' => $preview['option_c'],
                    'option_d' => $preview['option_d'],
                    'option_e' => $preview['option_e'],
                    'correct_option' => $preview['correct_option'],
                    'explanation_text' => $preview['explanation_text'],
                    'difficulty_score' => 5.0,
                    'status' => 'active',
                    'approved_at' => now(),
                    'current_version' => 1,
                ]);

                $importRow->update(['action' => 'inserted']);
                $inserted++;
            }

            $batch->update([
                'status' => 'completed',
                'completed_at' => now(),
                'success_rows' => $inserted + $merged,
                'failed_rows' => $batch->errors()->count() + $manualReview + $skipped,
            ]);
        });

        Cache::forget($this->previewCacheKey($batch->id));

        return [
            'inserted' => $inserted,
            'merged' => $merged,
            'manual_review' => $manualReview,
            'skipped' => $skipped,
        ];
    }

    private function loadPreviewRows(QuestionImportBatch $batch): array
    {
        return $batch->rows()
            ->get()
            ->map(function (QuestionImportRow $row) {
                return array_merge(
                    ['question_hash' => $row->question_hash],
                    $row->payload_json ?? []
                );
            })->all();
    }

    private function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if (! $handle) {
            throw ValidationException::withMessages(['file' => 'CSV dosyasi okunamadi.']);
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            return [];
        }

        $normalizedHeader = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $row = [];
            foreach ($normalizedHeader as $idx => $key) {
                $row[$key] = trim((string) ($data[$idx] ?? ''));
            }
            $rows[] = $row;

            if (count($rows) > self::MAX_ROWS) {
                fclose($handle);

                throw ValidationException::withMessages([
                    'file' => 'CSV import en fazla 1000 satir icerebilir.',
                ]);
            }
        }
        fclose($handle);

        return $rows;
    }

    private function normalizeRow(array $row): array
    {
        $required = ['subject', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'correct_option'];
        foreach ($required as $field) {
            if (empty($row[$field])) {
                throw new \RuntimeException("Eksik alan: {$field}");
            }
        }

        $correct = strtoupper($row['correct_option']);
        if (! in_array($correct, ['A', 'B', 'C', 'D', 'E'], true)) {
            throw new \RuntimeException('correct_option A-E olmali.');
        }

        return [
            'subject' => $this->cleanText($row['subject']),
            'question_text' => $this->cleanText($row['question_text']),
            'option_a' => $this->cleanText($row['option_a']),
            'option_b' => $this->cleanText($row['option_b']),
            'option_c' => $this->cleanText($row['option_c']),
            'option_d' => $this->cleanText($row['option_d']),
            'option_e' => $this->cleanText($row['option_e']),
            'correct_option' => $correct,
            'explanation_text' => $this->cleanText($row['explanation_text'] ?? ''),
        ];
    }

    private function cleanText(string $value): string
    {
        return trim(strip_tags($value));
    }

    private function resolveSubjectId(string $subjectName): int
    {
        $subject = Subject::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($subjectName))])
            ->first();
        if (! $subject) {
            throw new \RuntimeException("Ders bulunamadi: {$subjectName}");
        }

        return $subject->id;
    }

    private function questionHash(int $subjectId, string $questionText): string
    {
        return md5(trim($subjectId.'|'.mb_strtolower(trim($questionText))));
    }

    private function previewCacheKey(int $batchId): string
    {
        return self::PREVIEW_CACHE_PREFIX.$batchId;
    }
}
