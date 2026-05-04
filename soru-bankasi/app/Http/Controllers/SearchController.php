<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Subject;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
        ]);

        $term = trim((string) ($validated['q'] ?? ''));
        $selectedSubjectId = $validated['subject_id'] ?? null;
        $subjects = Subject::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $subjectResults = collect();
        $questionResults = collect();

        if ($term !== '') {
            $subjectResults = Subject::query()
                ->where('is_active', true)
                ->when($selectedSubjectId, fn ($query) => $query->where('id', $selectedSubjectId))
                ->where(function ($query) use ($term) {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('slug', 'like', "%{$term}%");
                })
                ->withCount([
                    'questions as active_questions_count' => fn ($query) => $query->where('status', 'active'),
                ])
                ->orderBy('name')
                ->limit(12)
                ->get(['id', 'name', 'slug']);

            $questionResults = Question::query()
                ->with('subject:id,name,slug')
                ->where('status', 'active')
                ->whereHas('subject', fn ($query) => $query->where('is_active', true))
                ->when($selectedSubjectId, fn ($query) => $query->where('subject_id', $selectedSubjectId))
                ->where(function ($query) use ($term) {
                    $query->where('question_text', 'like', "%{$term}%")
                        ->orWhere('option_a', 'like', "%{$term}%")
                        ->orWhere('option_b', 'like', "%{$term}%")
                        ->orWhere('option_c', 'like', "%{$term}%")
                        ->orWhere('option_d', 'like', "%{$term}%")
                        ->orWhere('option_e', 'like', "%{$term}%")
                        ->orWhere('explanation_text', 'like', "%{$term}%");
                })
                ->orderByDesc('updated_at')
                ->limit(50)
                ->get();
        }

        return view('search.index', [
            'term' => $term,
            'subjects' => $subjects,
            'selectedSubjectId' => $selectedSubjectId,
            'subjectResults' => $subjectResults,
            'questionResults' => $questionResults,
        ]);
    }
}
