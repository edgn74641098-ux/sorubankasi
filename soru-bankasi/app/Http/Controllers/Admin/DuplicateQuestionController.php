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
            ->get(['id', 'subject_id', 'question_text', 'status', 'current_version', 'created_at']);

        $groups = $this->buildDuplicateGroups($questions);
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

    private function buildDuplicateGroups(Collection $questions): Collection
    {
        return $questions
            ->groupBy(function (Question $question): string {
                return $question->subject_id . '::' . $this->normalizedText($question->question_text);
            })
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->map(function (Collection $group): array {
                $ordered = $group->sortBy('id')->values();
                $subjectName = $ordered->first()?->subject?->name ?? '-';

                return [
                    'subject_name' => $subjectName,
                    'canonical_text' => (string) ($ordered->first()?->question_text ?? ''),
                    'count' => $ordered->count(),
                    'questions' => $ordered,
                ];
            })
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

