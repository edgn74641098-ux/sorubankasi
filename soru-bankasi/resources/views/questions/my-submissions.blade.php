<x-app-layout>
    <x-slot name="header">
        <h1 class="sb-page-title">Onerilerim</h1>
    </x-slot>

    @php
        $statusMeta = [
            'pending' => ['Beklemede', 'warning', 'sb-dashboard-card--gold', 'bi-hourglass-split'],
            'approved' => ['Onaylandi', 'success', 'sb-dashboard-card--green', 'bi-check2-circle'],
            'rejected' => ['Reddedildi', 'danger', 'sb-dashboard-card--red', 'bi-x-circle'],
            'admin_review' => ['Admin incelemesi', 'info', 'sb-dashboard-card--blue', 'bi-search'],
        ];

        $currentRows = $submissions->getCollection();
        $approvedCount = $currentRows->where('status', 'approved')->count();
        $pendingCount = $currentRows->where('status', 'pending')->count();
    @endphp

    <div class="container sb-page">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card sb-dashboard-card sb-dashboard-card--brand">
                    <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px;">
                                <i class="bi bi-lightbulb fs-3"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Soru katkilarin</div>
                                <h2 class="h4 fw-bold mb-2">Onerilerini buradan takip et</h2>
                                <p class="text-muted mb-0">Gonderdigin sorularin inceleme durumunu, sonucu ve moderator notlarini tek ekranda gor.</p>
                            </div>
                        </div>
                        <a href="{{ route('questions.create') }}" class="btn btn-primary flex-shrink-0">
                            <i class="bi bi-plus-circle me-1"></i> Yeni Soru Oner
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--gold">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted small">Toplam Oneri</div>
                            <div class="display-6 fw-bold">{{ $submissions->total() }}</div>
                        </div>
                        <div class="rounded bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px;">
                            <i class="bi bi-stars fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--green">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted small">Bu Sayfada Onaylanan</div>
                            <div class="h2 fw-bold mb-0">{{ $approvedCount }}</div>
                        </div>
                        <i class="bi bi-check2-circle fs-2 text-success"></i>
                    </div>
                </div>
            </div>
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
        </div>

        <div class="card mt-4 sb-dashboard-card sb-dashboard-card--neutral">
            <div class="card-header bg-white d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <div>
                    <h2 class="h5 fw-bold mb-1"><i class="bi bi-journal-check me-2 text-primary"></i>Soru Onerilerim</h2>
                    <div class="text-muted small">Son gonderimler ve inceleme sonuclari.</div>
                </div>
            </div>
            <div class="card-body">
                @if($submissions->isEmpty())
                    <div class="sb-stat-card d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                            <div class="h5 fw-bold mb-1">Henuz soru onermediniz</div>
                            <div class="text-muted">Ilk sorunuzu gondererek havuza katkida bulunabilirsiniz.</div>
                        </div>
                        <a href="{{ route('questions.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Yeni soru oner
                        </a>
                    </div>
                @else
                    <div class="vstack gap-3">
                        @foreach($submissions as $submission)
                            @php
                                [$statusLabel, $statusClass, $cardClass, $statusIcon] = $statusMeta[$submission->status] ?? ['Bilinmiyor', 'secondary', 'sb-dashboard-card--neutral', 'bi-question-circle'];
                            @endphp

                            <div class="card sb-dashboard-card sb-submission-item {{ $cardClass }}">
                                <div class="card-body">
                                    <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3 sb-submission-item__row">
                                        <div class="d-flex align-items-start gap-3 sb-submission-item__main">
                                            <div class="rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 46px; height: 46px;">
                                                <i class="bi {{ $statusIcon }} fs-4"></i>
                                            </div>
                                            <div class="sb-submission-item__content">
                                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2 sb-submission-item__badges">
                                                    <span class="badge text-bg-secondary">{{ $submission->subject?->name ?? '-' }}</span>
                                                    <span class="badge text-bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                                                    <span class="text-muted small">{{ $submission->created_at->format('d.m.Y H:i') }}</span>
                                                </div>
                                                <div class="fw-bold mb-2 sb-submission-item__question">{{ \Illuminate\Support\Str::limit($submission->payload_json['question_text'] ?? '-', 120) }}</div>
                                                @if($submission->review_note)
                                                    <div class="text-muted small sb-submission-item__note">Moderator notu: {{ \Illuminate\Support\Str::limit($submission->review_note, 120) }}</div>
                                                @else
                                                    <div class="text-muted small sb-submission-item__note">Detaylarda siklari, aciklamayi ve inceleme durumunu gorebilirsiniz.</div>
                                                @endif
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" data-bs-toggle="modal" data-bs-target="#detailModal{{ $submission->id }}">
                                            <i class="bi bi-search me-1"></i> Detay
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="detailModal{{ $submission->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h2 class="modal-title h5">Soru Detaylari</h2>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <div class="text-muted small text-uppercase fw-semibold">Ders</div>
                                                <div class="fw-semibold">{{ $submission->subject?->name ?? '-' }}</div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="text-muted small text-uppercase fw-semibold">Soru</div>
                                                <div class="sb-stat-card">{{ $submission->payload_json['question_text'] ?? '-' }}</div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="text-muted small text-uppercase fw-semibold mb-2">Siklar</div>
                                                <div class="vstack gap-2">
                                                    @foreach(['A', 'B', 'C', 'D', 'E'] as $option)
                                                        <div class="border rounded p-3 d-flex justify-content-between gap-3 flex-wrap">
                                                            <div><strong>{{ $option }}.</strong> {{ $submission->payload_json['options'][$option] ?? '-' }}</div>
                                                            @if(($submission->payload_json['correct_option'] ?? null) === $option)
                                                                <span class="badge text-bg-success align-self-start">Dogru cevap</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="text-muted small text-uppercase fw-semibold">Aciklama</div>
                                                <div class="border rounded p-3">{{ $submission->payload_json['explanation_text'] ?? '-' }}</div>
                                            </div>

                                            @if($submission->status === 'rejected' && $submission->review_note)
                                                <div class="alert alert-danger mb-3">
                                                    <strong>Ret sebebi:</strong> {{ $submission->review_note }}
                                                </div>
                                            @elseif($submission->review_note)
                                                <div class="alert alert-secondary mb-3">{{ $submission->review_note }}</div>
                                            @endif

                                            <div class="d-flex flex-wrap gap-2">
                                                <span class="badge text-bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                                                @if($submission->reviewed_at)
                                                    <span class="badge text-bg-light text-dark border">Inceleme: {{ $submission->reviewed_at->format('d.m.Y H:i') }}</span>
                                                @endif
                                            </div>
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
                        {{ $submissions->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
