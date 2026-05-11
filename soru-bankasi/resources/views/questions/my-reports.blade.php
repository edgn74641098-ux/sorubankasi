<x-app-layout>
    <x-slot name="header">
        <h1 class="sb-page-title">Itirazlarim</h1>
    </x-slot>

    @php
        $statusMeta = [
            'pending' => ['Beklemede', 'warning', 'sb-dashboard-card--gold', 'bi-hourglass-split'],
            'approved' => ['Kabul edildi', 'success', 'sb-dashboard-card--green', 'bi-check2-circle'],
            'rejected' => ['Reddedildi', 'danger', 'sb-dashboard-card--red', 'bi-x-circle'],
            'resolved' => ['Cozuldu', 'info', 'sb-dashboard-card--blue', 'bi-patch-check'],
        ];

        $currentRows = $reports->getCollection();
        $pendingCount = $currentRows->where('status', 'pending')->count();
        $approvedCount = $currentRows->where('status', 'approved')->count();
    @endphp

    <div class="container sb-page">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card sb-dashboard-card sb-dashboard-card--brand">
                    <div class="card-body d-flex align-items-start gap-3">
                        <div class="rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px;">
                            <i class="bi bi-chat-square-text fs-3"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Soru itiraz takibi</div>
                            <h2 class="h4 fw-bold mb-2">Bildirdigin itirazlar burada</h2>
                            <p class="text-muted mb-0">Bekleyen, kabul edilen veya reddedilen itirazlari takip et; admin mesajlarini ve karar notlarini incele.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--gold">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted small">Toplam Itiraz</div>
                            <div class="display-6 fw-bold">{{ $reports->total() }}</div>
                        </div>
                        <div class="rounded bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px;">
                            <i class="bi bi-flag fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--blue">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted small">Bu Sayfada Bekleyen</div>
                            <div class="h2 fw-bold mb-0">{{ $pendingCount }}</div>
                        </div>
                        <i class="bi bi-hourglass-split fs-2 text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--green">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted small">Bu Sayfada Kabul</div>
                            <div class="h2 fw-bold mb-0">{{ $approvedCount }}</div>
                        </div>
                        <i class="bi bi-check2-circle fs-2 text-success"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4 sb-dashboard-card sb-dashboard-card--neutral">
            <div class="card-body">
                <form method="GET" action="{{ route('questions.reports') }}" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="status" class="form-label fw-semibold">Durum filtresi</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Tumu</option>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-1"></i> Filtrele
                        </button>
                        <a href="{{ route('questions.reports') }}" class="btn btn-outline-secondary">Temizle</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4 sb-dashboard-card sb-dashboard-card--neutral">
            <div class="card-header bg-white">
                <h2 class="h5 fw-bold mb-1"><i class="bi bi-list-check me-2 text-primary"></i>Itiraz Kayitlari</h2>
                <div class="text-muted small">Sorulara yaptiginiz itirazlar ve inceleme sonuclari.</div>
            </div>
            <div class="card-body">
                @if($reports->isEmpty())
                    <div class="sb-stat-card">
                        <div class="h5 fw-bold mb-1">Henuz itiraz kaydiniz yok</div>
                        <div class="text-muted">Test cozdukten sonra hatali oldugunu dusundugunuz sorular icin itiraz gonderebilirsiniz.</div>
                    </div>
                @else
                    <div class="vstack gap-3">
                        @foreach($reports as $report)
                            @php
                                [$statusLabel, $statusClass, $cardClass, $statusIcon] = $statusMeta[$report->status] ?? [$statusOptions[$report->status] ?? $report->status, 'secondary', 'sb-dashboard-card--neutral', 'bi-question-circle'];
                            @endphp

                            <div class="card sb-dashboard-card sb-report-item {{ $cardClass }}">
                                <div class="card-body">
                                    <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3 sb-report-item__row">
                                        <div class="d-flex align-items-start gap-3 sb-report-item__main">
                                            <div class="rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 46px; height: 46px;">
                                                <i class="bi {{ $statusIcon }} fs-4"></i>
                                            </div>
                                            <div class="sb-report-item__content">
                                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2 sb-report-item__badges">
                                                    <span class="badge text-bg-secondary">{{ $report->question->subject?->name ?? '-' }}</span>
                                                    <span class="badge text-bg-{{ $statusClass }}">{{ $statusOptions[$report->status] ?? $statusLabel }}</span>
                                                    @if($report->suggested_correct_option)
                                                        <span class="badge text-bg-primary">Oneriniz: {{ $report->suggested_correct_option }}</span>
                                                    @endif
                                                    @if($report->suggested_subject_id)
                                                        <span class="badge text-bg-info">Ders Oneriniz: {{ $report->suggestedSubject?->name ?? ('#' . $report->suggested_subject_id) }}</span>
                                                    @endif
                                                    <span class="text-muted small">{{ $report->created_at->format('d.m.Y H:i') }}</span>
                                                </div>
                                                <div class="fw-bold mb-2 sb-report-item__question">{{ \Illuminate\Support\Str::limit($report->question->question_text, 120) }}</div>
                                                @if($report->user_message)
                                                    <div class="text-success small fw-semibold sb-report-item__note">{{ \Illuminate\Support\Str::limit($report->user_message, 140) }}</div>
                                                @elseif($report->review_note)
                                                    <div class="text-muted small sb-report-item__note">{{ \Illuminate\Support\Str::limit($report->review_note, 140) }}</div>
                                                @else
                                                    <div class="text-muted small sb-report-item__note">Itiraziniz henuz incelenmedi.</div>
                                                @endif
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" data-bs-toggle="modal" data-bs-target="#reportDetail{{ $report->id }}">
                                            <i class="bi bi-search me-1"></i> Detay
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="reportDetail{{ $report->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h2 class="modal-title h5">Itiraz Detayi</h2>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <div class="text-muted small text-uppercase fw-semibold">Soru</div>
                                                <div class="sb-stat-card">{{ $report->question->question_text }}</div>
                                            </div>

                                            <div class="row g-3 mb-3">
                                                <div class="col-md-4">
                                                    <div class="card sb-dashboard-card sb-dashboard-card--neutral h-100">
                                                        <div class="card-body">
                                                            <div class="text-muted small">Kategori</div>
                                                            <strong>{{ $report->category_label }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card sb-dashboard-card sb-dashboard-card--blue h-100">
                                                        <div class="card-body">
                                                            <div class="text-muted small">Sizin oneriniz</div>
                                                            <strong>{{ $report->suggested_correct_option ?? '-' }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card sb-dashboard-card sb-dashboard-card--gold h-100">
                                                        <div class="card-body">
                                                            <div class="text-muted small">Sizin ders oneriniz</div>
                                                            <strong>{{ $report->suggestedSubject?->name ?? '-' }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card sb-dashboard-card sb-dashboard-card--green h-100">
                                                        <div class="card-body">
                                                            <div class="text-muted small">Guncel dogru cevap</div>
                                                            <strong>{{ $report->question->correct_option }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="text-muted small text-uppercase fw-semibold">Notunuz</div>
                                                <div class="border rounded p-3">{{ $report->note ?: 'Not eklenmedi.' }}</div>
                                            </div>

                                            @if($report->user_message)
                                                <div class="alert alert-success">{{ $report->user_message }}</div>
                                            @elseif($report->review_note)
                                                <div class="alert alert-secondary">{{ $report->review_note }}</div>
                                            @else
                                                <div class="alert alert-warning mb-0">Itiraziniz henuz incelenmedi.</div>
                                            @endif

                                            @if($report->reviewed_at)
                                                <div class="text-muted small">Inceleme tarihi: {{ $report->reviewed_at->format('d.m.Y H:i') }}</div>
                                            @endif
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $reports->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
