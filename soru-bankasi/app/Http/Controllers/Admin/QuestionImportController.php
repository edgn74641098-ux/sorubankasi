<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionImportBatch;
use App\Services\QuestionImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionImportController extends Controller
{
    public function __construct(private readonly QuestionImportService $importService)
    {
    }

    public function index(): View
    {
        $batches = QuestionImportBatch::query()
            ->with('uploadedBy:id,name')
            ->latest()
            ->paginate(20);

        return view('admin.imports.index', compact('batches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $batch = $this->importService->preview($request->file('file'), $request->user());

        return redirect()
            ->route('admin.imports.show', $batch)
            ->with('success', 'Import onizlemesi olusturuldu.');
    }

    public function show(QuestionImportBatch $import): View
    {
        $import->load([
            'rows.matchedQuestion:id,question_text',
            'errors',
            'uploadedBy:id,name',
        ]);

        return view('admin.imports.show', [
            'batch' => $import,
        ]);
    }

    public function confirm(Request $request, QuestionImportBatch $import): RedirectResponse
    {
        $actions = (array) $request->input('actions', []);
        $result = $this->importService->confirm($import, $actions, $request->user());

        return redirect()
            ->route('admin.imports.show', $import)
            ->with('success', "Import tamamlandi. Inserted: {$result['inserted']}, merged: {$result['merged']}, skipped: {$result['skipped']}, manual_review: {$result['manual_review']}");
    }
}

