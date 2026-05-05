<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Subject::class);
        $this->authorize('viewAny', Question::class);

        $subjectSearch = $request->string('subject_search')->value();
        $questionSearch = $request->string('question_search')->value();
        $questionSubjectId = $request->input('question_subject_id');

        $subjects = Subject::query()
            ->withCount('questions')
            ->whereNotNull('archived_at')
            ->when($subjectSearch !== '', function ($query) use ($subjectSearch): void {
                $query->where(function ($query) use ($subjectSearch): void {
                    $query->where('name', 'like', '%' . $subjectSearch . '%')
                        ->orWhere('slug', 'like', '%' . $subjectSearch . '%');
                });
            })
            ->latest('archived_at')
            ->paginate(10, ['*'], 'subjects_page')
            ->withQueryString();

        $subjectOptions = Subject::query()
            ->where(function ($query): void {
                $query->whereNull('archived_at')
                    ->orWhereHas('questions', fn ($query) => $query->where('status', 'archived'));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'archived_at']);

        $questions = Question::query()
            ->with(['subject:id,name', 'createdBy:id,name'])
            ->where('status', 'archived')
            ->when($questionSubjectId, fn ($query) => $query->where('subject_id', $questionSubjectId))
            ->when($questionSearch !== '', function ($query) use ($questionSearch): void {
                $query->where(function ($query) use ($questionSearch): void {
                    $query->where('question_text', 'like', '%' . $questionSearch . '%')
                        ->orWhere('option_a', 'like', '%' . $questionSearch . '%')
                        ->orWhere('option_b', 'like', '%' . $questionSearch . '%')
                        ->orWhere('option_c', 'like', '%' . $questionSearch . '%')
                        ->orWhere('option_d', 'like', '%' . $questionSearch . '%')
                        ->orWhere('option_e', 'like', '%' . $questionSearch . '%');
                });
            })
            ->latest('archived_at')
            ->paginate(10, ['*'], 'questions_page')
            ->withQueryString();

        return view('admin.archive.index', [
            'pageTitle' => 'Arsiv',
            'subjects' => $subjects,
            'questions' => $questions,
            'subjectOptions' => $subjectOptions,
            'filters' => [
                'subject_search' => $subjectSearch,
                'question_search' => $questionSearch,
                'question_subject_id' => $questionSubjectId,
            ],
        ]);
    }

    public function restoreSubject(Subject $subject): RedirectResponse
    {
        $this->authorize('delete', $subject);

        $this->restoreSubjectRecord($subject);
        $this->writeAudit('archive.subject_restored', 'subjects', $subject->id, ['name' => $subject->name]);

        return redirect()
            ->route('admin.archive.index')
            ->with('success', 'Ders arsivden geri alindi. Bagli sorular aktif hale getirildi.');
    }

    public function restoreQuestion(Question $question): RedirectResponse
    {
        $this->authorize('delete', $question);

        $this->restoreQuestionRecord($question);
        $this->writeAudit('archive.question_restored', 'questions', $question->id, ['question_text' => $question->question_text]);

        return redirect()
            ->route('admin.archive.index')
            ->with('success', 'Soru arsivden geri alindi ve aktif hale getirildi.');
    }

    public function restoreSubjects(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ]);

        $subjects = Subject::query()
            ->whereIn('id', $validated['subject_ids'])
            ->whereNotNull('archived_at')
            ->get();

        DB::transaction(function () use ($subjects): void {
            $subjects->each(function (Subject $subject): void {
                $this->restoreSubjectRecord($subject);
                $this->writeAudit('archive.subject_restored_bulk', 'subjects', $subject->id, ['name' => $subject->name]);
            });
        });

        return redirect()
            ->route('admin.archive.index')
            ->with('success', $subjects->count() . ' ders arsivden geri alindi. Bagli sorular aktif hale getirildi.');
    }

    public function removeSubject(Subject $subject): RedirectResponse
    {
        abort_unless(request()->user()?->isAdmin(), 403);
        $this->authorize('delete', $subject);

        DB::transaction(function () use ($subject): void {
            $this->removeSubjectRecord($subject);
            $this->writeAudit('archive.subject_removed', 'subjects', $subject->id, ['name' => $subject->name]);
        });

        return redirect()
            ->route('admin.archive.index')
            ->with('success', 'Ders arsivden kaldirildi. Gecmis test ve log kayitlari korunur.');
    }

    public function removeSubjects(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ]);

        $subjects = Subject::query()
            ->whereIn('id', $validated['subject_ids'])
            ->whereNotNull('archived_at')
            ->get();

        DB::transaction(function () use ($subjects): void {
            $subjects->each(function (Subject $subject): void {
                $this->removeSubjectRecord($subject);
                $this->writeAudit('archive.subject_removed_bulk', 'subjects', $subject->id, ['name' => $subject->name]);
            });
        });

        return redirect()
            ->route('admin.archive.index')
            ->with('success', $subjects->count() . ' ders arsivden kaldirildi. Gecmis test ve log kayitlari korunur.');
    }

    public function restoreQuestions(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'exists:questions,id'],
        ]);

        $questions = Question::query()
            ->with('subject')
            ->whereIn('id', $validated['question_ids'])
            ->where('status', 'archived')
            ->get();

        DB::transaction(function () use ($questions): void {
            $questions->each(function (Question $question): void {
                $this->restoreQuestionRecord($question);
                $this->writeAudit('archive.question_restored_bulk', 'questions', $question->id, ['question_text' => $question->question_text]);
            });
        });

        return redirect()
            ->route('admin.archive.index')
            ->with('success', $questions->count() . ' soru arsivden geri alindi ve aktif hale getirildi.');
    }

    public function removeQuestion(Question $question): RedirectResponse
    {
        abort_unless(request()->user()?->isAdmin(), 403);
        $this->authorize('delete', $question);

        DB::transaction(function () use ($question): void {
            $this->removeQuestionRecord($question);
            $this->writeAudit('archive.question_removed', 'questions', $question->id, ['question_text' => $question->question_text]);
        });

        return redirect()
            ->route('admin.archive.index')
            ->with('success', 'Soru arsivden kaldirildi. Gecmis test ve log kayitlari korunur.');
    }

    public function removeQuestions(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'exists:questions,id'],
        ]);

        $questions = Question::query()
            ->whereIn('id', $validated['question_ids'])
            ->where('status', 'archived')
            ->get();

        DB::transaction(function () use ($questions): void {
            $questions->each(function (Question $question): void {
                $this->removeQuestionRecord($question);
                $this->writeAudit('archive.question_removed_bulk', 'questions', $question->id, ['question_text' => $question->question_text]);
            });
        });

        return redirect()
            ->route('admin.archive.index')
            ->with('success', $questions->count() . ' soru arsivden kaldirildi. Gecmis test ve log kayitlari korunur.');
    }

    private function restoreSubjectRecord(Subject $subject): void
    {
        $subject->update([
            'is_active' => true,
            'archived_at' => null,
            'purge_after' => null,
        ]);

        $subject->questions()
            ->where('status', 'archived')
            ->update([
                'status' => 'active',
                'approved_by' => request()->user()?->id,
                'approved_at' => now(),
                'archived_at' => null,
                'purge_after' => null,
                'updated_at' => now(),
            ]);
    }

    private function restoreQuestionRecord(Question $question): void
    {
        if ($question->subject?->archived_at) {
            $question->subject->update([
                'is_active' => true,
                'archived_at' => null,
                'purge_after' => null,
            ]);
        }

        $question->update([
            'status' => 'active',
            'approved_by' => request()->user()?->id,
            'approved_at' => now(),
            'archived_at' => null,
            'purge_after' => null,
        ]);
    }

    private function removeSubjectRecord(Subject $subject): void
    {
        $subject->questions()
            ->where('status', 'archived')
            ->get()
            ->each(fn (Question $question) => $question->delete());

        $subject->delete();
    }

    private function removeQuestionRecord(Question $question): void
    {
        if ($question->status !== 'archived') {
            return;
        }

        $question->delete();
    }

    private function writeAudit(string $action, string $entityType, int $entityId, array $newValue): void
    {
        $isRemoved = str_contains($action, '_removed');

        AuditLog::query()->create([
            'actor_id' => request()->user()?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_value' => ['status' => 'archived'],
            'new_value' => $newValue + ['status' => $isRemoved ? 'removed_from_archive_soft_deleted' : 'restored_active'],
            'reason' => $isRemoved ? 'Arsivden kaldirma' : 'Arsivden geri alma',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
