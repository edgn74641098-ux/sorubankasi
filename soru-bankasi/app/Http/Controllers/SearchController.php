<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        [$term, $selectedSubjectId] = $this->validatedFilters($request);
        $subjects = Subject::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $subjectResults = collect();
        $questionQuery = $this->questionQuery($term, $selectedSubjectId);

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
        $showQuestions = $term !== '' || $selectedSubjectId !== null;
        $questionResults = $showQuestions
            ? $questionQuery->paginate(50)->withQueryString()->fragment('question-results')
            : new LengthAwarePaginator([], 0, 50);

        return view('search.index', [
            'term' => $term,
            'subjects' => $subjects,
            'selectedSubjectId' => $selectedSubjectId,
            'subjectResults' => $subjectResults,
            'questionResults' => $questionResults,
            'showQuestions' => $showQuestions,
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        [$term, $selectedSubjectId] = $this->validatedFilters($request);
        $showQuestions = $term !== '' || $selectedSubjectId !== null;

        abort_unless($showQuestions, 422, 'PDF indirmek icin en az bir ders secin veya arama yapin.');

        $questions = $this->questionQuery($term, $selectedSubjectId)
            ->limit(500)
            ->get();

        $selectedSubject = $selectedSubjectId
            ? Subject::query()->find($selectedSubjectId)
            : null;

        $pdf = Pdf::loadView('search.pdf', [
            'term' => $term,
            'selectedSubject' => $selectedSubject,
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
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
        ]);

        return [
            trim((string) ($validated['q'] ?? '')),
            $validated['subject_id'] ?? null,
        ];
    }

    private function questionQuery(string $term, ?int $selectedSubjectId)
    {
        return Question::query()
            ->with('subject:id,name,slug')
            ->where('status', 'active')
            ->whereHas('subject', fn ($query) => $query->where('is_active', true))
            ->when($selectedSubjectId, fn ($query) => $query->where('subject_id', $selectedSubjectId))
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
            })
            ->orderByDesc('updated_at');
    }
}
