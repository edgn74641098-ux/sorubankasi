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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly AuditLogService $auditLog
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Question::class);

        $subjects = Subject::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $questions = Question::query()
            ->with(['subject:id,name', 'createdBy:id,name'])
            ->where('status', '!=', 'archived')
            ->when($request->filled('subject_id'), fn ($query) => $query->where('subject_id', $request->integer('subject_id')))
            ->when(
                in_array($request->string('status')->value(), array_keys($this->statusOptions()), true),
                fn ($query) => $query->where('status', $request->string('status')->value())
            )
            ->when($request->filled('search'), fn ($query) => $query->where('question_text', 'like', '%' . $request->string('search')->value() . '%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.questions.index', [
            'questions' => $questions,
            'subjects' => $subjects,
            'filters' => [
                'subject_id' => $request->input('subject_id'),
                'status' => $request->input('status'),
                'search' => $request->input('search'),
            ],
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Question::class);

        return view('admin.questions.create', [
            'subjects' => $this->availableSubjects(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Question::class);

        $validated = $this->validateQuestion($request);
        $statusMeta = $this->statusMeta($validated['status'], $request);

        $question = Question::query()->create([
            'subject_id' => $validated['subject_id'],
            'created_by' => $request->user()->id,
            'approved_by' => $statusMeta['approved_by'],
            'source_type' => $request->user()->isAdmin() ? 'admin' : 'editor',
            'question_text' => $validated['question_text'],
            'option_a' => $validated['option_a'],
            'option_b' => $validated['option_b'],
            'option_c' => $validated['option_c'],
            'option_d' => $validated['option_d'],
            'option_e' => $validated['option_e'],
            'correct_option' => $validated['correct_option'],
            'explanation_text' => $validated['explanation'],
            'difficulty_score' => $validated['difficulty_score'],
                'status' => $validated['status'],
                'approved_at' => $statusMeta['approved_at'],
                'archived_at' => null,
                'purge_after' => null,
                'current_version' => 1,
            ]);

        $this->auditLog->record(
            $request->user(),
            'question.created',
            'questions',
            $question->id,
            null,
            $this->versionPayload($question),
            'Soru olusturuldu.',
            $request
        );

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Soru basariyla olusturuldu.');
    }

    public function edit(Question $question): View
    {
        $this->authorize('view', $question);
        $this->authorize('update', $question);

        return view('admin.questions.edit', [
            'question' => $question,
            'subjects' => $this->availableSubjects(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $this->authorize('update', $question);

        $validated = $this->validateQuestion($request);
        $statusMeta = $this->statusMeta($validated['status'], $request);

        DB::transaction(function () use ($request, $question, $validated, $statusMeta): void {
            $currentVersion = (int) $question->current_version;
            $oldValue = $this->versionPayload($question);

            QuestionVersion::query()->create([
                'question_id' => $question->id,
                'version_no' => $currentVersion,
                'changed_by' => $request->user()->id,
                'change_reason' => 'Guncelleme oncesi otomatik surum kaydi',
                'payload_json' => $this->versionPayload($question),
            ]);

            $question->update([
                'subject_id' => $validated['subject_id'],
                'approved_by' => $statusMeta['approved_by'],
                'question_text' => $validated['question_text'],
                'option_a' => $validated['option_a'],
                'option_b' => $validated['option_b'],
                'option_c' => $validated['option_c'],
                'option_d' => $validated['option_d'],
                'option_e' => $validated['option_e'],
                'correct_option' => $validated['correct_option'],
                'explanation_text' => $validated['explanation'],
                'difficulty_score' => $validated['difficulty_score'],
                'status' => $validated['status'],
                'approved_at' => $statusMeta['approved_at'],
                'archived_at' => $validated['status'] === 'archived' ? ($question->archived_at ?: now()) : null,
                'purge_after' => $validated['status'] === 'archived' ? ($question->purge_after ?: $this->purgeAfter(now())) : null,
                'current_version' => $currentVersion + 1,
            ]);

            $this->auditLog->record(
                $request->user(),
                'question.updated',
                'questions',
                $question->id,
                $oldValue,
                $this->versionPayload($question->fresh()),
                'Soru guncellendi.',
                $request
            );
        });

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Soru basariyla guncellendi.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->authorize('delete', $question);

        DB::transaction(fn () => $this->archiveQuestion($question, request()));

        return redirect()
            ->route('admin.questions.index')
            ->with('success', $this->archiveMessage('Soru arsive tasindi.'));
    }

    public function archiveBulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'exists:questions,id'],
        ]);

        $questions = Question::query()
            ->whereIn('id', $validated['question_ids'])
            ->where('status', '!=', 'archived')
            ->get();

        $questions->each(fn (Question $question) => $this->authorize('delete', $question));

        DB::transaction(fn () => $this->archiveQuestions($questions, $request));

        $this->auditLog->record(
            $request->user(),
            'question.archived_bulk',
            'questions',
            null,
            null,
            ['question_ids' => $questions->pluck('id')->all(), 'count' => $questions->count()],
            $questions->count() . ' soru toplu arsive tasindi.',
            $request
        );

        return redirect()
            ->route('admin.questions.index')
            ->with('success', $questions->count() . ' soru arsive tasindi.');
    }

    public function activateBulk(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'question_ids' => ['nullable', 'array'],
            'question_ids.*' => ['integer', 'exists:questions,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'status' => ['nullable', Rule::in(array_keys($this->statusOptions()))],
            'search' => ['nullable', 'string', 'max:255'],
            'scope' => ['required', Rule::in(['selected', 'filter'])],
        ]);

        if ($validated['scope'] === 'selected' && empty($validated['question_ids'] ?? [])) {
            return back()->withErrors([
                'questions' => 'Aktif hale getirmek icin en az bir pasif soru secin.',
            ]);
        }

        if ($validated['scope'] === 'filter' && ($validated['status'] ?? null) !== 'inactive') {
            return back()->withErrors([
                'questions' => 'Filtredeki tum sorulari aktif yapmak icin durum filtresi Pasif olmalidir.',
            ]);
        }

        $questions = Question::query()
            ->where('status', 'inactive')
            ->when(
                $validated['scope'] === 'selected',
                fn ($query) => $query->whereIn('id', $validated['question_ids']),
                fn ($query) => $query
                    ->when($validated['subject_id'] ?? null, fn ($query, $subjectId) => $query->where('subject_id', $subjectId))
                    ->when(filled($validated['search'] ?? null), fn ($query) => $query->where('question_text', 'like', '%' . $validated['search'] . '%'))
            )
            ->get();

        $questions->each(fn (Question $question) => $this->authorize('update', $question));

        DB::transaction(function () use ($request, $questions): void {
            $questions->each(function (Question $question) use ($request): void {
                $oldValue = $this->versionPayload($question);
                $question->update([
                    'status' => 'active',
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                    'archived_at' => null,
                    'purge_after' => null,
                ]);

                $this->auditLog->record(
                    $request->user(),
                    'question.activated',
                    'questions',
                    $question->id,
                    $oldValue,
                    $this->versionPayload($question->fresh()),
                    'Pasif soru aktif hale getirildi.',
                    $request
                );
            });

            if ($questions->isNotEmpty()) {
                $this->auditLog->record(
                    $request->user(),
                    'question.activated_bulk',
                    'questions',
                    null,
                    null,
                    ['question_ids' => $questions->pluck('id')->all(), 'count' => $questions->count()],
                    $questions->count() . ' pasif soru toplu aktif hale getirildi.',
                    $request
                );
            }
        });

        return redirect()
            ->route('admin.questions.index', $request->only(['subject_id', 'status', 'search']))
            ->with('success', $questions->count() . ' pasif soru aktif hale getirildi.');
    }

    private function archiveQuestions(Collection $questions, Request $request): void
    {
        $questions->each(fn (Question $question) => $this->archiveQuestion($question, $request));
    }

    private function archiveQuestion(Question $question, Request $request): void
    {
        $currentVersion = (int) $question->current_version;
        $archiveAt = now();
        $oldValue = $this->versionPayload($question);

        QuestionVersion::query()->create([
            'question_id' => $question->id,
            'version_no' => $currentVersion,
            'changed_by' => request()->user()?->id,
            'change_reason' => 'Arsive alma oncesi otomatik surum kaydi',
            'payload_json' => $this->versionPayload($question),
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
            'question.archived',
            'questions',
            $question->id,
            $oldValue,
            $this->versionPayload($question->fresh()),
            'Soru arsive tasindi.',
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

    private function archiveMessage(string $prefix): string
    {
        if (! $this->settingsService->getBool('archive_auto_prune_enabled', true)) {
            return $prefix . ' Otomatik silme kapali.';
        }

        return $prefix . ' ' . $this->settingsService->getInt('archive_retention_days', 7) . ' gun sonra otomatik silme icin isaretlendi.';
    }

    private function availableSubjects()
    {
        return Subject::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function validateQuestion(Request $request): array
    {
        return $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'question_text' => ['required', 'string'],
            'option_a' => ['required', 'string'],
            'option_b' => ['required', 'string'],
            'option_c' => ['required', 'string'],
            'option_d' => ['required', 'string'],
            'option_e' => ['required', 'string'],
            'correct_option' => ['required', Rule::in(['A', 'B', 'C', 'D', 'E'])],
            'explanation' => ['required', 'string'],
            'difficulty_score' => ['required', 'integer', 'between:1,10'],
            'status' => ['required', Rule::in(array_keys($this->statusOptions()))],
        ]);
    }

    private function statusOptions(): array
    {
        return [
            'draft' => 'Taslak',
            'active' => 'Aktif',
            'inactive' => 'Pasif',
        ];
    }

    private function statusMeta(string $status, Request $request): array
    {
        if ($status !== 'active') {
            return [
                'approved_by' => null,
                'approved_at' => null,
            ];
        }

        return [
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ];
    }

    private function versionPayload(Question $question): array
    {
        return [
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
        ];
    }
}
