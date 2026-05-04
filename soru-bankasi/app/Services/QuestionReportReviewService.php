<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\QuestionVersion;
use App\Models\User;
use App\Notifications\QuestionReportAcceptedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionReportReviewService
{
    public function __construct(
        private readonly AuditLogService $auditLog
    ) {
    }

    public function approve(QuestionReport $report, User $reviewer, ?string $reviewNote, ?Request $request = null): QuestionReport
    {
        $oldCorrectOption = null;

        $report = DB::transaction(function () use ($report, $reviewer, $reviewNote, $request, &$oldCorrectOption): QuestionReport {
            $report->loadMissing(['question', 'user']);
            $question = $report->question;
            $oldCorrectOption = $question->correct_option;

            if ($report->suggested_correct_option && $report->suggested_correct_option !== $question->correct_option) {
                $this->snapshotQuestion($question, $reviewer, $report);

                $question->update([
                    'correct_option' => $report->suggested_correct_option,
                    'current_version' => (int) $question->current_version + 1,
                ]);
            }

            $userMessage = $this->acceptedMessage($report, $oldCorrectOption);

            $report->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $reviewNote,
                'user_message' => $userMessage,
            ]);

            $this->auditLog->record(
                $reviewer,
                'question_report.approved',
                'question_reports',
                $report->id,
                ['status' => 'pending', 'correct_option' => $oldCorrectOption],
                [
                    'status' => 'approved',
                    'review_note' => $reviewNote,
                    'suggested_correct_option' => $report->suggested_correct_option,
                    'correct_option' => $report->suggested_correct_option ?: $oldCorrectOption,
                ],
                'Soru itirazi onaylandi.',
                $request
            );

            return $report->fresh(['question', 'user', 'reviewedBy']);
        });

        $this->notifyAccepted($report, $oldCorrectOption);

        return $report;
    }

    public function reject(QuestionReport $report, User $reviewer, string $reviewNote, ?Request $request = null): QuestionReport
    {
        $report->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $reviewNote,
            'user_message' => "Itiraziniz incelendi ancak kabul edilmedi. Not: {$reviewNote}",
        ]);

        $this->auditLog->record(
            $reviewer,
            'question_report.rejected',
            'question_reports',
            $report->id,
            ['status' => 'pending'],
            ['status' => 'rejected', 'review_note' => $reviewNote],
            "Soru itirazi reddedildi. Sebep: {$reviewNote}",
            $request
        );

        return $report->fresh(['question', 'user', 'reviewedBy']);
    }

    private function snapshotQuestion(Question $question, User $reviewer, QuestionReport $report): void
    {
        QuestionVersion::query()->create([
            'question_id' => $question->id,
            'version_no' => (int) $question->current_version,
            'changed_by' => $reviewer->id,
            'change_reason' => "Itiraz #{$report->id} kabul edildi; dogru cevap guncellendi.",
            'payload_json' => [
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
            ],
        ]);
    }

    private function acceptedMessage(QuestionReport $report, ?string $oldCorrectOption): string
    {
        if ($report->suggested_correct_option) {
            return "Itiraziniz kabul edildi. Katkiniz icin tesekkur ederiz. Sorunun dogru cevabi {$oldCorrectOption} yerine {$report->suggested_correct_option} olarak guncellendi.";
        }

        return 'Itiraziniz kabul edildi. Katkiniz ve dikkatiniz icin tesekkur ederiz.';
    }

    private function notifyAccepted(QuestionReport $report, ?string $oldCorrectOption): void
    {
        try {
            $report->user->notify(new QuestionReportAcceptedNotification($report, $oldCorrectOption));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
