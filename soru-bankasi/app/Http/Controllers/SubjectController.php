<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Test;
use App\Models\UserRecentQuestionHistory;
use App\Models\UserWrongQuestionStat;
use App\Services\TestFinalizeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request, TestFinalizeService $finalizeService)
    {
        $this->authorize('viewAny', Subject::class);
        $finalizeService->finalizeExpiredForUser($request->user()->id);

        $user = $request->user();
        $selectedTerm = (int) $request->integer('term', (int) ($user->preferred_subject_term ?? 1));
        if (! in_array($selectedTerm, [1, 2], true)) {
            $selectedTerm = 1;
        }

        if ((int) ($user->preferred_subject_term ?? 1) !== $selectedTerm) {
            $user->forceFill(['preferred_subject_term' => $selectedTerm])->save();
        }

        $subjects = Subject::query()
            ->where('is_active', true)
            ->where('term', $selectedTerm)
            ->withCount([
                'questions as approved_questions_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'term']);

        $weakQuestionCounts = UserWrongQuestionStat::query()
            ->join('questions', 'questions.id', '=', 'user_wrong_question_stats.question_id')
            ->where('user_wrong_question_stats.user_id', $request->user()->id)
            ->where('questions.status', 'active')
            ->selectRaw('questions.subject_id, COUNT(DISTINCT user_wrong_question_stats.question_id) as weak_count')
            ->groupBy('questions.subject_id')
            ->pluck('weak_count', 'questions.subject_id');

        $performanceRows = Test::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'finished')
            ->selectRaw('subject_id, SUM(correct_count) as correct_total, SUM(question_count) as question_total')
            ->groupBy('subject_id')
            ->get()
            ->keyBy('subject_id');

        $solvedUniqueCounts = UserRecentQuestionHistory::query()
            ->join('questions', 'questions.id', '=', 'user_recent_question_history.question_id')
            ->where('user_recent_question_history.user_id', $request->user()->id)
            ->whereRaw('(user_recent_question_history.correct_count + user_recent_question_history.wrong_count) > 0')
            ->where('questions.status', 'active')
            ->selectRaw('questions.subject_id, COUNT(DISTINCT user_recent_question_history.question_id) as solved_unique_count')
            ->groupBy('questions.subject_id')
            ->pluck('solved_unique_count', 'questions.subject_id');

        $subjects->each(function (Subject $subject) use ($weakQuestionCounts, $performanceRows, $solvedUniqueCounts) {
            $performance = $performanceRows->get($subject->id);
            $questionTotal = (int) ($performance?->question_total ?? 0);
            $solvedUnique = (int) ($solvedUniqueCounts[$subject->id] ?? 0);
            $remainingUnique = max(0, ((int) $subject->approved_questions_count) - $solvedUnique);

            $subject->weak_questions_count = (int) ($weakQuestionCounts[$subject->id] ?? 0);
            $subject->solved_unique_count = $solvedUnique;
            $subject->remaining_unique_count = $remainingUnique;
            $subject->success_rate = $questionTotal > 0
                ? round(((int) $performance->correct_total / $questionTotal) * 100, 1)
                : null;
        });

        $preferredMode = in_array($user->preferred_test_mode, ['RANDOM', 'DIFFICULTY_RANGE', 'WEAKNESSES'], true)
            ? $user->preferred_test_mode
            : 'RANDOM';
        $preferredMinDifficulty = max(1, min(10, (int) ($user->preferred_min_difficulty ?? 3)));
        $preferredMaxDifficulty = max($preferredMinDifficulty, min(10, (int) ($user->preferred_max_difficulty ?? 7)));
        $preferredExcludeSolvedQuestions = (bool) ($user->preferred_exclude_solved_questions ?? false);
        $preferredSubjectId = $subjects->contains('id', (int) $user->preferred_subject_id)
            ? (int) $user->preferred_subject_id
            : (int) ($subjects->first()?->id ?? 0);

        return view('subjects.index', [
            'subjects' => $subjects,
            'selectedTerm' => $selectedTerm,
            'preferredMode' => $preferredMode,
            'preferredMinDifficulty' => $preferredMinDifficulty,
            'preferredMaxDifficulty' => $preferredMaxDifficulty,
            'preferredExcludeSolvedQuestions' => $preferredExcludeSolvedQuestions,
            'preferredSubjectId' => $preferredSubjectId,
            'activeTest' => Test::query()
                ->where('user_id', $request->user()->id)
                ->where('status', 'active')
                ->latest('started_at')
                ->first(),
        ]);
    }

    public function apiIndex(): JsonResponse
    {
        $subjects = Subject::query()
            ->where('is_active', true)
            ->withCount([
                'questions as active_questions_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Subject $subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
                'slug' => $subject->slug,
                'active_questions_count' => $subject->active_questions_count,
            ]);

        return response()->json($subjects);
    }

    public function apiShow(Subject $subject): JsonResponse
    {
        abort_unless($subject->is_active, 404);

        $subject->loadCount([
            'questions as active_questions_count' => fn ($query) => $query->where('status', 'active'),
            'tests as completed_tests_count' => fn ($query) => $query->where('status', 'finished'),
        ]);

        return response()->json([
            'id' => $subject->id,
            'name' => $subject->name,
            'slug' => $subject->slug,
            'active_questions_count' => $subject->active_questions_count,
            'completed_tests_count' => $subject->completed_tests_count,
        ]);
    }
}
