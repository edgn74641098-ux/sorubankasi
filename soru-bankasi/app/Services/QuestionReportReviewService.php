<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\QuestionVersion;
use App\Models\User;
use App\Notifications\QuestionReportAcceptedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionReportReviewService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly SettingsService $settingsService
    ) {
    }

    public function approve(QuestionReport $report, User $reviewer, ?string $reviewNote, ?Request $request = null): QuestionReport
    {
        $oldCorrectOption = null;
        $oldSubjectId = null;

        $report = DB::transaction(function () use ($report, $reviewer, $reviewNote, $request, &$oldCorrectOption, &$oldSubjectId): QuestionReport {
            $report->loadMissing(['question', 'user', 'suggestedSubject']);
            $question = $report->question;
            $oldCorrectOption = $question->correct_option;
            $oldSubjectId = $question->subject_id;
            $shouldUpdateCorrectOption = $report->suggested_correct_option && $report->suggested_correct_option !== $question->correct_option;
            $shouldUpdateSubject = $report->suggested_subject_id && (int) $report->suggested_subject_id !== (int) $question->subject_id;
            $typoPayload = $report->suggested_payload_json ?? [];
            $shouldUpdateTypoPayload = $report->category === 'TYPO' && !empty($typoPayload);
            $shouldUpdateTypoFields = $shouldUpdateTypoPayload && (
                ($typoPayload['question_text'] ?? null) !== $question->question_text
                || ($typoPayload['option_a'] ?? null) !== $question->option_a
                || ($typoPayload['option_b'] ?? null) !== $question->option_b
                || ($typoPayload['option_c'] ?? null) !== $question->option_c
                || ($typoPayload['option_d'] ?? null) !== $question->option_d
                || ($typoPayload['option_e'] ?? null) !== $question->option_e
                || ($typoPayload['explanation_text'] ?? null) !== $question->explanation_text
            );

            if ($shouldUpdateCorrectOption || $shouldUpdateSubject || $shouldUpdateTypoFields) {
                $this->snapshotQuestion($question, $reviewer, $report);

                $payload = [
                    'current_version' => (int) $question->current_version + 1,
                ];
                if ($shouldUpdateCorrectOption) {
                    $payload['correct_option'] = $report->suggested_correct_option;
                }
                if ($shouldUpdateSubject) {
                    $payload['subject_id'] = $report->suggested_subject_id;
                }
                if ($shouldUpdateTypoFields) {
                    $payload['question_text'] = $typoPayload['question_text'];
                    $payload['option_a'] = $typoPayload['option_a'];
                    $payload['option_b'] = $typoPayload['option_b'];
                    $payload['option_c'] = $typoPayload['option_c'];
                    $payload['option_d'] = $typoPayload['option_d'];
                    $payload['option_e'] = $typoPayload['option_e'];
                    $payload['explanation_text'] = $typoPayload['explanation_text'];
                }
                $question->update($payload);
            }

            $userMessage = $this->acceptedMessage($report, $oldCorrectOption, $oldSubjectId);

            $report->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $reviewNote,
                'user_message' => $userMessage,
            ]);

            $this->auditLog->record(
                $reviewer,
                'question_report.approved',
                'question_reports',
                $report->id,
                ['status' => 'pending', 'correct_option' => $oldCorrectOption],
                [
                    'status' => 'approved',
                    'review_note' => $reviewNote,
                    'suggested_correct_option' => $report->suggested_correct_option,
                    'suggested_subject_id' => $report->suggested_subject_id,
                    'correct_option' => $report->suggested_correct_option ?: $oldCorrectOption,
                    'subject_id' => $report->question->fresh()->subject_id,
                ],
                'Soru itirazi onaylandi.',
                $request
            );

            return $report->fresh(['question', 'user', 'reviewedBy']);
        });

        $this->notifyAccepted($report, $oldCorrectOption);

        return $report;
    }

    public function reject(QuestionReport $report, User $reviewer, string $reviewNote, ?Request $request = null): QuestionReport
    {
        $report->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $reviewNote,
            'user_message' => "Itiraziniz incelendi ancak kabul edilmedi. Not: {$reviewNote}",
        ]);

        $this->auditLog->record(
            $reviewer,
            'question_report.rejected',
            'question_reports',
            $report->id,
            ['status' => 'pending'],
            ['status' => 'rejected', 'review_note' => $reviewNote],
            "Soru itirazi reddedildi. Sebep: {$reviewNote}",
            $request
        );

        return $report->fresh(['question', 'user', 'reviewedBy']);
    }

    private function snapshotQuestion(Question $question, User $reviewer, QuestionReport $report): void
    {
        QuestionVersion::query()->create([
            'question_id' => $question->id,
            'version_no' => (int) $question->current_version,
            'changed_by' => $reviewer->id,
            'change_reason' => "Itiraz #{$report->id} kabul edildi; dogru cevap guncellendi.",
            'payload_json' => [
                'subject_id' => $question->subject_id,
                'question_text' => $question->question_text,
                'option_a' => $question->option_a,
                'option_b' => $question->option_b,
                'option_c' => $question->option_c,
                'option_d' => $question->option_d,
                'option_e' => $question->option_e,
                'correct_option' => $question->correct_option,
                'explanation_text' => $question->explanation_text,
                'difficulty_score' => $question->difficulty_score,
                'status' => $question->status,
                'approved_by' => $question->approved_by,
                'approved_at' => optional($question->approved_at)->toISOString(),
                'current_version' => $question->current_version,
            ],
        ]);
    }

    private function acceptedMessage(QuestionReport $report, ?string $oldCorrectOption, ?int $oldSubjectId): string
    {
        $newCorrectOption = $report->suggested_correct_option ?: $oldCorrectOption;
        $template = $this->settingsService->getString(
            'question_report_accept_message',
            'Itiraziniz kabul edildi. Katkiniz icin tesekkur ederiz. Sorunun dogru cevabi {old_answer} yerine {new_answer} olarak guncellendi.'
        );

        $message = strtr($template, [
            '{old_answer}' => (string) ($oldCorrectOption ?? '-'),
            '{new_answer}' => (string) ($newCorrectOption ?? '-'),
            '{question_id}' => (string) $report->question_id,
        ]);

        if ($report->suggested_subject_id && $oldSubjectId && $report->suggested_subject_id !== $oldSubjectId) {
            $newSubjectName = $report->suggestedSubject?->name ?? ('Ders #' . $report->suggested_subject_id);
            $message .= " Sorunun dersi {$newSubjectName} olarak guncellendi.";
        }

        return $message;
    }

    private function notifyAccepted(QuestionReport $report, ?string $oldCorrectOption): void
    {
        try {
            $report->user->notify(new QuestionReportAcceptedNotification($report, $oldCorrectOption));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
