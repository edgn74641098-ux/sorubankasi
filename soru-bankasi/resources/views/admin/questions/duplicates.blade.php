@extends('layouts.admin', ['pageTitle' => 'Kopya Sorular', 'title' => 'Kopya Sorular'])

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Kopya Soru Temizligi</h1>
            <p class="text-muted mb-0">Ayni ders icinde metni birebir ayni olan sorulari grup halinde gorebilir ve arsive tasiyabilirsiniz.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.questions.duplicates.index') }}" class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="subject_id" class="form-label">Ders</label>
                    <select name="subject_id" id="subject_id" class="form-select">
                        <option value="">Tum Dersler</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected((string) ($filters['subject_id'] ?? '') === (string) $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Soru Metninde Ara</label>
                    <input type="text" id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Kelime girin">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-outline-primary">Filtrele</button>
                    <a href="{{ route('admin.questions.duplicates.index') }}" class="btn btn-outline-secondary">Temizle</a>
                </div>
            </form>

            @if($duplicateGroups->isEmpty())
                <div class="alert alert-info mb-0">Bu filtrede kopya soru grubu bulunamadi.</div>
            @else
                <div class="d-grid gap-3">
                    @foreach($duplicateGroups as $groupIndex => $group)
                        @php($defaultKeepId = $group['questions']->first()->id)
                        <div class="border rounded p-3 bg-light">
                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                                <div>
                                    <div class="fw-semibold">{{ $group['subject_name'] }}</div>
                                    <div class="text-muted small">{{ $group['count'] }} adet kopya soru bulundu.</div>
                                </div>
                                <div class="text-muted small">Ornek metin: {{ \Illuminate\Support\Str::limit($group['canonical_text'], 160) }}</div>
                            </div>

                            <form method="POST" action="{{ route('admin.questions.duplicates.archive-group') }}" class="row g-2">
                                @csrf
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="sb-width-48">Tut</th>
                                                    <th>ID</th>
                                                    <th>Soru</th>
                                                    <th>Durum</th>
                                                    <th>Versiyon</th>
                                                    <th class="text-end">Incele</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($group['questions'] as $question)
                                                    <tr>
                                                        <td>
                                                            <input
                                                                type="radio"
                                                                name="keep_question_id"
                                                                value="{{ $question->id }}"
                                                                class="form-check-input"
                                                                @checked($question->id === $defaultKeepId)
                                                                required
                                                            >
                                                            <input type="hidden" name="duplicate_ids[]" value="{{ $question->id }}">
                                                        </td>
                                                        <td>{{ $question->id }}</td>
                                                        <td>{{ \Illuminate\Support\Str::limit($question->question_text, 180) }}</td>
                                                        <td>
                                                            <span class="badge text-bg-{{ $question->status === 'active' ? 'success' : 'secondary' }}">
                                                                {{ $question->status }}
                                                            </span>
                                                        </td>
                                                        <td>v{{ $question->current_version }}</td>
                                                        <td class="text-end">
                                                            <a href="{{ route('admin.questions.edit', $question) }}" class="btn btn-sm btn-outline-primary">
                                                                Soruyu Incele
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Secili soru disindakileri arsive tasimak istediginize emin misiniz?')">
                                        Secilmeyenleri Arsive Tasi
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $duplicateGroups->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
@endsection
