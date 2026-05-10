<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Subject;
use App\Services\AuditLogService;
use App\Services\QuestionReportReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuestionReportController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly QuestionReportReviewService $reviewService
    ) {
    }

    public function store(Request $request): RedirectResponse
    {
        $result = $this->createReport($request);

        if ($result['duplicate']) {
            return back()->with('info', 'Bu soru icin bekleyen bir itiraziniz zaten var.');
        }

        return back()->with('success', 'Itiraziniz inceleme kuyruguna alindi.');
    }

    public function apiStore(Request $request): JsonResponse
    {
        $result = $this->createReport($request);

        if ($result['duplicate']) {
            return response()->json([
                'message' => 'Bu soru icin zaten bir itiraz gonderdiniz.',
                'report_id' => $result['report']->id,
            ], 409);
        }

        $report = $result['report'];

        return response()->json([
            'id' => $report->id,
            'status' => $report->status,
            'created_at' => $report->created_at->toIso8601String(),
        ], 201);
    }

    public function myReports(Request $request): View
    {
        return view('questions.my-reports', [
            'reports' => $this->myReportsQuery($request)
                ->paginate(10)
                ->withQueryString(),
            'filters' => [
                'status' => $request->input('status'),
            ],
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function apiMine(Request $request): JsonResponse
    {
        $reports = $this->myReportsQuery($request)
            ->paginate(15)
            ->withQueryString();

        return response()->json([
            'data' => $reports->getCollection()->map(fn (QuestionReport $report) => [
                'id' => $report->id,
                'question' => [
                    'id' => $report->question->id,
                    'text' => $report->question->question_text,
                    'subject' => $report->question->subject?->name,
                    'current_correct_option' => $report->question->correct_option,
                ],
                'category' => $report->category,
                'category_label' => $report->category_label,
                'suggested_correct_option' => $report->suggested_correct_option,
                'suggested_subject_id' => $report->suggested_subject_id,
                'suggested_subject_name' => $report->suggestedSubject?->name,
                'suggested_payload_json' => $report->suggested_payload_json,
                'note' => $report->note,
                'status' => $report->status,
                'status_label' => $report->status_label,
                'review_note' => $report->review_note,
                'user_message' => $report->user_message,
                'reviewed_at' => $report->reviewed_at?->toIso8601String(),
                'created_at' => $report->created_at->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function apiPending(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QuestionReport::class);

        $reports = QuestionReport::query()
            ->with(['user:id,name,email', 'question:id,question_text,subject_id', 'question.subject:id,name', 'suggestedSubject:id,name'])
            ->where('status', 'pending')
            ->latest('created_at')
            ->paginate(15);

        return response()->json([
            'data' => $reports->getCollection()->map(fn (QuestionReport $report) => [
                'id' => $report->id,
                'user' => [
                    'id' => $report->user->id,
                    'name' => $report->user->name,
                    'email' => $report->user->email,
                ],
                'question' => [
                    'id' => $report->question->id,
                    'text' => $report->question->question_text,
                    'subject' => $report->question->subject?->name,
                ],
                'category' => $report->category,
                'category_label' => $report->category_label,
                'note' => $report->note,
                'suggested_correct_option' => $report->suggested_correct_option,
                'suggested_subject_id' => $report->suggested_subject_id,
                'suggested_subject_name' => $report->suggestedSubject?->name,
                'suggested_payload_json' => $report->suggested_payload_json,
                'status' => $report->status,
                'created_at' => $report->created_at->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function apiApprove(Request $request, QuestionReport $report): JsonResponse
    {
        $this->authorize('update', $report);

        if ($report->status !== 'pending') {
            return response()->json([
                'message' => 'Bu itiraz zaten incelenmis.',
            ], 409);
        }

        $validated = $request->validate([
            'action' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $report = $validated['action'] === 'approved'
            ? $this->reviewService->approve($report, $request->user(), $validated['review_note'] ?? null, $request)
            : $this->reviewService->reject($report, $request->user(), $validated['review_note'] ?: 'Itiraz kabul edilmedi.', $request);

        return response()->json([
            'id' => $report->id,
            'status' => $report->status,
            'suggested_correct_option' => $report->suggested_correct_option,
            'user_message' => $report->user_message,
            'reviewed_at' => $report->reviewed_at->toIso8601String(),
        ]);
    }

    private function createReport(Request $request): array
    {
        $user = $request->user();

        $validated = $request->validate([
            'question_id' => ['required', 'exists:questions,id'],
            'category' => ['required', Rule::in(['WRONG_ANSWER', 'UNCLEAR_WORDING', 'TYPO', 'WRONG_SUBJECT', 'OTHER'])],
            'suggested_correct_option' => [
                Rule::requiredIf(fn () => $request->input('category') !== 'WRONG_SUBJECT'),
                'nullable',
                Rule::in(['A', 'B', 'C', 'D', 'E']),
            ],
            'suggested_subject_id' => [
                Rule::requiredIf(fn () => $request->input('category') === 'WRONG_SUBJECT'),
                'nullable',
                'integer',
                'exists:subjects,id',
            ],
            'note' => ['nullable', 'string', 'max:500'],
            'suggested_question_text' => [Rule::requiredIf(fn () => $request->input('category') === 'TYPO'), 'nullable', 'string', 'min:10', 'max:4000'],
            'suggested_option_a' => [Rule::requiredIf(fn () => $request->input('category') === 'TYPO'), 'nullable', 'string', 'min:1', 'max:1000'],
            'suggested_option_b' => [Rule::requiredIf(fn () => $request->input('category') === 'TYPO'), 'nullable', 'string', 'min:1', 'max:1000'],
            'suggested_option_c' => [Rule::requiredIf(fn () => $request->input('category') === 'TYPO'), 'nullable', 'string', 'min:1', 'max:1000'],
            'suggested_option_d' => [Rule::requiredIf(fn () => $request->input('category') === 'TYPO'), 'nullable', 'string', 'min:1', 'max:1000'],
            'suggested_option_e' => [Rule::requiredIf(fn () => $request->input('category') === 'TYPO'), 'nullable', 'string', 'min:1', 'max:1000'],
            'suggested_explanation_text' => [Rule::requiredIf(fn () => $request->input('category') === 'TYPO'), 'nullable', 'string', 'min:1', 'max:2000'],
        ]);

        $question = Question::query()->findOrFail($validated['question_id']);
        $suggestedSubjectId = $validated['suggested_subject_id'] ?? null;
        if ($suggestedSubjectId !== null && (int) $suggestedSubjectId === (int) $question->subject_id) {
            $suggestedSubjectId = null;
        }

        $existingReport = QuestionReport::query()
            ->where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->where('status', 'pending')
            ->first();

        if ($existingReport) {
            return [
                'duplicate' => true,
                'report' => $existingReport,
            ];
        }

        $suggestedPayload = $validated['category'] === 'TYPO'
            ? [
                'question_text' => trim((string) ($validated['suggested_question_text'] ?? '')),
                'option_a' => trim((string) ($validated['suggested_option_a'] ?? '')),
                'option_b' => trim((string) ($validated['suggested_option_b'] ?? '')),
                'option_c' => trim((string) ($validated['suggested_option_c'] ?? '')),
                'option_d' => trim((string) ($validated['suggested_option_d'] ?? '')),
                'option_e' => trim((string) ($validated['suggested_option_e'] ?? '')),
                'explanation_text' => trim((string) ($validated['suggested_explanation_text'] ?? '')),
            ]
            : null;

        $report = QuestionReport::query()->create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'category' => $validated['category'],
            'note' => $validated['note'] ?? null,
            'suggested_correct_option' => $validated['suggested_correct_option'] ?? null,
            'suggested_subject_id' => $suggestedSubjectId,
            'suggested_payload_json' => $suggestedPayload,
            'status' => 'pending',
        ]);

        $this->auditLog->record(
            $user,
            'question.reported',
            'question_reports',
            $report->id,
            [],
            [
                'question_id' => $question->id,
                'category' => $validated['category'],
                'note' => $validated['note'] ?? null,
                'suggested_correct_option' => $validated['suggested_correct_option'] ?? null,
                'suggested_subject_id' => $suggestedSubjectId,
                'suggested_subject_name' => $suggestedSubjectId ? Subject::query()->find($suggestedSubjectId)?->name : null,
                'suggested_payload_json' => $suggestedPayload,
            ],
            "Soru itiraz edildi. Kategori: {$validated['category']}",
            $request
        );

        return [
            'duplicate' => false,
            'report' => $report,
        ];
    }

    private function myReportsQuery(Request $request)
    {
        return QuestionReport::query()
            ->with(['question:id,subject_id,question_text,correct_option', 'question.subject:id,name', 'reviewedBy:id,name', 'suggestedSubject:id,name'])
            ->where('user_id', $request->user()->id)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->latest('created_at');
    }

    private function statusOptions(): array
    {
        return [
            'pending' => 'Beklemede',
            'approved' => 'Kabul edildi',
            'rejected' => 'Reddedildi',
            'resolved' => 'Cozuldu',
        ];
    }
}
