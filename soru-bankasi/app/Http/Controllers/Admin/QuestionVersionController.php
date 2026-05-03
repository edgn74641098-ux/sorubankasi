<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\TestItem;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuestionVersionController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(Question $question): View
    {
        $this->authorize('update', $question);

        return view('admin.questions.versions', [
            'question' => $question->load('subject:id,name'),
            'versions' => $question->versions()
                ->with('changedBy:id,name,email')
                ->latest('version_no')
                ->paginate(20),
        ]);
    }

    public function rollback(Request $request, Question $question, QuestionVersion $version): RedirectResponse
    {
        $this->authorize('update', $question);
        abort_unless($version->question_id === $question->id, 404);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $question, $version, $validated): void {
            $currentVersion = (int) $question->current_version;
            $currentPayload = $this->versionPayload($question);
            $rollbackPayload = $version->payload_json;

            QuestionVersion::query()->create([
                'question_id' => $question->id,
                'version_no' => $currentVersion,
                'changed_by' => $request->user()->id,
                'change_reason' => 'Rollback oncesi otomatik surum kaydi',
                'payload_json' => $currentPayload,
            ]);

            $question->update([
                'subject_id' => $rollbackPayload['subject_id'],
                'question_text' => $rollbackPayload['question_text'],
                'option_a' => $rollbackPayload['option_a'],
                'option_b' => $rollbackPayload['option_b'],
                'option_c' => $rollbackPayload['option_c'],
                'option_d' => $rollbackPayload['option_d'],
                'option_e' => $rollbackPayload['option_e'],
                'correct_option' => $rollbackPayload['correct_option'],
                'explanation_text' => $rollbackPayload['explanation_text'],
                'difficulty_score' => $rollbackPayload['difficulty_score'],
                'status' => $rollbackPayload['status'],
                'approved_by' => $rollbackPayload['approved_by'] ?? null,
                'approved_at' => $rollbackPayload['approved_at'] ?? null,
                'current_version' => $currentVersion + 1,
            ]);

            TestItem::query()
                ->where('question_id', $question->id)
                ->update(['rollback_applied' => true]);

            $this->auditLog->record(
                $request->user(),
                'question.rollback',
                'questions',
                $question->id,
                $currentPayload,
                $rollbackPayload,
                $validated['reason'],
                $request
            );
        });

        return redirect()
            ->route('admin.questions.versions.index', $question)
            ->with('success', 'Soru secilen surume geri alindi.');
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
