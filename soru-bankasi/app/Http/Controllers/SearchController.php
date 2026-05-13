<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Question;
use App\Models\Subject;
use App\Models\UserFavoriteQuestion;
use App\Models\UserWrongQuestionStat;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        [$term, $selectedSubjectId, $stuckOnly, $useDifficulty, $minDifficulty, $maxDifficulty] = $this->validatedFilters($request);
        $subjects = Subject::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $subjectResults = collect();
        $questionQuery = $this->questionQuery($request, $term, $selectedSubjectId, $stuckOnly, $useDifficulty, $minDifficulty, $maxDifficulty);

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

            $questionQuery->where(function ($query) use ($term) {
                    $query->where('question_text', 'like', "%{$term}%")
                        ->orWhere('option_a', 'like', "%{$term}%")
                        ->orWhere('option_b', 'like', "%{$term}%")
                        ->orWhere('option_c', 'like', "%{$term}%")
                        ->orWhere('option_d', 'like', "%{$term}%")
                        ->orWhere('option_e', 'like', "%{$term}%")
                        ->orWhere('explanation_text', 'like', "%{$term}%");
                });
        }
        $showQuestions = $term !== '' || $selectedSubjectId !== null || $stuckOnly || $useDifficulty;
        $questionResults = $showQuestions
            ? $questionQuery->paginate(50)->withQueryString()->fragment('question-results')
            : new LengthAwarePaginator([], 0, 50);

        return view('search.index', [
            'term' => $term,
            'subjects' => $subjects,
            'selectedSubjectId' => $selectedSubjectId,
            'stuckOnly' => $stuckOnly,
            'useDifficulty' => $useDifficulty,
            'minDifficulty' => $minDifficulty,
            'maxDifficulty' => $maxDifficulty,
            'subjectResults' => $subjectResults,
            'questionResults' => $questionResults,
            'showQuestions' => $showQuestions,
            'favoriteQuestionIds' => UserFavoriteQuestion::query()
                ->where('user_id', $request->user()->id)
                ->pluck('question_id')
                ->map(fn ($id) => (int) $id)
                ->all(),
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        [$term, $selectedSubjectId, $stuckOnly, $useDifficulty, $minDifficulty, $maxDifficulty] = $this->validatedFilters($request);
        $showQuestions = $term !== '' || $selectedSubjectId !== null || $stuckOnly || $useDifficulty;

        abort_unless($showQuestions, 422, 'PDF indirmek icin en az bir ders secin veya arama yapin.');

        $questions = $this->questionQuery($request, $term, $selectedSubjectId, $stuckOnly, $useDifficulty, $minDifficulty, $maxDifficulty)
            ->limit(500)
            ->get();

        $selectedSubject = $selectedSubjectId
            ? Subject::query()->find($selectedSubjectId)
            : null;

        $pdf = Pdf::loadView('search.pdf', [
            'term' => $term,
            'selectedSubject' => $selectedSubject,
            'stuckOnly' => $stuckOnly,
            'useDifficulty' => $useDifficulty,
            'minDifficulty' => $minDifficulty,
            'maxDifficulty' => $maxDifficulty,
            'questions' => $questions,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $filename = 'soru-ara-' . now()->format('Ymd-His') . '.pdf';

        return $pdf->download($filename);
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'subject_id' => [
                'nullable',
                'integer',
                'exists:subjects,id',
                Rule::requiredIf(fn () => $request->boolean('stuck_only')),
            ],
            'stuck_only' => ['nullable', 'boolean'],
            'use_difficulty' => ['nullable', 'boolean'],
            'min_difficulty' => ['nullable', 'numeric', 'between:1,10'],
            'max_difficulty' => ['nullable', 'numeric', 'between:1,10'],
        ]);

        $stuckOnly = (bool) ($validated['stuck_only'] ?? false);
        $useDifficulty = (bool) ($validated['use_difficulty'] ?? false);
        $minDifficulty = (float) ($validated['min_difficulty'] ?? 3);
        $maxDifficulty = (float) ($validated['max_difficulty'] ?? 8);

        if ($maxDifficulty < $minDifficulty) {
            [$minDifficulty, $maxDifficulty] = [$maxDifficulty, $minDifficulty];
        }

        return [
            trim((string) ($validated['q'] ?? '')),
            $validated['subject_id'] ?? null,
            $stuckOnly,
            $useDifficulty,
            $minDifficulty,
            $maxDifficulty,
        ];
    }

    private function questionQuery(
        Request $request,
        string $term,
        ?int $selectedSubjectId,
        bool $stuckOnly,
        bool $useDifficulty,
        float $minDifficulty,
        float $maxDifficulty
    )
    {
        $query = Question::query()
            ->with('subject:id,name,slug')
            ->where('status', 'active')
            ->whereHas('subject', fn ($query) => $query->where('is_active', true))
            ->when($selectedSubjectId, fn ($query) => $query->where('subject_id', $selectedSubjectId))
            ->when($useDifficulty, fn ($query) => $query->whereBetween('difficulty_score', [$minDifficulty, $maxDifficulty]))
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('question_text', 'like', "%{$term}%")
                        ->orWhere('option_a', 'like', "%{$term}%")
                        ->orWhere('option_b', 'like', "%{$term}%")
                        ->orWhere('option_c', 'like', "%{$term}%")
                        ->orWhere('option_d', 'like', "%{$term}%")
                        ->orWhere('option_e', 'like', "%{$term}%")
                        ->orWhere('explanation_text', 'like', "%{$term}%");
                });
            });

        if ($stuckOnly) {
            $query->whereIn('id', UserWrongQuestionStat::query()
                ->where('user_id', $request->user()->id)
                ->select('question_id'));
        }

        return $query->orderByDesc('updated_at');
    }
}
