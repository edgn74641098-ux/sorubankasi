@extends('layouts.admin', ['pageTitle' => 'Soru Yonetimi', 'title' => 'Soru Yonetimi'])

@push('scripts')
    <script src="{{ asset('js/admin-questions-index.js') }}" defer></script>
@endpush

@section('content')
    @php
        $sort = $filters['sort'] ?? 'created_at';
        $direction = strtolower($filters['direction'] ?? 'desc');
        $sortLink = function (string $column) use ($filters, $sort, $direction) {
            $nextDirection = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';

            return route('admin.questions.index', [
                'subject_id' => $filters['subject_id'] ?? null,
                'status' => $filters['status'] ?? null,
                'search' => $filters['search'] ?? null,
                'sort' => $column,
                'direction' => $nextDirection,
            ]);
        };
        $sortArrow = function (string $column) use ($sort, $direction) {
            if ($sort !== $column) {
                return 'bi-arrow-down-up text-muted';
            }

            return $direction === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up';
        };
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Sorular</h1>
            <p class="text-muted mb-0">Soru havuzunu filtreleyin ve yonetin. Arsivlenen sorular ayri Arsiv bolumunde izlenir.</p>
        </div>
        <a href="{{ route('admin.questions.create') }}" class="btn btn-primary">Yeni Soru Ekle</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="GET" action="{{ route('admin.questions.index') }}" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label for="subject_id" class="form-label">Ders</label>
                    <select name="subject_id" id="subject_id" class="form-select">
                        <option value="">Tum Dersler</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected((string) $filters['subject_id'] === (string) $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Durum</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Tumu</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Soru Metni Ara</label>
                    <input type="text" id="search" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Anahtar kelime girin">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-outline-primary">Ara</button>
                    <a href="{{ route('admin.questions.index') }}" class="btn btn-outline-secondary">Temizle</a>
                </div>
            </form>

            @if($questions->isEmpty())
                <div class="alert alert-info mb-0">Filtrelere uygun soru bulunamadi.</div>
            @else
                <form id="bulkArchiveForm" method="POST" action="{{ route('admin.questions.archive-bulk') }}" class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    @csrf
                    <div class="text-muted small">Secili sorulari tek seferde arsive tasiyabilirsiniz.</div>
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        @if(auth()->user()->isAdmin())
                            <button type="submit" form="bulkActivateForm" name="scope" value="selected" class="btn btn-outline-success btn-sm" data-confirm="Secili pasif sorulari aktif hale getirmek istediginize emin misiniz?">
                                Secili Pasifleri Aktif Yap
                            </button>
                        @endif
                        <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="Secili sorulari arsive tasimak istediginize emin misiniz?">
                            Secilileri Arsive Tasi
                        </button>
                    </div>
                </form>
                @if(auth()->user()->isAdmin())
                    <form id="bulkActivateForm" method="POST" action="{{ route('admin.questions.activate-bulk') }}">
                        @csrf
                        <input type="hidden" name="subject_id" value="{{ $filters['subject_id'] }}">
                        <input type="hidden" name="status" value="{{ $filters['status'] }}">
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                    </form>
                    @if($filters['status'] === 'inactive')
                        <div class="alert alert-warning d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                            <div>
                                Bu filtrede {{ $questions->total() }} pasif soru var. Pasif sorular kullanici ders havuzunda ve aramada aktif soru olarak gorunmez.
                            </div>
                            <button type="submit" form="bulkActivateForm" name="scope" value="filter" class="btn btn-success btn-sm" data-confirm="Bu filtredeki tum pasif sorulari aktif hale getirmek istediginize emin misiniz?">
                                Filtredeki Tum Pasifleri Aktif Yap
                            </button>
                        </div>
                    @endif
                @endif

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th class="sb-width-48">
                                    <input type="checkbox" class="form-check-input js-question-select-all">
                                </th>
                                <th>
                                    <a href="{{ $sortLink('subject') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                        <span>Ders</span><i class="bi {{ $sortArrow('subject') }}"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortLink('question_text') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                        <span>Soru</span><i class="bi {{ $sortArrow('question_text') }}"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortLink('difficulty_score') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                        <span>Zorluk</span><i class="bi {{ $sortArrow('difficulty_score') }}"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortLink('status') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                        <span>Durum</span><i class="bi {{ $sortArrow('status') }}"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortLink('current_version') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                        <span>Versiyon</span><i class="bi {{ $sortArrow('current_version') }}"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortLink('created_at') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                        <span>Eklenme Tarihi</span><i class="bi {{ $sortArrow('created_at') }}"></i>
                                    </a>
                                </th>
                                <th>Ekleyen</th>
                                <th class="text-end">Islemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($questions as $question)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input js-question-select" name="question_ids[]" value="{{ $question->id }}" form="bulkArchiveForm">
                                        @if(auth()->user()->isAdmin())
                                            <input type="checkbox" class="form-check-input js-question-activate-select d-none" name="question_ids[]" value="{{ $question->id }}" form="bulkActivateForm">
                                        @endif
                                    </td>
                                    <td>{{ $question->subject?->name }}</td>
                                    <td class="sb-min-340">{{ \Illuminate\Support\Str::limit($question->question_text, 120) }}</td>
                                    <td>{{ number_format((float) $question->difficulty_score, 1) }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $question->status === 'active' ? 'success' : ($question->status === 'draft' ? 'warning' : 'secondary') }}">
                                            {{ $statusOptions[$question->status] ?? $question->status }}
                                        </span>
                                    </td>
                                    <td>v{{ $question->current_version }}</td>
                                    <td>{{ $question->created_at?->format('d.m.Y H:i') }}</td>
                                    <td>{{ $question->createdBy?->name ?? '-' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.questions.edit', $question) }}" class="btn btn-sm btn-outline-primary">Duzenle</a>
                                        <a href="{{ route('admin.questions.versions.index', $question) }}" class="btn btn-sm btn-outline-secondary">Surumler</a>

                                        @can('delete', $question)
                                            <form method="POST" action="{{ route('admin.questions.destroy', $question) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Bu soruyu arsive tasimak istediginize emin misiniz?">Arsive Tasi</button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $questions->links('pagination::bootstrap-5') }}
            @endif
        </div>
    </div>
@endsection
