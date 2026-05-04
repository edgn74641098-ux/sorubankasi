@extends('layouts.admin')

@section('content')
    @php($pageTitle = 'Arsiv')

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Arsiv</h1>
            <p class="text-muted mb-0">Arsive tasinan dersler ve sorular 7 gunluk otomatik silme takvimiyle burada takip edilir.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h5 mb-0">Arsivlenen Dersler</h2>
                        <div class="small text-muted">Ders silinince bagli sorular da arsive alinir.</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if(! $subjects->isEmpty() && auth()->user()->isAdmin())
                            <form id="bulk-subject-restore-form" method="POST" action="{{ route('admin.archive.subjects.restore-bulk') }}">
                                @csrf
                            </form>
                            <button type="submit" form="bulk-subject-restore-form" class="btn btn-sm btn-outline-success" onclick="return confirm('Secili dersleri geri almak istediginize emin misiniz? Bagli arsiv sorulari pasif duruma alinir.')">Secilenleri Geri Al</button>
                        @endif
                        <span class="badge text-bg-secondary">{{ $subjects->total() }} kayit</span>
                    </div>
                </div>
                <div class="card-body border-bottom">
                    <form method="GET" action="{{ route('admin.archive.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="subject_search" class="form-label">Ders Ara</label>
                            <input type="text" id="subject_search" name="subject_search" class="form-control" value="{{ $filters['subject_search'] }}" placeholder="Ders adi veya slug">
                        </div>
                        <div class="col-md-6 d-flex gap-2">
                            <button type="submit" class="btn btn-outline-primary">Dersleri Filtrele</button>
                            <a href="{{ route('admin.archive.index', request()->except(['subject_search', 'subjects_page'])) }}" class="btn btn-outline-secondary">Ders Filtresini Temizle</a>
                        </div>
                    </form>
                </div>
                <div class="card-body p-0">
                    @if($subjects->isEmpty())
                        <div class="p-4 text-muted">Arsivlenmis ders bulunmuyor.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        @if(auth()->user()->isAdmin())
                                            <th style="width: 48px;">Sec</th>
                                        @endif
                                        <th>Ders</th>
                                        <th>Soru Sayisi</th>
                                        <th>Arsivlenme</th>
                                        <th>Otomatik Silme</th>
                                        <th class="text-end">Islem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subjects as $subject)
                                        <tr>
                                            @if(auth()->user()->isAdmin())
                                                <td>
                                                    <input type="checkbox" form="bulk-subject-restore-form" name="subject_ids[]" value="{{ $subject->id }}" class="form-check-input" aria-label="{{ $subject->name }} dersini sec">
                                                </td>
                                            @endif
                                            <td class="fw-semibold">{{ $subject->name }}</td>
                                            <td>{{ $subject->questions_count }}</td>
                                            <td>{{ optional($subject->archived_at)->format('d.m.Y H:i') }}</td>
                                            <td>{{ optional($subject->purge_after)->format('d.m.Y H:i') }}</td>
                                            <td class="text-end">
                                                @can('delete', $subject)
                                                    <form method="POST" action="{{ route('admin.archive.subjects.restore', $subject) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Bu dersi ve arsivdeki bagli sorularini geri almak istediginize emin misiniz? Sorular pasif duruma alinir.')">Geri Al</button>
                                                    </form>
                                                @else
                                                    <span class="badge text-bg-warning">Arsivde</span>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                @if($subjects->hasPages())
                    <div class="card-footer bg-white">
                        {{ $subjects->appends(request()->except('subjects_page'))->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h5 mb-0">Arsivlenen Sorular</h2>
                        <div class="small text-muted">Silinen sorular burada 7 gun boyunca izlenir.</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if(! $questions->isEmpty() && auth()->user()->isAdmin())
                            <form id="bulk-question-restore-form" method="POST" action="{{ route('admin.archive.questions.restore-bulk') }}">
                                @csrf
                            </form>
                            <button type="submit" form="bulk-question-restore-form" class="btn btn-sm btn-outline-success" onclick="return confirm('Secili sorulari geri almak istediginize emin misiniz? Sorular pasif duruma alinir.')">Secilenleri Geri Al</button>
                        @endif
                        <span class="badge text-bg-secondary">{{ $questions->total() }} kayit</span>
                    </div>
                </div>
                <div class="card-body border-bottom">
                    <form method="GET" action="{{ route('admin.archive.index') }}" class="row g-3 align-items-end">
                        <input type="hidden" name="subject_search" value="{{ $filters['subject_search'] }}">
                        <div class="col-md-4">
                            <label for="question_subject_id" class="form-label">Ders</label>
                            <select name="question_subject_id" id="question_subject_id" class="form-select">
                                <option value="">Tum Dersler</option>
                                @foreach($subjectOptions as $subject)
                                    <option value="{{ $subject->id }}" @selected((string) $filters['question_subject_id'] === (string) $subject->id)>
                                        {{ $subject->name }}{{ $subject->archived_at ? ' (arsiv)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="question_search" class="form-label">Soru Ara</label>
                            <input type="text" id="question_search" name="question_search" class="form-control" value="{{ $filters['question_search'] }}" placeholder="Soru metni veya siklarda ara">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-outline-primary">Sorulari Filtrele</button>
                            <a href="{{ route('admin.archive.index', request()->except(['question_subject_id', 'question_search', 'questions_page'])) }}" class="btn btn-outline-secondary">Temizle</a>
                        </div>
                    </form>
                </div>
                <div class="card-body p-0">
                    @if($questions->isEmpty())
                        <div class="p-4 text-muted">Arsivlenmis soru bulunmuyor.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        @if(auth()->user()->isAdmin())
                                            <th style="width: 48px;">Sec</th>
                                        @endif
                                        <th>Soru</th>
                                        <th>Ders</th>
                                        <th>Ekleyen</th>
                                        <th>Arsivlenme</th>
                                        <th>Otomatik Silme</th>
                                        <th class="text-end">Islem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($questions as $question)
                                        <tr>
                                            @if(auth()->user()->isAdmin())
                                                <td>
                                                    <input type="checkbox" form="bulk-question-restore-form" name="question_ids[]" value="{{ $question->id }}" class="form-check-input" aria-label="Soruyu sec">
                                                </td>
                                            @endif
                                            <td style="max-width: 420px;">{{ str($question->question_text)->limit(90) }}</td>
                                            <td>{{ $question->subject?->name ?? '-' }}</td>
                                            <td>{{ $question->createdBy?->name ?? '-' }}</td>
                                            <td>{{ optional($question->archived_at)->format('d.m.Y H:i') }}</td>
                                            <td>{{ optional($question->purge_after)->format('d.m.Y H:i') }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.questions.versions.index', $question) }}" class="btn btn-sm btn-outline-secondary">Surumler</a>
                                                @can('delete', $question)
                                                    <form method="POST" action="{{ route('admin.archive.questions.restore', $question) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Bu soruyu geri almak istediginize emin misiniz? Soru pasif duruma alinir.')">Geri Al</button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                @if($questions->hasPages())
                    <div class="card-footer bg-white">
                        {{ $questions->appends(request()->except('questions_page'))->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
