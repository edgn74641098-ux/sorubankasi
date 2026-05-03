@extends('layouts.admin', ['pageTitle' => 'Soru Moderasyon', 'title' => 'Soru Moderasyon'])

@section('content')
<div class="container-fluid py-5">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-clipboard-check"></i> Soru Moderasyon Paneli
                    </h3>
                    <small>Bekleyen kullanıcı soru önerilerini inceleyin ve onaylayın/reddedin</small>
                </div>

                <div class="card-body">
                    @if ($submissions->count() === 0)
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle"></i>
                            Incelenecek soru bulunmamaktadır.
                        </div>
                    @else
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <p class="text-muted">
                                    <strong>Toplam Bekleyen Sorular:</strong> {{ $submissions->total() }}
                                </p>
                            </div>
                        </div>

                        @foreach ($submissions as $submission)
                            <div class="card mb-4 sb-review-card">
                                <div class="card-header bg-light">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h5 class="mb-0">
                                                {{ $submission->subject->name }}
                                                <span class="badge bg-warning text-dark ms-2">
                                                    Beklemede
                                                </span>
                                            </h5>
                                            <small class="text-muted">
                                                Gönderen: <strong>{{ $submission->user->name }}</strong> 
                                                ({{ $submission->user->email }})
                                            </small>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <small class="text-muted">
                                                {{ $submission->created_at->format('d.m.Y H:i') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body">
                                    {{-- Question Text --}}
                                    <div class="mb-4">
                                        <h6 class="text-uppercase text-muted">
                                            <i class="bi bi-question-circle"></i> Soru Metni
                                        </h6>
                                        <div class="bg-light p-3 rounded">
                                            <p class="mb-0">{{ $submission->payload_json['question_text'] }}</p>
                                        </div>
                                    </div>

                                    {{-- Options --}}
                                    <div class="mb-4">
                                        <h6 class="text-uppercase text-muted">
                                            <i class="bi bi-list-check"></i> Cevap Şıkları
                                        </h6>
                                        <div class="row">
                                            @foreach (['A', 'B', 'C', 'D', 'E'] as $option)
                                                <div class="col-md-6 mb-2">
                                                    <div class="p-2 border rounded">
                                                        <strong class="text-primary">{{ $option }}:</strong>
                                                        {{ $submission->payload_json['options'][$option] }}
                                                        @if ($submission->payload_json['correct_option'] === $option)
                                                            <span class="badge bg-success float-end">
                                                                ✓ Doğru Cevap
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Explanation --}}
                                    <div class="mb-4">
                                        <h6 class="text-uppercase text-muted">
                                            <i class="bi bi-lightbulb"></i> Açıklama
                                        </h6>
                                        <div class="bg-light p-3 rounded">
                                            <p class="mb-0">{{ $submission->payload_json['explanation_text'] }}</p>
                                        </div>
                                    </div>

                                    {{-- Action Buttons --}}
                                    <div class="mt-4">
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <button class="btn btn-success btn-block w-100" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#approveModal{{ $submission->id }}">
                                                    <i class="bi bi-check-circle"></i> Onayla
                                                </button>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <button class="btn btn-danger btn-block w-100" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#rejectModal{{ $submission->id }}">
                                                    <i class="bi bi-x-circle"></i> Reddet
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Approve Modal --}}
                            <div class="modal fade" id="approveModal{{ $submission->id }}" 
                                 tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Soruyu Onayla</h5>
                                            <button type="button" class="btn-close" 
                                                    data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.submissions.approve', $submission) }}" 
                                              method="POST">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="alert alert-success" role="alert">
                                                    <i class="bi bi-info-circle"></i>
                                                    Bu soru onaylanırsa, kullanıcıya <strong>+10 puan</strong> 
                                                    verilecektir.
                                                </div>

                                                <div class="mb-3">
                                                    <label for="review_note{{ $submission->id }}" 
                                                           class="form-label">
                                                        Notlar (Opsiyonel)
                                                    </label>
                                                    <textarea class="form-control" 
                                                              id="review_note{{ $submission->id }}" 
                                                              name="review_note" rows="3" 
                                                              placeholder="Onaylama hakkında not ekleyin (örn: Açıklama iyileştirilebilir ama kabul edilebilir)">
                                                    </textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" 
                                                        data-bs-dismiss="modal">
                                                    İptal
                                                </button>
                                                <button type="submit" class="btn btn-success">
                                                    <i class="bi bi-check-circle"></i> Onayla
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- Reject Modal --}}
                            <div class="modal fade" id="rejectModal{{ $submission->id }}" 
                                 tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Soruyu Reddet</h5>
                                            <button type="button" class="btn-close" 
                                                    data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.submissions.reject', $submission) }}" 
                                              method="POST">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="alert alert-danger" role="alert">
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                    <strong>Uyarı:</strong> Reddetme sebebini açıkça yazmalısınız. 
                                                    Kullanıcı bu notları görecektir.
                                                </div>

                                                <div class="mb-3">
                                                    <label for="review_note{{ $submission->id }}_reject" 
                                                           class="form-label">
                                                        <strong>Ret Sebebi (Zorunlu)</strong>
                                                    </label>
                                                    <textarea class="form-control @error('review_note') is-invalid @enderror" 
                                                              id="review_note{{ $submission->id }}_reject" 
                                                              name="review_note" rows="3" 
                                                              placeholder="Soruyu neden reddetiyorsunuz? (Örn: Yazım hatası var, şıklar açık değil, vb.)"
                                                              required>
                                                    </textarea>
                                                    @error('review_note')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" 
                                                        data-bs-dismiss="modal">
                                                    İptal
                                                </button>
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="bi bi-x-circle"></i> Reddet
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Pagination --}}
                        <nav aria-label="Sayfa Numarası" class="mt-4">
                            {{ $submissions->links() }}
                        </nav>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
