<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Subject;
use App\Models\UserFavoriteQuestion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class FavoriteQuestionController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $term = trim((string) ($validated['q'] ?? ''));
        $subjectId = $validated['subject_id'] ?? null;

        $favorites = UserFavoriteQuestion::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'question.subject:id,name',
            ])
            ->when($subjectId, function ($query) use ($subjectId): void {
                $query->whereHas('question', fn ($q) => $q->where('subject_id', $subjectId));
            })
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($q) use ($term): void {
                    $q->where('note', 'like', '%' . $term . '%')
                        ->orWhereHas('question', function ($questionQuery) use ($term): void {
                            $questionQuery->where('question_text', 'like', '%' . $term . '%')
                                ->orWhere('option_a', 'like', '%' . $term . '%')
                                ->orWhere('option_b', 'like', '%' . $term . '%')
                                ->orWhere('option_c', 'like', '%' . $term . '%')
                                ->orWhere('option_d', 'like', '%' . $term . '%')
                                ->orWhere('option_e', 'like', '%' . $term . '%')
                                ->orWhere('explanation_text', 'like', '%' . $term . '%');
                        });
                });
            })
            ->latest('created_at')
            ->get();

        $groupedFavorites = $favorites
            ->groupBy(fn (UserFavoriteQuestion $favorite) => $favorite->question?->subject?->name ?? 'Dersi Silinmis')
            ->sortKeys();

        $subjects = Subject::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('questions.favorites', [
            'subjects' => $subjects,
            'selectedSubjectId' => $subjectId,
            'term' => $term,
            'favorites' => $favorites,
            'groupedFavorites' => $groupedFavorites,
        ]);
    }

    public function store(Question $question, Request $request): RedirectResponse
    {
        UserFavoriteQuestion::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'question_id' => $question->id,
        ]);

        return $this->redirectBack($request)->with('success', 'Soru favorilere eklendi.');
    }

    public function destroy(Question $question, Request $request): RedirectResponse
    {
        UserFavoriteQuestion::query()
            ->where('user_id', $request->user()->id)
            ->where('question_id', $question->id)
            ->delete();

        return $this->redirectBack($request)->with('success', 'Soru favorilerden cikarildi.');
    }

    public function updateNote(UserFavoriteQuestion $favorite, Request $request): RedirectResponse
    {
        abort_unless($favorite->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $favorite->update([
            'note' => trim((string) ($validated['note'] ?? '')) !== '' ? $validated['note'] : null,
        ]);

        return $this->redirectBack($request)->with('success', 'Favori notu guncellendi.');
    }

    private function redirectBack(Request $request): RedirectResponse
    {
        $target = (string) $request->input('redirect_to', '');
        $currentHost = (string) $request->getHost();

        if ($target !== '') {
            $targetHost = (string) parse_url($target, PHP_URL_HOST);
            $isRelative = Str::startsWith($target, '/');
            $isSameHost = $targetHost !== '' && strcasecmp($targetHost, $currentHost) === 0;

            if ($isRelative || $isSameHost) {
                return redirect()->to($target);
            }
        }

        $previousUrl = url()->previous();
        if (Str::contains($previousUrl, '/search') && ! Str::contains($previousUrl, '#question-results')) {
            return redirect()->to($previousUrl.'#question-results');
        }

        return back();
    }

    public function exportPdf(Request $request): Response
    {
        $validated = $request->validate([
            'favorite_ids' => ['required', 'array', 'min:1'],
            'favorite_ids.*' => ['integer'],
        ]);

        $favorites = UserFavoriteQuestion::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $validated['favorite_ids'])
            ->with('question.subject:id,name')
            ->get();

        abort_if($favorites->isEmpty(), 422, 'PDF indirmek icin en az bir favori soru secin.');

        $pdf = Pdf::loadView('questions.favorites-pdf', [
            'favorites' => $favorites,
            'generatedAt' => now(),
            'user' => $request->user(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('favori-sorular-' . now()->format('Ymd-His') . '.pdf');
    }
}
