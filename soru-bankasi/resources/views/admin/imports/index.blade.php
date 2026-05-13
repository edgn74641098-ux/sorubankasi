@extends('layouts.admin', ['pageTitle' => 'Soru Import', 'title' => 'Soru Import'])

@section('content')
    @php
        $statusMeta = [
            'preview' => ['Bekliyor', 'warning', 'Onay bekleyen onizleme'],
            'completed' => ['Onaylandi', 'success', 'Sorulara uygulandi'],
            'processing' => ['Isleniyor', 'info', 'Import isleniyor'],
            'failed' => ['Hatali', 'danger', 'Import tamamlanamadi'],
        ];
    @endphp

    <div class="admin-search-panel mb-4">
        <div class="admin-search-panel__head">
            <div>
                <h1>Soru Import</h1>
                <p class="text-muted mb-0">CSV dosyasini yukleyin, onizleyin, sonra onaylayip soru havuzuna uygulayin.</p>
            </div>
            <a href="{{ route('admin.imports.template.download') }}" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-arrow-down me-1"></i> Sablon Indir
            </a>
        </div>

        <form method="POST" action="{{ route('admin.imports.store') }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            <div class="col-lg-8">
                <label for="file" class="form-label">CSV dosyasi</label>
                <input id="file" name="file" type="file" class="form-control @error('file') is-invalid @enderror" accept=".csv" required>
                @error('file')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-lg-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-upload me-1"></i> Onizleme Olustur
                </button>
            </div>
        </form>

        <div class="small text-muted mt-3">
            <strong>Beklenen kolonlar:</strong>
            <code>subject,question_text,option_a,option_b,option_c,option_d,option_e,correct_option,explanation_text</code>
            <br>
            <strong>Limit:</strong> Maksimum 20 MB, 1000 satir, UTF-8 CSV.
        </div>
    </div>

    <div class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h2>Gecmis Importlar</h2>
                <p>Bekleyen importlar sari, onaylanip uygulananlar yesil gorunur.</p>
            </div>
        </div>

        <div class="admin-feed">
            @forelse($batches as $batch)
                @php
                    [$statusLabel, $statusClass, $statusHint] = $statusMeta[$batch->status] ?? [$batch->status, 'secondary', 'Durum bilinmiyor'];
                    $appliedRows = (int) $batch->inserted_rows_count + (int) $batch->merged_rows_count;
                @endphp

                <div class="admin-import-row">
                    <div class="admin-import-row__main">
                        <div class="admin-import-file">
                            <span class="admin-import-file__icon"><i class="bi bi-filetype-csv"></i></span>
                            <div>
                                <strong>#{{ $batch->id }} · {{ $batch->file_name }}</strong>
                                <p>{{ $statusHint }} · {{ $batch->created_at?->format('d.m.Y H:i') }}</p>
                            </div>
                        </div>

                        <div class="admin-import-metrics">
                            <span><strong>{{ number_format($batch->total_rows) }}</strong> toplam</span>
                            <span><strong>{{ number_format($batch->success_rows) }}</strong> basarili</span>
                            <span><strong>{{ number_format($batch->failed_rows) }}</strong> hatali</span>
                            <span><strong>{{ number_format($appliedRows) }}</strong> uygulanan</span>
                            <span><strong>{{ number_format($batch->pending_rows_count) }}</strong> bekleyen satir</span>
                        </div>
                    </div>

                    <div class="admin-import-row__side">
                        <span class="badge text-bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                        <span class="text-muted small">{{ $batch->uploadedBy?->name ?? '-' }}</span>
                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            <a href="{{ route('admin.imports.show', $batch) }}" class="btn btn-sm btn-outline-primary">
                                Detay
                            </a>
                            <form method="POST" action="{{ route('admin.imports.destroy', $batch) }}" data-confirm="Bu import kaydini silmek istediginize emin misiniz? Uygulanmis sorular silinmez.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    Sil
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="admin-empty-state">Henuz import yok.</p>
            @endforelse
        </div>

        <div class="px-4 pb-4">
            {{ $batches->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
