<x-app-layout>
    <x-slot name="header">
        <h1 class="sb-page-title">Ara</h1>
    </x-slot>

    <div class="container sb-page">
        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--brand">
                    <div class="card-body d-flex align-items-start gap-3">
                        <div class="rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px;">
                            <i class="bi bi-search fs-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h2 class="h4 fw-bold mb-2">Ders ve soru ara</h2>
                            <form method="GET" action="{{ route('search.index') }}" class="row g-2">
                                <div class="col-md-7">
                                    <label for="q" class="visually-hidden">Arama kelimesi</label>
                                    <input
                                        id="q"
                                        name="q"
                                        type="search"
                                        value="{{ $term }}"
                                        class="form-control form-control-lg"
                                        placeholder="Kelime yazip Enter'a basin"
                                        autofocus
                                    >
                                </div>
                                <div class="col-md-3">
                                    <label for="subject_id" class="visually-hidden">Ders filtresi</label>
                                    <select id="subject_id" name="subject_id" class="form-select form-select-lg">
                                        <option value="">Tum dersler</option>
                                        @foreach($subjects as $subject)
                                            <option value="{{ $subject->id }}" @selected((string) $selectedSubjectId === (string) $subject->id)>
                                                {{ $subject->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="bi bi-search me-1"></i> Ara
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--gold">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted small">Sonuc ozeti</div>
                            <div class="display-6 fw-bold">{{ $term === '' ? '-' : $subjectResults->count() + $questionResults->count() }}</div>
                            <div class="text-muted small">
                                @if($term === '')
                                    Arama bekleniyor.
                                @elseif($selectedSubjectId)
                                    Ders filtresi aktif.
                                @else
                                    {{ $subjectResults->count() }} ders, {{ $questionResults->count() }} soru.
                                @endif
                            </div>
                        </div>
                        <div class="rounded bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px;">
                            <i class="bi bi-filter-circle fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($term === '')
            <div class="card sb-dashboard-card sb-dashboard-card--neutral">
                <div class="card-body text-muted">
                    Ders filtresi secerek soru metni, siklar veya aciklama icinde arama yapabilirsiniz.
                </div>
            </div>
        @else
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card sb-dashboard-card sb-dashboard-card--green h-100">
                        <div class="card-header bg-white d-flex align-items-center justify-content-between">
                            <h2 class="h5 fw-bold mb-0"><i class="bi bi-journal-bookmark me-2 text-success"></i>Ders Havuzu</h2>
                            <span class="badge text-bg-success">{{ $subjectResults->count() }}</span>
                        </div>
                        <div class="card-body">
                            <div class="vstack gap-3">
                                @forelse($subjectResults as $subject)
                                    <a href="{{ route('subjects.index', ['subject_id' => $subject->id]) }}" class="text-decoration-none text-reset">
                                        <div class="border rounded p-3 bg-white">
                                            <div class="fw-semibold">{{ $subject->name }}</div>
                                            <div class="text-muted small mt-1">{{ $subject->active_questions_count }} aktif soru</div>
                                        </div>
                                    </a>
                                @empty
                                    <div class="text-muted small">Bu kelimeyle eslesen ders bulunamadi.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card sb-dashboard-card sb-dashboard-card--brand">
                        <div class="card-header bg-white d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <h2 class="h5 fw-bold mb-0"><i class="bi bi-card-text me-2 text-primary"></i>Soru Sonuclari</h2>
                                <div class="text-muted small mt-1">
                                    "{{ $term }}" kelimesinin gectigi aktif sorular
                                    @if($selectedSubjectId)
                                        · ders filtresi uygulanıyor
                                    @endif
                                </div>
                            </div>
                            <span class="badge text-bg-primary">{{ $questionResults->count() }}</span>
                        </div>
                        <div class="card-body">
                            <div class="vstack gap-3">
                                @forelse($questionResults as $question)
                                    <div class="sb-stat-card">
                                        <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-2 mb-2">
                                            <div class="d-flex flex-wrap gap-2">
                                                <span class="badge text-bg-primary">{{ $question->subject?->name ?? 'Ders yok' }}</span>
                                                <span class="badge text-bg-light text-dark border">Zorluk: {{ number_format((float) $question->difficulty_score, 1) }}</span>
                                            </div>
                                            <a href="{{ route('subjects.index', ['subject_id' => $question->subject_id]) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-play-fill me-1"></i> Bu Dersten Test
                                            </a>
                                        </div>

                                        <div class="fw-semibold mb-3 small lh-base">{{ $question->question_text }}</div>

                                        <div class="row g-2 small">
                                            @foreach(['A', 'B', 'C', 'D', 'E'] as $option)
                                                @php($field = 'option_' . strtolower($option))
                                                @php($isCorrectOption = $question->correct_option === $option)
                                                <div class="col-md-6">
                                                    <div class="border rounded p-2 lh-sm {{ $isCorrectOption ? 'bg-success-subtle border-success text-success-emphasis' : 'bg-white' }}">
                                                        <span class="fw-semibold">{{ $option }}.</span> {{ $question->{$field} }}
                                                        @if($isCorrectOption)
                                                            <span class="badge text-bg-success ms-2 fw-semibold">Dogru cevap</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        @if($question->explanation_text)
                                            <div class="text-muted small mt-3">
                                                <span class="fw-semibold">Aciklama:</span> {{ \Illuminate\Support\Str::limit($question->explanation_text, 180) }}
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="text-muted">Bu kelimenin gectigi aktif soru bulunamadi.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
