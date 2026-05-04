@extends('layouts.admin', ['pageTitle' => 'Soru Onerileri', 'title' => 'Soru Onerileri'])

@section('content')
    <section class="admin-command-hero">
        <div>
            <p class="eyebrow">Moderasyon Kuyrugu</p>
            <h1>Soru onerileri</h1>
            <p>Kullanicilarin gonderdigi sorulari, cevap anahtarini ve aciklamayi tek akista inceleyin.</p>
        </div>
        <div class="admin-command-score">
            <span>Bekleyen Oneri</span>
            <strong>{{ number_format($submissions->total()) }}</strong>
            <small>onay veya ret bekliyor</small>
        </div>
    </section>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <section class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h2>Bekleyen Oneriler</h2>
                <p>Soru metni, siklar ve kullanici bilgisini kontrol ederek yayina alin.</p>
            </div>
        </div>

        <div class="admin-panel__content">
            @if ($submissions->isEmpty())
                <div class="alert alert-info mb-0">Incelenecek soru onerisi bulunmuyor.</div>
            @else
                <div class="vstack gap-3">
                    @foreach ($submissions as $submission)
                        @php
                            $payload = $submission->payload_json;
                            $correctOption = $payload['correct_option'] ?? null;
                        @endphp

                        <article class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                                    <div>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="badge text-bg-primary">{{ $submission->subject?->name ?? 'Ders yok' }}</span>
                                            <span class="badge text-bg-warning">Beklemede</span>
                                            <span class="badge text-bg-success">Dogru: {{ $correctOption ?? '-' }}</span>
                                        </div>
                                        <div class="small text-muted mt-2">
                                            Gonderen: <strong>{{ $submission->user?->name }}</strong>
                                            ({{ $submission->user?->email }}) · {{ $submission->created_at->format('d.m.Y H:i') }}
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-start gap-2">
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $submission->id }}">
                                            <i class="bi bi-check-circle me-1"></i> Onayla
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $submission->id }}">
                                            <i class="bi bi-x-circle me-1"></i> Reddet
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="text-muted small text-uppercase fw-semibold mb-2">Soru Metni</div>
                                    <div class="bg-light rounded p-3 fw-semibold">{{ $payload['question_text'] ?? '-' }}</div>
                                </div>

                                <div class="mb-4">
                                    <div class="text-muted small text-uppercase fw-semibold mb-2">Cevap Sıkları</div>
                                    <div class="row g-2">
                                        @foreach (['A', 'B', 'C', 'D', 'E'] as $option)
                                            @php($isCorrect = $correctOption === $option)
                                            <div class="col-lg-6">
                                                <div class="border rounded p-2 small {{ $isCorrect ? 'bg-success-subtle border-success text-success-emphasis' : 'bg-white' }}">
                                                    <span class="fw-bold">{{ $option }}.</span>
                                                    {{ $payload['options'][$option] ?? '-' }}
                                                    @if($isCorrect)
                                                        <span class="badge text-bg-success ms-2">Dogru cevap</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <div class="text-muted small text-uppercase fw-semibold mb-2">Aciklama</div>
                                    <div class="border rounded p-3 bg-white">{{ $payload['explanation_text'] ?? '-' }}</div>
                                </div>
                            </div>
                        </article>

                        <div class="modal fade" id="approveModal{{ $submission->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('admin.submissions.approve', $submission) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h2 class="modal-title h5">Soruyu Onayla</h2>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-success">
                                                Onaylanirsa soru aktif havuza eklenir ve kullaniciya <strong>+{{ number_format($approvalReward) }} puan</strong> verilir.
                                            </div>
                                            <label for="approve_note_{{ $submission->id }}" class="form-label">Onay notu</label>
                                            <textarea id="approve_note_{{ $submission->id }}" name="review_note" rows="3" maxlength="500" class="form-control"></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Vazgec</button>
                                            <button type="submit" class="btn btn-success">Onayla</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="rejectModal{{ $submission->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('admin.submissions.reject', $submission) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h2 class="modal-title h5">Soruyu Reddet</h2>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-warning">
                                                Ret sebebi kullanici tarafindan gorulecegi icin net ve yapici yazin.
                                            </div>
                                            <label for="reject_note_{{ $submission->id }}" class="form-label">Ret sebebi</label>
                                            <textarea id="reject_note_{{ $submission->id }}" name="review_note" rows="3" maxlength="500" class="form-control @error('review_note') is-invalid @enderror" @if($rejectionNoteRequired) required @endif></textarea>
                                            @unless($rejectionNoteRequired)
                                                <div class="form-text">Bu sistem ayarlarinda ret notu opsiyonel.</div>
                                            @endunless
                                            @error('review_note')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Vazgec</button>
                                            <button type="submit" class="btn btn-outline-danger">Reddet</button>
                                        </div>
                                    </form>
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
    </section>
@endsection
