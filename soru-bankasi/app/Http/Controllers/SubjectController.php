<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Test;
use App\Services\TestFinalizeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request, TestFinalizeService $finalizeService)
    {
        $this->authorize('viewAny', Subject::class);
        $finalizeService->finalizeExpiredForUser($request->user()->id);

        $subjects = Subject::query()
            ->where('is_active', true)
            ->withCount([
                'questions as approved_questions_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('subjects.index', [
            'subjects' => $subjects,
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
