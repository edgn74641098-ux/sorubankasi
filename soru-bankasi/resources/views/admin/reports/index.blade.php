@extends('layouts.admin', ['pageTitle' => 'Soru Itirazlari', 'title' => 'Soru Itirazlari'])

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Itirazlar</h1>
            <p class="text-muted mb-0">Kullanicilarin test ekranindan bildirdigi soru itirazlarini inceleyin.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label for="status" class="form-label">Durum</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Tumu</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">Kategori</label>
                    <select name="category" id="category" class="form-select">
                        <option value="">Tumu</option>
                        @foreach($categoryOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['category'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-outline-primary">Filtrele</button>
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">Temizle</a>
                </div>
            </form>

            @if($reports->isEmpty())
                <div class="alert alert-info mb-0">Filtrelere uygun itiraz bulunamadi.</div>
            @else
                <div class="vstack gap-3">
                    @foreach($reports as $report)
                        <div class="card border">
                            <div class="card-header bg-light">
                                <div class="d-flex flex-column flex-lg-row justify-content-between gap-2">
                                    <div>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="fw-semibold">{{ $report->question->subject?->name ?? 'Ders yok' }}</span>
                                            <span class="badge text-bg-warning">{{ $report->category_label }}</span>
                                            <span class="badge text-bg-info">Oneri: {{ $report->suggested_correct_option ?? '-' }}</span>
                                            <span class="badge text-bg-{{ $report->status === 'pending' ? 'secondary' : ($report->status === 'approved' ? 'success' : 'danger') }}">
                                                {{ $statusOptions[$report->status] ?? $report->status }}
                                            </span>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            Bildiren: {{ $report->user->name }} ({{ $report->user->email }}) · {{ $report->created_at->format('d.m.Y H:i') }}
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <a href="{{ route('admin.questions.edit', $report->question) }}" class="btn btn-sm btn-outline-primary">Soruyu Ac</a>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="text-muted small text-uppercase fw-semibold mb-1">Soru</div>
                                    <div class="bg-light rounded p-3">{{ $report->question->question_text }}</div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <div class="text-muted small text-uppercase fw-semibold mb-1">Kullanici Notu</div>
                                        <div class="border rounded p-3 h-100">{{ $report->note ?: 'Not eklenmedi.' }}</div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="text-muted small text-uppercase fw-semibold mb-1">Soru Bilgisi</div>
                                        <div class="border rounded p-3 h-100">
                                            <div>Dogru cevap: <strong>{{ $report->question->correct_option }}</strong></div>
                                            <div>Onerilen cevap: <strong>{{ $report->suggested_correct_option ?? '-' }}</strong></div>
                                            <div>Zorluk: <strong>{{ number_format((float) $report->question->difficulty_score, 1) }}</strong></div>
                                            <div>Durum: <strong>{{ $report->question->status }}</strong></div>
                                        </div>
                                    </div>
                                </div>

                                @if($report->status === 'pending')
                                    <div class="row g-3 mt-3">
                                        <div class="col-lg-6">
                                            <form method="POST" action="{{ route('admin.reports.approve', $report) }}" class="border rounded p-3">
                                                @csrf
                                                <label for="approve_note_{{ $report->id }}" class="form-label">Onay notu</label>
                                                <textarea id="approve_note_{{ $report->id }}" name="review_note" rows="2" maxlength="500" class="form-control mb-3"></textarea>
                                                <button type="submit" class="btn btn-success w-100">Itirazi Onayla</button>
                                            </form>
                                        </div>
                                        <div class="col-lg-6">
                                            <form method="POST" action="{{ route('admin.reports.reject', $report) }}" class="border rounded p-3">
                                                @csrf
                                                <label for="reject_note_{{ $report->id }}" class="form-label">Ret sebebi</label>
                                                <textarea id="reject_note_{{ $report->id }}" name="review_note" rows="2" maxlength="500" class="form-control mb-3" required></textarea>
                                                <button type="submit" class="btn btn-outline-danger w-100">Itirazi Reddet</button>
                                            </form>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-secondary mt-3 mb-0">
                                        Inceleyen: {{ $report->reviewedBy?->name ?? 'Bilinmiyor' }} · {{ $report->reviewed_at?->format('d.m.Y H:i') ?? '-' }}
                                        @if($report->review_note)
                                            <div class="mt-1">{{ $report->review_note }}</div>
                                        @endif
                                        @if($report->user_message)
                                            <div class="mt-2"><strong>Kullanici mesaji:</strong> {{ $report->user_message }}</div>
                                        @endif
                                    </div>
                                @endif
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
@endsection
