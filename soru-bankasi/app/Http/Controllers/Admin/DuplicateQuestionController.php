<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Subject;
use App\Services\AuditLogService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DuplicateQuestionController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly AuditLogService $auditLog
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Question::class);

        $subjectId = $request->integer('subject_id');
        $search = trim((string) $request->input('search', ''));
        $threshold = max(70, min(99, $request->integer('threshold', 86)));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        $subjects = Subject::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $questions = Question::query()
            ->with('subject:id,name')
            ->where('status', '!=', 'archived')
            ->when($subjectId > 0, fn ($query) => $query->where('subject_id', $subjectId))
            ->when($search !== '', fn ($query) => $query->where('question_text', 'like', '%' . $search . '%'))
            ->orderBy('subject_id')
            ->orderBy('id')
            ->get([
                'id',
                'subject_id',
                'question_text',
                'option_a',
                'option_b',
                'option_c',
                'option_d',
                'option_e',
                'status',
                'current_version',
                'created_at',
            ]);

        $groups = $this->buildDuplicateGroups($questions, $threshold);
        $total = $groups->count();
        $offset = ($page - 1) * $perPage;
        $pageItems = $groups->slice($offset, $perPage)->values();

        $duplicateGroups = new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $page,
            ['path' => route('admin.questions.duplicates.index'), 'query' => $request->query()]
        );

        return view('admin.questions.duplicates', [
            'subjects' => $subjects,
            'duplicateGroups' => $duplicateGroups,
            'filters' => [
                'subject_id' => $subjectId > 0 ? $subjectId : null,
                'search' => $search,
                'threshold' => $threshold,
            ],
        ]);
    }

    public function archiveGroup(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'keep_question_id' => ['required', 'integer', 'exists:questions,id'],
            'duplicate_ids' => ['required', 'array', 'min:1'],
            'duplicate_ids.*' => ['integer', 'exists:questions,id'],
        ]);

        $keepQuestionId = (int) $validated['keep_question_id'];
        $duplicateIds = collect($validated['duplicate_ids'])
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === $keepQuestionId)
            ->values();

        if ($duplicateIds->isEmpty()) {
            return back()->withErrors(['duplicates' => 'Arsive tasinacak soru secimi bulunamadi.']);
        }

        $questions = Question::query()
            ->whereIn('id', $duplicateIds->all())
            ->where('status', '!=', 'archived')
            ->get();

        $questions->each(fn (Question $question) => $this->authorize('delete', $question));

        DB::transaction(function () use ($request, $questions, $keepQuestionId): void {
            $questions->each(fn (Question $question) => $this->archiveQuestion($question, $request));

            $this->auditLog->record(
                $request->user(),
                'question.duplicate_cleanup',
                'questions',
                $keepQuestionId,
                null,
                [
                    'kept_question_id' => $keepQuestionId,
                    'archived_question_ids' => $questions->pluck('id')->all(),
                    'archived_count' => $questions->count(),
                ],
                'Kopya soru temizligi yapildi.',
                $request
            );
        });

        return back()->with('success', $questions->count() . ' kopya soru arsive tasindi.');
    }

    private function buildDuplicateGroups(Collection $questions, int $threshold): Collection
    {
        $groups = collect();

        foreach ($questions->groupBy('subject_id') as $subjectQuestions) {
            $subjectQuestions = $subjectQuestions->values();
            $count = $subjectQuestions->count();
            if ($count < 2) {
                continue;
            }

            $tokenMap = [];
            $textMap = [];
            foreach ($subjectQuestions as $question) {
                $text = $this->comparableText($question);
                $textMap[$question->id] = $this->normalizedText($text);
                $tokenMap[$question->id] = $this->tokens($textMap[$question->id]);
            }

            $parent = [];
            foreach ($subjectQuestions as $question) {
                $parent[$question->id] = $question->id;
            }

            $inverted = [];
            foreach ($subjectQuestions as $question) {
                foreach (array_slice($tokenMap[$question->id], 0, 18) as $token) {
                    $inverted[$token][] = $question->id;
                }
            }

            $compared = [];
            foreach ($subjectQuestions as $question) {
                $id = $question->id;
                $candidateHits = [];
                foreach (array_slice($tokenMap[$id], 0, 18) as $token) {
                    foreach ($inverted[$token] ?? [] as $candidateId) {
                        if ($candidateId !== $id) {
                            $candidateHits[$candidateId] = ($candidateHits[$candidateId] ?? 0) + 1;
                        }
                    }
                }

                foreach ($candidateHits as $candidateId => $sharedTokenCount) {
                    // Avoid scoring very weak candidates.
                    if ($sharedTokenCount < 2) {
                        continue;
                    }

                    $a = min($id, $candidateId);
                    $b = max($id, $candidateId);
                    $pairKey = $a . ':' . $b;
                    if (isset($compared[$pairKey])) {
                        continue;
                    }
                    $compared[$pairKey] = true;

                    $score = $this->similarityScore(
                        $textMap[$id],
                        $tokenMap[$id],
                        $textMap[$candidateId],
                        $tokenMap[$candidateId]
                    );

                    if ($score >= $threshold) {
                        $this->union($parent, $id, $candidateId);
                    }
                }
            }

            $byRoot = [];
            foreach ($subjectQuestions as $question) {
                $root = $this->find($parent, $question->id);
                $byRoot[$root][] = $question;
            }

            foreach ($byRoot as $cluster) {
                if (count($cluster) < 2) {
                    continue;
                }

                $ordered = collect($cluster)->sortBy('id')->values();
                $subjectName = $ordered->first()?->subject?->name ?? '-';
                $canonical = $ordered->first();
                $canonicalText = $textMap[$canonical->id] ?? '';
                $canonicalTokens = $tokenMap[$canonical->id] ?? [];

                $withScore = $ordered->map(function (Question $question) use ($canonical, $canonicalText, $canonicalTokens, $textMap, $tokenMap) {
                    $question->duplicate_similarity = $question->id === $canonical->id
                        ? 100
                        : $this->similarityScore(
                            $canonicalText,
                            $canonicalTokens,
                            $textMap[$question->id] ?? '',
                            $tokenMap[$question->id] ?? []
                        );
                    return $question;
                })->sortByDesc('duplicate_similarity')->values();

                $scores = $withScore->pluck('duplicate_similarity')->filter(fn ($score) => is_numeric($score));

                $groups->push([
                    'subject_name' => $subjectName,
                    'canonical_text' => (string) ($canonical->question_text ?? ''),
                    'count' => $withScore->count(),
                    'questions' => $withScore,
                    'max_similarity' => (int) ($scores->max() ?? 100),
                    'min_similarity' => (int) ($scores->min() ?? 100),
                ]);
            }
        }

        return $groups
            ->sortByDesc('max_similarity')
            ->sortByDesc('count')
            ->values();
    }

    private function normalizedText(string $value): string
    {
        $text = mb_strtolower(trim($value), 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text) ?? $text;
        return trim($text);
    }

    private function comparableText(Question $question): string
    {
        return implode(' ', array_filter([
            mb_substr((string) $question->question_text, 0, 400, 'UTF-8'),
            mb_substr((string) $question->option_a, 0, 140, 'UTF-8'),
            mb_substr((string) $question->option_b, 0, 140, 'UTF-8'),
            mb_substr((string) $question->option_c, 0, 140, 'UTF-8'),
            mb_substr((string) $question->option_d, 0, 140, 'UTF-8'),
            mb_substr((string) $question->option_e, 0, 140, 'UTF-8'),
        ], fn ($value) => trim((string) $value) !== ''));
    }

    private function tokens(string $normalized): array
    {
        static $stopWords = [
            've', 'veya', 'ile', 'icin', 'bu', 'bir', 'mi', 'midir', 'nedir',
            'olan', 'olarak', 'da', 'de', 'ki', 'ya', 'ile', 'en', 'daha',
        ];

        $parts = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $filtered = array_values(array_filter($parts, function (string $token) use ($stopWords): bool {
            return mb_strlen($token, 'UTF-8') > 1 && ! in_array($token, $stopWords, true);
        }));
        $unique = array_values(array_unique($filtered));
        sort($unique);
        return $unique;
    }

    private function similarityScore(string $aText, array $aTokens, string $bText, array $bTokens): int
    {
        if ($aText === '' || $bText === '') {
            return 0;
        }

        $aSet = array_values(array_unique($aTokens));
        $bSet = array_values(array_unique($bTokens));

        if (count($aSet) === 0 || count($bSet) === 0) {
            return 0;
        }

        $intersection = count(array_intersect($aSet, $bSet));
        $denominator = max(count($aSet), count($bSet));
        $tokenSetPercent = $denominator > 0 ? ($intersection / $denominator) * 100 : 0;

        $trigramPercent = $this->trigramSimilarityPercent($aText, $bText);

        // Keep order-independent signal dominant but add character-level signal.
        $final = ($tokenSetPercent * 0.7) + ($trigramPercent * 0.3);
        return (int) round($final);
    }

    private function trigramSimilarityPercent(string $aText, string $bText): float
    {
        $a = $this->trigramSet($aText);
        $b = $this->trigramSet($bText);

        if (count($a) === 0 || count($b) === 0) {
            return 0.0;
        }

        $intersection = count(array_intersect_key($a, $b));
        $denominator = max(count($a), count($b));

        return $denominator > 0 ? ($intersection / $denominator) * 100 : 0.0;
    }

    private function trigramSet(string $text): array
    {
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);
        $len = mb_strlen($text, 'UTF-8');
        if ($len < 3) {
            return $len > 0 ? [$text => true] : [];
        }

        $set = [];
        for ($i = 0; $i <= $len - 3; $i++) {
            $tri = mb_substr($text, $i, 3, 'UTF-8');
            $set[$tri] = true;
        }

        return $set;
    }

    private function find(array &$parent, int $x): int
    {
        if ($parent[$x] !== $x) {
            $parent[$x] = $this->find($parent, $parent[$x]);
        }
        return $parent[$x];
    }

    private function union(array &$parent, int $a, int $b): void
    {
        $ra = $this->find($parent, $a);
        $rb = $this->find($parent, $b);
        if ($ra !== $rb) {
            $parent[$rb] = $ra;
        }
    }

    private function archiveQuestion(Question $question, Request $request): void
    {
        $currentVersion = (int) $question->current_version;
        $archiveAt = now();
        $oldValue = [
            'status' => $question->status,
            'subject_id' => $question->subject_id,
            'question_text' => $question->question_text,
            'current_version' => $question->current_version,
        ];

        QuestionVersion::query()->create([
            'question_id' => $question->id,
            'version_no' => $currentVersion,
            'changed_by' => $request->user()->id,
            'change_reason' => 'Kopya soru temizligi oncesi otomatik surum kaydi',
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
                'current_version' => $question->current_version,
            ],
        ]);

        $question->update([
            'status' => 'archived',
            'approved_by' => null,
            'approved_at' => null,
            'archived_at' => $archiveAt,
            'purge_after' => $this->purgeAfter($archiveAt),
            'current_version' => $currentVersion + 1,
        ]);

        $this->auditLog->record(
            $request->user(),
            'question.archived_duplicate',
            'questions',
            $question->id,
            $oldValue,
            ['status' => 'archived', 'current_version' => $question->current_version],
            'Kopya soru arsive tasindi.',
            $request
        );
    }

    private function purgeAfter($archiveAt)
    {
        if (! $this->settingsService->getBool('archive_auto_prune_enabled', true)) {
            return null;
        }

        return $archiveAt->copy()->addDays($this->settingsService->getInt('archive_retention_days', 7));
    }
}
