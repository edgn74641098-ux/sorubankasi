<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionImportBatch;
use App\Services\AuditLogService;
use App\Services\QuestionImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class QuestionImportController extends Controller
{
    public function __construct(
        private readonly QuestionImportService $importService,
        private readonly AuditLogService $auditLog
    ) {
    }

    public function index(): View
    {
        $batches = QuestionImportBatch::query()
            ->with('uploadedBy:id,name')
            ->withCount([
                'rows',
                'errors',
                'rows as pending_rows_count' => fn ($query) => $query->where('action', 'pending'),
                'rows as inserted_rows_count' => fn ($query) => $query->where('action', 'inserted'),
                'rows as merged_rows_count' => fn ($query) => $query->where('action', 'merged'),
                'rows as skipped_rows_count' => fn ($query) => $query->where('action', 'skipped'),
                'rows as manual_review_rows_count' => fn ($query) => $query->where('action', 'manual_review'),
            ])
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

    public function destroy(Request $request, QuestionImportBatch $import): RedirectResponse
    {
        $payload = [
            'file_name' => $import->file_name,
            'status' => $import->status,
            'total_rows' => $import->total_rows,
            'success_rows' => $import->success_rows,
            'failed_rows' => $import->failed_rows,
        ];

        Cache::forget('import.preview.'.$import->id);

        $this->auditLog->record(
            $request->user(),
            'import.deleted',
            'question_import_batches',
            $import->id,
            $payload,
            null,
            "Import kaydi silindi: {$import->file_name}",
            $request
        );

        $import->delete();

        return redirect()
            ->route('admin.imports.index')
            ->with('success', 'Import kaydi silindi. Uygulanmis sorular silinmedi.');
    }

    public function downloadTemplate()
    {
        $headers = ['subject', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'correct_option', 'explanation_text'];
        
        // Örnek veriler
        $examples = [
            ['Matematik', 'İntegral tanımı ne göz önüne alınarak yazılır?', 'Riemann toplamı', 'Türev', 'Limit', 'Logaritma', 'Dışbükeylik', 'A', 'İntegral, bir fonksiyonun belirli bir aralıktaki alanını hesaplamak için Riemann toplamı kavramı kullanılır.'],
            ['Fizik', 'Newton\'un birinci hareket yasası neyi ifade eder?', 'Eylemsizlik yasası', 'Momentum yasası', 'Enerji korunumu', 'Kuvvet tanımı', 'Çalışma-enerji teoremi', 'A', 'Newton\'un birinci yasası (eylemsizlik yasası), dış kuvvet uygulanmadığında cisimlerin hareket halini koruduğunu ifade eder.'],
            ['Kimya', 'pH değeri 7 olan bir çözeltinin özellikleri nedir?', 'Nötr çözelti', 'Asidik çözeltι', 'Bazik çözeltι', 'Tampon çözeltι', 'Koloid çözeltι', 'A', 'pH değeri 7 olan çözeltiler nötr çözeltilerdir ve H⁺ ile OH⁻ iyon konsantrasyonları eşittir.'],
        ];

        // CSV dosyası oluştur
        $filename = 'soru-import-template-' . date('Y-m-d-His') . '.csv';
        $file = tmpfile();
        
        // UTF-8 BOM ekle (Excel türkçe karakterleri doğru görsün diye)
        fwrite($file, "\xEF\xBB\xBF");
        
        // Headers yazılsın
        fputcsv($file, $headers);
        
        // Örnek veriler yazılsın
        foreach ($examples as $row) {
            fputcsv($file, $row);
        }
        
        rewind($file);
        $content = stream_get_contents($file);
        fclose($file);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
