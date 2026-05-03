<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Question::class);

        $subjects = Subject::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $questions = Question::query()
            ->with(['subject:id,name', 'createdBy:id,name'])
            ->when($request->filled('subject_id'), fn ($query) => $query->where('subject_id', $request->integer('subject_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
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

        Question::query()->create([
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
            'current_version' => 1,
        ]);

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
                'current_version' => $currentVersion + 1,
            ]);
        });

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Soru basariyla guncellendi.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->authorize('delete', $question);

        DB::transaction(function () use ($question): void {
            $currentVersion = (int) $question->current_version;

            QuestionVersion::query()->create([
                'question_id' => $question->id,
                'version_no' => $currentVersion,
                'changed_by' => request()->user()?->id,
                'change_reason' => 'Pasife alma oncesi otomatik surum kaydi',
                'payload_json' => $this->versionPayload($question),
            ]);

            $question->update([
                'status' => 'inactive',
                'approved_by' => null,
                'approved_at' => null,
                'current_version' => $currentVersion + 1,
            ]);
        });

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Soru pasif duruma alindi.');
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