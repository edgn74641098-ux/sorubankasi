@extends('layouts.admin', ['pageTitle' => 'Soru Yonetimi', 'title' => 'Soru Yonetimi'])

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Sorular</h1>
            <p class="text-muted mb-0">Soru havuzunu filtreleyin ve yonetin. Arsivlenen sorular ayri Arsiv bolumunde izlenir.</p>
        </div>
        <a href="{{ route('admin.questions.create') }}" class="btn btn-primary">Yeni Soru Ekle</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
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
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Ders</th>
                                <th>Soru</th>
                                <th>Zorluk</th>
                                <th>Durum</th>
                                <th>Versiyon</th>
                                <th class="text-end">Islemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($questions as $question)
                                <tr>
                                    <td>{{ $question->subject?->name }}</td>
                                    <td class="sb-min-340">{{ \Illuminate\Support\Str::limit($question->question_text, 120) }}</td>
                                    <td>{{ number_format((float) $question->difficulty_score, 1) }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $question->status === 'active' ? 'success' : ($question->status === 'draft' ? 'warning' : 'secondary') }}">
                                            {{ $statusOptions[$question->status] ?? $question->status }}
                                        </span>
                                    </td>
                                    <td>v{{ $question->current_version }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.questions.edit', $question) }}" class="btn btn-sm btn-outline-primary">Duzenle</a>
                                        <a href="{{ route('admin.questions.versions.index', $question) }}" class="btn btn-sm btn-outline-secondary">Surumler</a>

                                        @can('delete', $question)
                                            <form method="POST" action="{{ route('admin.questions.destroy', $question) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu soruyu arsive tasimak istediginize emin misiniz?')">Arsive Tasi</button>
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
