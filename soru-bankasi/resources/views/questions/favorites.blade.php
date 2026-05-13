<x-app-layout>
    @push('scripts')
        <script src="{{ asset('js/favorites-index.js') }}" defer></script>
    @endpush

    <x-slot name="header">
        <h1 class="sb-page-title">Favorilerim</h1>
    </x-slot>

    <div class="container sb-page">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="card sb-dashboard-card sb-dashboard-card--neutral mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('questions.favorites.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label for="subject_id" class="form-label">Ders</label>
                        <select id="subject_id" name="subject_id" class="form-select">
                            <option value="">Tum dersler</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected((string) $selectedSubjectId === (string) $subject->id)>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="q" class="form-label">Soru/Not Ara</label>
                        <input id="q" name="q" type="search" class="form-control" value="{{ $term }}" placeholder="Soru metni veya not">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-outline-primary w-100">Filtrele</button>
                    </div>
                </form>
            </div>
        </div>

        @if($favorites->isEmpty())
            <div class="card sb-dashboard-card sb-dashboard-card--neutral">
                <div class="card-body text-muted">Henuz favori soru yok.</div>
            </div>
        @else
            <form id="favoritesPdfForm" method="POST" action="{{ route('questions.favorites.pdf') }}">
                @csrf
            </form>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-muted small">{{ $favorites->count() }} favori soru listeleniyor.</div>
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-primary btn-sm js-favorites-select-all">Tumunu sec</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm js-favorites-clear-all">Tumunu cikar</button>
                        <button type="submit" form="favoritesPdfForm" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Secilenleri PDF indir
                        </button>
                    </div>
                </div>

                <div class="alert alert-light border small py-2">
                    <i class="bi bi-info-circle me-1"></i>
                    PDF icin soru secimi yukaridan toplu veya tek tek yapilabilir.
                </div>

                <div class="vstack gap-4">
                    @foreach($groupedFavorites as $subjectName => $subjectFavorites)
                        <div class="card sb-dashboard-card sb-dashboard-card--brand">
                            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                                <h2 class="h5 fw-bold mb-0">{{ $subjectName }}</h2>
                                <span class="badge text-bg-primary">{{ $subjectFavorites->count() }} soru</span>
                            </div>
                            <div class="card-body">
                                <div class="vstack gap-3">
                                    @foreach($subjectFavorites as $favorite)
                                        @php($question = $favorite->question)
                                        <div class="sb-stat-card">
                                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-2">
                                                <label class="form-check d-flex align-items-center gap-2 mb-0">
                                                    <input type="checkbox" class="form-check-input js-favorite-pdf-checkbox" form="favoritesPdfForm" name="favorite_ids[]" value="{{ $favorite->id }}">
                                                    <span class="small text-muted">PDF'e ekle</span>
                                                </label>
                                                <div class="d-flex gap-2">
                                                    @if($question)
                                                        <form method="POST" action="{{ route('questions.favorites.destroy', $question) }}" class="js-favorite-remove-form">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Favoriden Cikar</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="fw-semibold mb-2">{{ $question?->question_text ?? 'Soru silinmis' }}</div>

                                            @if($question)
                                                <div class="row g-2 small mb-3">
                                                    @foreach(['A', 'B', 'C', 'D', 'E'] as $option)
                                                        @php($field = 'option_' . strtolower($option))
                                                        @php($isCorrect = $question->correct_option === $option)
                                                        <div class="col-md-6">
                                                            <div class="border rounded p-2 {{ $isCorrect ? 'bg-success-subtle border-success' : 'bg-white' }}">
                                                                <strong>{{ $option }}.</strong> {{ $question->{$field} }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @if($question->explanation_text)
                                                    <div class="small text-muted mb-3">
                                                        <span class="fw-semibold">Aciklama:</span> {{ $question->explanation_text }}
                                                    </div>
                                                @endif
                                            @endif

                                            <form method="POST" action="{{ route('questions.favorites.note.update', $favorite) }}">
                                                @csrf
                                                @method('PATCH')
                                                <label class="form-label small fw-semibold" for="note_{{ $favorite->id }}">Kisisel not (sadece sana ozel)</label>
                                                <textarea id="note_{{ $favorite->id }}" name="note" rows="2" class="form-control form-control-sm" maxlength="2000">{{ $favorite->note }}</textarea>
                                                <div class="d-flex justify-content-end mt-2">
                                                    <button type="submit" class="btn btn-sm btn-primary">Notu Kaydet</button>
                                                </div>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
        @endif
    </div>
</x-app-layout>
