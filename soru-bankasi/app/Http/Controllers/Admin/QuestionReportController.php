<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionReport;
use App\Services\QuestionReportReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionReportController extends Controller
{
    public function __construct(
        private readonly QuestionReportReviewService $reviewService
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', QuestionReport::class);

        $status = $request->input('status', 'pending');
        $category = $request->input('category');

        $reports = QuestionReport::query()
            ->with([
                'user:id,name,email',
                'reviewedBy:id,name,email',
                'suggestedSubject:id,name',
                'question:id,subject_id,question_text,correct_option,status,difficulty_score,current_version,option_a,option_b,option_c,option_d,option_e,explanation_text,approved_by,approved_at',
                'question.subject:id,name',
            ])
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', $status))
            ->when($category !== null && $category !== '', fn ($query) => $query->where('category', $category))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'filters' => [
                'status' => $status,
                'category' => $category,
            ],
            'statusOptions' => $this->statusOptions(),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function approve(Request $request, QuestionReport $report): RedirectResponse
    {
        $this->authorize('update', $report);
        $this->ensurePending($report);

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->reviewService->approve($report, $request->user(), $validated['review_note'] ?? null, $request);

        return back()->with('success', 'Itiraz onaylandi. Onerilen dogru cevap varsa soruya uygulandi.');
    }

    public function reject(Request $request, QuestionReport $report): RedirectResponse
    {
        $this->authorize('update', $report);
        $this->ensurePending($report);

        $validated = $request->validate([
            'review_note' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->reviewService->reject($report, $request->user(), $validated['review_note'], $request);

        return back()->with('success', 'Itiraz reddedildi.');
    }

    private function ensurePending(QuestionReport $report): void
    {
        if ($report->status !== 'pending') {
            abort(409, 'Bu itiraz zaten incelenmis.');
        }
    }

    private function statusOptions(): array
    {
        return [
            'pending' => 'Beklemede',
            'approved' => 'Onaylandi',
            'rejected' => 'Reddedildi',
            'resolved' => 'Cozuldu',
        ];
    }

    private function categoryOptions(): array
    {
        return [
            'WRONG_ANSWER' => 'Yanlis cevap',
            'UNCLEAR_WORDING' => 'Ifade belirsiz',
            'TYPO' => 'Yazim hatasi',
            'WRONG_SUBJECT' => 'Yanlis ders',
            'OTHER' => 'Diger',
        ];
    }
}
