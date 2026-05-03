<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Test;
use App\Services\AnswerEvaluationService;
use App\Services\TestFinalizeService;
use App\Services\TestGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TestController extends Controller
{
    public function __construct(
        private readonly TestGenerationService $generationService,
        private readonly AnswerEvaluationService $answerEvaluationService,
        private readonly TestFinalizeService $finalizeService
    ) {
    }

    public function create(): View
    {
        $this->authorize('create', Test::class);
        $this->finalizeService->finalizeExpiredForUser(Auth::id());

        return view('tests.start', [
            'subjects' => Subject::query()
                ->where('is_active', true)
                ->withCount([
                    'questions as active_questions_count' => fn ($query) => $query->where('status', 'active'),
                ])
                ->orderBy('name')
                ->get(),
            'activeTest' => Test::query()
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->latest('started_at')
                ->first(),
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $this->authorize('create', Test::class);

        $validated = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'mode' => ['required', Rule::in(['RANDOM', 'DIFFICULTY_RANGE', 'WEAKNESSES'])],
            'min_difficulty' => ['nullable', 'integer', 'between:1,10'],
            'max_difficulty' => ['nullable', 'integer', 'between:1,10'],
        ]);

        $subject = Subject::query()->findOrFail($validated['subject_id']);
        $this->authorize('startTest', $subject);

        try {
            $result = $this->generationService->generate($request->user(), $subject, $validated['mode'], $validated);
        } catch (HttpException $exception) {
            $payload = json_decode($exception->getMessage(), true);

            return back()
                ->withInput()
                ->withErrors([
                    'test' => $payload['message'] ?? 'Test oluşturulamadı.',
                    'error_code' => $payload['error'] ?? 'unknown_error',
                ], 'default');
        }

        $redirect = redirect()
            ->route('tests.show', ['test' => $result['test']->id])
            ->with('success', 'Test başarıyla başlatıldı.');

        if ($result['message']) {
            $redirect->with('info', $result['message']);
        }

        return $redirect;
    }

    public function show(Test $test, Request $request): View|RedirectResponse
    {
        $this->authorize('view', $test);
        $this->finalizeService->finalizeExpiredForUser($request->user()->id);
        $test->refresh();

        if ($test->status !== 'active') {
            return redirect()->route('tests.review', $test);
        }

        $test->load(['subject', 'items.question']);
        $current = max(1, min((int) $request->integer('q', 1), $test->items->count()));
        $item = $test->items->values()->get($current - 1);

        return view('tests.show', [
            'test' => $test,
            'item' => $item,
            'currentIndex' => $current,
            'totalItems' => $test->items->count(),
            'remainingSeconds' => max(0, now()->diffInSeconds($test->started_at->copy()->addMinutes(30), false)),
        ]);
    }

    public function answer(Test $test, Request $request): RedirectResponse
    {
        $this->authorize('answer', $test);
        $this->finalizeService->finalizeExpiredForUser($request->user()->id);
        $test->refresh()->load(['items.question']);

        if ($test->status !== 'active') {
            return redirect()->route('tests.review', $test);
        }

        $validated = $request->validate([
            'test_item_id' => ['required', 'integer'],
            'answer' => ['nullable', Rule::in(['A', 'B', 'C', 'D', 'E'])],
            'current_index' => ['required', 'integer', 'min:1'],
            'action' => ['nullable', Rule::in(['next', 'prev', 'stay'])],
        ]);

        $testItem = $test->items->firstWhere('id', $validated['test_item_id']);
        abort_unless($testItem, 404);

        $result = $this->answerEvaluationService->evaluate(
            $test,
            $testItem,
            $validated['answer'] ?? null
        );

        $nextIndex = match ($validated['action'] ?? 'stay') {
            'prev' => max(1, $validated['current_index'] - 1),
            'next' => min($test->items->count(), $validated['current_index'] + 1),
            default => $validated['current_index'],
        };

        return redirect()
            ->route('tests.show', ['test' => $test->id, 'q' => $nextIndex])
            ->with('answer_feedback', $result);
    }

    public function finish(Test $test, Request $request): RedirectResponse
    {
        $this->authorize('finish', $test);

        $this->finalizeService->finalize($test);

        return redirect()
            ->route('tests.review', $test)
            ->with('success', 'Test tamamlandı.');
    }

    public function review(Test $test): View
    {
        $this->authorize('review', $test);
        $test->load(['subject', 'items.question']);

        return view('tests.review', [
            'test' => $test,
            'percentages' => [
                'correct' => $this->percentage($test->correct_count, $test->question_count),
                'wrong' => $this->percentage($test->wrong_count, $test->question_count),
                'blank' => $this->percentage($test->blank_count, $test->question_count),
            ],
        ]);
    }

    private function percentage(int $value, int $total): float
    {
        if ($total === 0) {
            return 0;
        }

        return round(($value / $total) * 100, 1);
    }

    /**
     * ==================== API METHODS ====================
     */

    /**
     * Get user's active/recent tests
     */
    public function apiIndex(Request $request)
    {
        $tests = Test::query()
            ->where('user_id', $request->user()->id)
            ->with('subject')
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn (Test $test) => [
                'id' => $test->id,
                'subject_id' => $test->subject_id,
                'subject_name' => $test->subject->name,
                'status' => $test->status,
                'score' => $test->score,
                'question_count' => $test->question_count,
                'correct_count' => $test->correct_count,
                'wrong_count' => $test->wrong_count,
                'blank_count' => $test->blank_count,
                'started_at' => $test->started_at->toIso8601String(),
                'ended_at' => $test->ended_at?->toIso8601String(),
            ]);

        return response()->json($tests);
    }

    /**
     * Get single test detail
     */
    public function apiShow(Test $test, Request $request)
    {
        $this->authorize('view', $test);

        $test->load(['subject', 'items.question']);

        return response()->json([
            'id' => $test->id,
            'subject_id' => $test->subject_id,
            'subject_name' => $test->subject->name,
            'status' => $test->status,
            'feedback_mode' => $test->feedback_mode,
            'score' => $test->score,
            'question_count' => $test->question_count,
            'duration_minutes' => $test->duration_minutes,
            'correct_count' => $test->correct_count,
            'wrong_count' => $test->wrong_count,
            'blank_count' => $test->blank_count,
            'started_at' => $test->started_at->toIso8601String(),
            'ended_at' => $test->ended_at?->toIso8601String(),
            'items' => $test->items->map(fn ($item) => [
                'id' => $item->id,
                'question_id' => $item->question_id,
                'question_text' => $item->question->question_text,
                'user_answer' => $item->user_answer,
                'is_correct' => $item->is_correct,
                'awarded_points' => $item->awarded_points,
            ]),
        ]);
    }

    /**
     * Submit answer for test item
     */
    public function apiAnswer(Test $test, Request $request)
    {
        $this->authorize('answer', $test);
        $this->finalizeService->finalizeExpiredForUser($request->user()->id);
        $test->refresh()->load(['items.question']);

        $validated = $request->validate([
            'test_item_id' => ['required', 'integer'],
            'answer' => ['nullable', Rule::in(['A', 'B', 'C', 'D', 'E'])],
        ]);

        $testItem = $test->items->firstWhere('id', $validated['test_item_id']);
        abort_unless($testItem, 404);

        $result = $this->answerEvaluationService->evaluate(
            $test,
            $testItem,
            $validated['answer'] ?? null
        );

        return response()->json($result);
    }

    /**
     * Finish test
     */
    public function apiFinish(Test $test)
    {
        $this->authorize('finish', $test);

        $finalized = $this->finalizeService->finalize($test);

        return response()->json([
            'id' => $finalized->id,
            'status' => $finalized->status,
            'score' => $finalized->score,
            'correct_count' => $finalized->correct_count,
            'wrong_count' => $finalized->wrong_count,
            'blank_count' => $finalized->blank_count,
            'finished_at' => $finalized->ended_at->toIso8601String(),
        ]);
    }
}
