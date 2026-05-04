<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));

        $subjects = collect();
        $questions = collect();
        $reports = collect();
        $tests = collect();
        $users = collect();

        if ($query !== '') {
            $subjects = Subject::query()
                ->whereNull('archived_at')
                ->where(fn ($builder) => $builder
                    ->where('name', 'like', '%' . $query . '%')
                    ->orWhere('slug', 'like', '%' . $query . '%'))
                ->orderBy('name')
                ->limit(8)
                ->get();

            $questions = Question::query()
                ->with('subject:id,name')
                ->where('status', '!=', 'archived')
                ->where('question_text', 'like', '%' . $query . '%')
                ->latest()
                ->limit(8)
                ->get();

            $reports = QuestionReport::query()
                ->with(['user:id,name,email', 'question:id,question_text'])
                ->where(fn ($builder) => $builder
                    ->where('note', 'like', '%' . $query . '%')
                    ->orWhere('review_note', 'like', '%' . $query . '%')
                    ->orWhereHas('question', fn ($questionQuery) => $questionQuery->where('question_text', 'like', '%' . $query . '%')))
                ->latest()
                ->limit(8)
                ->get();

            $tests = Test::query()
                ->with(['user:id,name,email', 'subject:id,name'])
                ->where(fn ($builder) => $builder
                    ->whereDate('created_at', $query)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', '%' . $query . '%')
                        ->orWhere('email', 'like', '%' . $query . '%'))
                    ->orWhereHas('subject', fn ($subjectQuery) => $subjectQuery->where('name', 'like', '%' . $query . '%')))
                ->latest()
                ->limit(8)
                ->get();

            if ($request->user()->isAdmin()) {
                $users = User::query()
                    ->where(fn ($builder) => $builder
                        ->where('name', 'like', '%' . $query . '%')
                        ->orWhere('email', 'like', '%' . $query . '%'))
                    ->orderBy('name')
                    ->limit(8)
                    ->get();
            }
        }

        return view('admin.search.index', [
            'pageTitle' => 'Arama',
            'query' => $query,
            'subjects' => $subjects,
            'questions' => $questions,
            'reports' => $reports,
            'tests' => $tests,
            'users' => $users,
        ]);
    }
}
