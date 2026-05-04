<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Subject;
use App\Models\UserSubmittedQuestion;
use App\Services\AuditLogService;
use App\Services\RateLimitingService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserSubmittedQuestionController extends Controller
{
    public function __construct(
        private readonly RateLimitingService $rateLimitingService,
        private readonly AuditLogService $auditLog,
        private readonly SettingsService $settingsService
    ) {
    }

    public function create(): View
    {
        abort_unless($this->settingsService->getBool('user_submissions_enabled', true), 403);

        return view('questions.submit', [
            'subjects' => Subject::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $this->settingsService->getBool('user_submissions_enabled', true)) {
            return back()->withErrors([
                'submission' => 'Soru onerisi gonderimi su anda kapali.',
            ]);
        }

        if (! $this->rateLimitingService->canSubmitQuestion($user)) {
            return back()->withErrors([
                'submission' => "Bugun {$this->rateLimitingService->getDailyQuestionLimit()} soru limitine ulastiniz. Lutfen yarin deneyin.",
            ]);
        }

        $this->createSubmission($user, $this->validatedSubmissionData($request));

        return redirect()
            ->route('questions.submitted')
            ->with('success', 'Sorunuz basariyla gonderildi. Moderator tarafindan incelenecektir.');
    }

    public function myQuestions(): View
    {
        $user = Auth::user();

        return view('questions.my-submissions', [
            'submissions' => $user->submittedQuestions()
                ->with('subject')
                ->where('submission_type', 'question_suggestion')
                ->latest('created_at')
                ->paginate(10),
        ]);
    }

    public function reportUnnecessary(Request $request, Question $question): RedirectResponse
    {
        $user = $request->user();

        if ($question->status === 'archived') {
            return back()->withErrors([
                'submission' => 'Bu soru zaten sistemden kaldirilmis.',
            ]);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $existing = UserSubmittedQuestion::query()
            ->where('user_id', $user->id)
            ->where('reported_question_id', $question->id)
            ->where('submission_type', 'unnecessary_question_report')
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return back()->with('info', 'Bu soru icin bekleyen gereksiz soru bildiriminiz zaten var.');
        }

        $submission = UserSubmittedQuestion::query()->create([
            'user_id' => $user->id,
            'subject_id' => $question->subject_id,
            'submission_type' => 'unnecessary_question_report',
            'reported_question_id' => $question->id,
            'payload_json' => [
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'options' => [
                    'A' => $question->option_a,
                    'B' => $question->option_b,
                    'C' => $question->option_c,
                    'D' => $question->option_d,
                    'E' => $question->option_e,
                ],
                'correct_option' => $question->correct_option,
                'reason' => $validated['reason'],
            ],
            'status' => 'pending',
        ]);

        $this->auditLog->record(
            $user,
            'user_submission.unnecessary_question_reported',
            'user_submitted_questions',
            $submission->id,
            null,
            [
                'question_id' => $question->id,
                'reason' => $validated['reason'],
            ],
            'Kullanici gereksiz soru bildirimi gonderdi.',
            $request
        );

        return back()->with('success', 'Gereksiz soru bildiriminiz inceleme kuyruguna alindi.');
    }

    public function apiMySubmissions(Request $request): JsonResponse
    {
        $submissions = $request->user()
            ->submittedQuestions()
            ->with('subject:id,name,slug')
            ->where('submission_type', 'question_suggestion')
            ->latest('created_at')
            ->paginate(15);

        return response()->json([
            'data' => $submissions->getCollection()->map(fn (UserSubmittedQuestion $submission) => [
                'id' => $submission->id,
                'subject' => [
                    'id' => $submission->subject?->id,
                    'name' => $submission->subject?->name,
                    'slug' => $submission->subject?->slug,
                ],
                'question_text' => $submission->payload_json['question_text'] ?? null,
                'status' => $submission->status,
                'review_note' => $submission->review_note,
                'reviewed_at' => $submission->reviewed_at?->toIso8601String(),
                'created_at' => $submission->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $submissions->currentPage(),
                'last_page' => $submissions->lastPage(),
                'per_page' => $submissions->perPage(),
                'total' => $submissions->total(),
            ],
        ]);
    }

    public function apiStore(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->settingsService->getBool('user_submissions_enabled', true)) {
            return response()->json([
                'message' => 'Soru onerisi gonderimi su anda kapali.',
            ], 403);
        }

        if (! $this->rateLimitingService->canSubmitQuestion($user)) {
            return response()->json([
                'message' => "Bugun {$this->rateLimitingService->getDailyQuestionLimit()} soru limitine ulastiniz. Lutfen yarin deneyin.",
            ], 429);
        }

        $submission = $this->createSubmission($user, $this->validatedSubmissionData($request));

        return response()->json([
            'id' => $submission->id,
            'status' => $submission->status,
            'created_at' => $submission->created_at?->toIso8601String(),
        ], 201);
    }

    public function pendingReview(): View
    {
        $this->authorize('reviewSubmissions', UserSubmittedQuestion::class);

        return view('admin.submissions.pending', [
            'submissions' => UserSubmittedQuestion::query()
                ->with(['user:id,name,email', 'subject:id,name', 'reportedQuestion:id,subject_id,question_text,status,approved_by,approved_at,archived_at,purge_after,current_version'])
                ->where('status', 'pending')
                ->latest('created_at')
                ->paginate(15),
            'approvalReward' => $this->settingsService->getInt('submission_approval_reward', 10),
            'rejectionNoteRequired' => $this->settingsService->getBool('submission_rejection_note_required', true),
        ]);
    }

    public function approve(Request $request, UserSubmittedQuestion $submission): RedirectResponse
    {
        $this->authorize('reviewSubmissions', $submission);

        if ($submission->status !== 'pending') {
            return back()->withErrors('Bu soru zaten incelenmis.');
        }

        if ($submission->isUnnecessaryQuestionReport()) {
            return $this->approveUnnecessaryQuestionReport($request, $submission);
        }

        DB::transaction(function () use ($request, $submission): void {
            $payload = $submission->payload_json;

            $question = Question::query()->create([
                'subject_id' => $submission->subject_id,
                'created_by' => $submission->user_id,
                'approved_by' => Auth::id(),
                'source_type' => 'user_submission',
                'question_text' => $payload['question_text'],
                'option_a' => $payload['options']['A'],
                'option_b' => $payload['options']['B'],
                'option_c' => $payload['options']['C'],
                'option_d' => $payload['options']['D'],
                'option_e' => $payload['options']['E'],
                'correct_option' => $payload['correct_option'],
                'explanation_text' => $payload['explanation_text'],
                'difficulty_score' => 5,
                'status' => 'active',
                'approved_at' => now(),
                'current_version' => 1,
            ]);

            $reward = $this->settingsService->getInt('submission_approval_reward', 10);
            $submission->user->increment('total_score', $reward);
            $submission->update([
                'status' => 'approved',
                'approved_question_id' => $question->id,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'review_note' => $request->input('review_note') ?: null,
            ]);

            $this->auditLog->record(
                $request->user(),
                'user_submission.approved',
                'user_submitted_questions',
                $submission->id,
                ['status' => 'pending'],
                ['status' => 'approved', 'question_id' => $question->id, 'reward' => $reward],
                "Kullanici sorusu onaylandi ve +{$reward} puan verildi.",
                $request
            );
        });

        return back()->with('success', 'Soru onaylandi. Kullaniciya +' . $this->settingsService->getInt('submission_approval_reward', 10) . ' puan verildi.');
    }

    public function reject(Request $request, UserSubmittedQuestion $submission): RedirectResponse
    {
        $this->authorize('reviewSubmissions', $submission);

        $noteRequired = $this->settingsService->getBool('submission_rejection_note_required', true);
        $validated = $request->validate([
            'review_note' => [$noteRequired ? 'required' : 'nullable', 'string', 'min:10', 'max:500'],
        ]);

        if ($submission->status !== 'pending') {
            return back()->withErrors('Bu soru zaten incelenmis.');
        }

        $submission->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => $validated['review_note'] ?? null,
        ]);

        $this->auditLog->record(
            $request->user(),
            'user_submission.rejected',
            'user_submitted_questions',
            $submission->id,
            ['status' => 'pending'],
            ['status' => 'rejected', 'review_note' => $validated['review_note'] ?? null],
            ($submission->isUnnecessaryQuestionReport() ? 'Gereksiz soru bildirimi reddedildi.' : 'Kullanici sorusu reddedildi.')
                . (filled($validated['review_note'] ?? null) ? " Sebep: {$validated['review_note']}" : ''),
            $request
        );

        return back()->with('success', $submission->isUnnecessaryQuestionReport() ? 'Bildirim reddedildi. Soru sistemde kalmaya devam edecek.' : 'Soru reddedildi.');
    }

    public function revokeApproval(Request $request, UserSubmittedQuestion $submission): RedirectResponse
    {
        $this->authorize('revokeApproval', $submission);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        if ($submission->status !== 'approved') {
            return back()->withErrors('Sadece onaylanan sorular icin geri alinabilir.');
        }

        DB::transaction(function () use ($request, $submission, $validated): void {
            $reward = $this->settingsService->getInt('submission_approval_reward', 10);
            $submission->user->decrement('total_score', $reward);

            if ($submission->approvedQuestion) {
                $submission->approvedQuestion->update([
                    'status' => 'inactive',
                    'approved_by' => null,
                    'approved_at' => null,
                ]);
            }

            $submission->update([
                'status' => 'rejected',
                'review_note' => $validated['reason'],
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            $this->auditLog->record(
                $request->user(),
                'user_submission.approval_revoked',
                'user_submitted_questions',
                $submission->id,
                ['status' => 'approved'],
                ['status' => 'rejected', 'reason' => $validated['reason'], 'reward' => -1 * $reward],
                "Onaylanan soru geri alindi. Sebep: {$validated['reason']}",
                $request
            );
        });

        return back()->with('success', 'Soru onaylamasi geri alindi. Kullanicidan -' . $this->settingsService->getInt('submission_approval_reward', 10) . ' puan kaldirildi.');
    }

    private function sanitizeValidated(array $validated): array
    {
        foreach (['question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'explanation_text'] as $key) {
            $validated[$key] = trim(strip_tags($validated[$key]));
        }

        return $validated;
    }

    private function validatedSubmissionData(Request $request): array
    {
        return $this->sanitizeValidated($request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'question_text' => ['required', 'string', 'min:20', 'max:4000'],
            'option_a' => ['required', 'string', 'min:1', 'max:500'],
            'option_b' => ['required', 'string', 'min:1', 'max:500'],
            'option_c' => ['required', 'string', 'min:1', 'max:500'],
            'option_d' => ['required', 'string', 'min:1', 'max:500'],
            'option_e' => ['required', 'string', 'min:1', 'max:500'],
            'correct_option' => ['required', 'in:A,B,C,D,E'],
            'explanation_text' => ['required', 'string', 'min:20', 'max:2000'],
        ]));
    }

    private function createSubmission($user, array $validated): UserSubmittedQuestion
    {
        return UserSubmittedQuestion::query()->create([
            'user_id' => $user->id,
            'subject_id' => $validated['subject_id'],
            'submission_type' => 'question_suggestion',
            'payload_json' => [
                'question_text' => $validated['question_text'],
                'options' => [
                    'A' => $validated['option_a'],
                    'B' => $validated['option_b'],
                    'C' => $validated['option_c'],
                    'D' => $validated['option_d'],
                    'E' => $validated['option_e'],
                ],
                'correct_option' => $validated['correct_option'],
                'explanation_text' => $validated['explanation_text'],
            ],
            'status' => 'pending',
        ]);
    }

    private function approveUnnecessaryQuestionReport(Request $request, UserSubmittedQuestion $submission): RedirectResponse
    {
        $question = $submission->reportedQuestion;

        if (! $question || $question->status === 'archived') {
            $submission->update([
                'status' => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'review_note' => $request->input('review_note') ?: null,
            ]);

            return back()->with('success', 'Bildirim onaylandi. Soru zaten sistemden kaldirilmis.');
        }

        DB::transaction(function () use ($request, $submission, $question): void {
            $currentVersion = (int) $question->current_version;
            $archiveAt = now();
            $oldValue = $this->questionPayload($question);

            QuestionVersion::query()->create([
                'question_id' => $question->id,
                'version_no' => $currentVersion,
                'changed_by' => $request->user()->id,
                'change_reason' => 'Gereksiz soru bildirimi onayi ile sistemden kaldirma',
                'payload_json' => $oldValue,
            ]);

            $question->update([
                'status' => 'archived',
                'approved_by' => null,
                'approved_at' => null,
                'archived_at' => $archiveAt,
                'purge_after' => $this->purgeAfter($archiveAt),
                'current_version' => $currentVersion + 1,
            ]);

            $submission->update([
                'status' => 'approved',
                'approved_question_id' => $question->id,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'review_note' => $request->input('review_note') ?: null,
            ]);

            $this->auditLog->record(
                $request->user(),
                'user_submission.unnecessary_question_approved',
                'user_submitted_questions',
                $submission->id,
                ['status' => 'pending', 'question' => $oldValue],
                ['status' => 'approved', 'question' => $this->questionPayload($question->fresh())],
                'Gereksiz soru bildirimi onaylandi ve soru sistemden kaldirildi.',
                $request
            );
        });

        return back()->with('success', 'Bildirim onaylandi. Soru sistemden kaldirildi.');
    }

    private function purgeAfter($archiveAt)
    {
        if (! $this->settingsService->getBool('archive_auto_prune_enabled', true)) {
            return null;
        }

        return $archiveAt->copy()->addDays($this->settingsService->getInt('archive_retention_days', 7));
    }

    private function questionPayload(Question $question): array
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
            'archived_at' => optional($question->archived_at)->toISOString(),
            'purge_after' => optional($question->purge_after)->toISOString(),
            'current_version' => $question->current_version,
        ];
    }
}
