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
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="stuck_only"
                                            name="stuck_only"
                                            value="1"
                                            @checked($stuckOnly ?? false)
                                        >
                                        <label class="form-check-label fw-semibold" for="stuck_only">
                                            Takildigim sorular
                                        </label>
                                        <div class="text-muted small mt-1">
                                            Acikken sadece secili derste takildigin sorular listelenir.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="use_difficulty"
                                            name="use_difficulty"
                                            value="1"
                                            @checked($useDifficulty ?? false)
                                        >
                                        <label class="form-check-label fw-semibold" for="use_difficulty">
                                            Zorluk derecesi sec
                                        </label>
                                        <div class="text-muted small mt-1">
                                            Acikken sadece secilen zorluk araligindaki sorular listelenir.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 {{ ($useDifficulty ?? false) ? '' : 'd-none' }}" id="difficulty-range-row">
                                    <div class="card sb-dashboard-card sb-dashboard-card--blue">
                                        <div class="card-body">
                                            <div class="row g-4">
                                                <div class="col-md-6">
                                                    <div class="d-flex justify-content-start mb-1">
                                                        <span class="badge text-bg-success-subtle text-success-emphasis" id="minDifficultyValue">{{ number_format((float) ($minDifficulty ?? 3), 1, '.', '') }}</span>
                                                    </div>
                                                    <input
                                                        type="range"
                                                        id="min_difficulty"
                                                        name="min_difficulty"
                                                        min="1"
                                                        max="10"
                                                        step="0.1"
                                                        value="{{ number_format((float) ($minDifficulty ?? 3), 1, '.', '') }}"
                                                        class="form-range sb-difficulty-range"
                                                    >
                                                    <div class="sb-difficulty-scale" aria-hidden="true">
                                                        @for($score = 1; $score <= 10; $score++)
                                                            <span>{{ $score }}</span>
                                                        @endfor
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="d-flex justify-content-end mb-1">
                                                        <span class="badge text-bg-danger-subtle text-danger-emphasis" id="maxDifficultyValue">{{ number_format((float) ($maxDifficulty ?? 8), 1, '.', '') }}</span>
                                                    </div>
                                                    <input
                                                        type="range"
                                                        id="max_difficulty"
                                                        name="max_difficulty"
                                                        min="1"
                                                        max="10"
                                                        step="0.1"
                                                        value="{{ number_format((float) ($maxDifficulty ?? 8), 1, '.', '') }}"
                                                        class="form-range sb-difficulty-range"
                                                    >
                                                    <div class="sb-difficulty-scale" aria-hidden="true">
                                                        @for($score = 1; $score <= 10; $score++)
                                                            <span>{{ $score }}</span>
                                                        @endfor
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if($showQuestions)
                                    <div class="col-md-12">
                                        <a
                                            href="{{ route('search.pdf', ['q' => $term !== '' ? $term : null, 'subject_id' => $selectedSubjectId, 'stuck_only' => ($stuckOnly ?? false) ? 1 : null, 'use_difficulty' => ($useDifficulty ?? false) ? 1 : null, 'min_difficulty' => ($useDifficulty ?? false) ? ($minDifficulty ?? 3) : null, 'max_difficulty' => ($useDifficulty ?? false) ? ($maxDifficulty ?? 8) : null]) }}"
                                            class="btn btn-outline-secondary btn-sm"
                                        >
                                            <i class="bi bi-file-earmark-pdf me-1"></i> PDF Indir
                                        </a>
                                    </div>
                                @endif
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
                            <div class="display-6 fw-bold">{{ $showQuestions ? ($subjectResults->count() + $questionResults->total()) : '-' }}</div>
                            <div class="text-muted small">
                                @if(!$showQuestions)
                                    Arama bekleniyor.
                                @elseif(($stuckOnly ?? false) && $selectedSubjectId)
                                    Secili dersteki takildigin sorular listeleniyor.
                                @elseif($useDifficulty ?? false)
                                    Zorluk araligi: {{ $minDifficulty ?? 3 }} - {{ $maxDifficulty ?? 8 }}
                                @elseif($term === '' && $selectedSubjectId)
                                    Secili dersteki tum aktif sorular listeleniyor.
                                @elseif($selectedSubjectId)
                                    Ders filtresi aktif.
                                @else
                                    {{ $subjectResults->count() }} ders, {{ $questionResults->total() }} soru.
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

        @if(!$showQuestions)
            <div class="card sb-dashboard-card sb-dashboard-card--neutral">
                <div class="card-body text-muted">
                    Ders filtresi secerek soru metni, siklar veya aciklama icinde arama yapabilirsiniz.
                </div>
            </div>
        @else
            <div class="row g-4">
                @if($term !== '')
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
                @endif

                <div class="{{ $term !== '' ? 'col-lg-8' : 'col-12' }}">
                    <div class="card sb-dashboard-card sb-dashboard-card--brand">
                        <div id="question-results"></div>
                        <div class="card-header bg-white d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <h2 class="h5 fw-bold mb-0"><i class="bi bi-card-text me-2 text-primary"></i>Soru Sonuclari</h2>
                                <div class="text-muted small mt-1">
                                    @if($term !== '')
                                        "{{ $term }}" kelimesinin gectigi aktif sorular
                                        @if($selectedSubjectId)
                                            - ders filtresi uygulaniyor
                                        @endif
                                        @if($stuckOnly ?? false)
                                            - sadece takildigin sorular
                                        @endif
                                        @if($useDifficulty ?? false)
                                            - zorluk {{ $minDifficulty ?? 3 }}-{{ $maxDifficulty ?? 8 }}
                                        @endif
                                    @elseif($stuckOnly ?? false)
                                        Secili derste sadece takildigin sorular
                                    @elseif($useDifficulty ?? false)
                                        Zorluk {{ $minDifficulty ?? 3 }}-{{ $maxDifficulty ?? 8 }} araligindaki sorular
                                    @else
                                        Secili dersteki tum aktif sorular
                                    @endif
                                </div>
                            </div>
                            <span class="badge text-bg-primary">{{ $questionResults->total() }}</span>
                        </div>
                        <div class="card-body">
                            <div class="vstack gap-3">
                                @forelse($questionResults as $question)
                                    @php($rowNumber = $questionResults->firstItem() ? $questionResults->firstItem() + $loop->index : $loop->iteration)
                                    <div class="sb-stat-card">
                                        <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-2 mb-2">
                                            <div class="d-flex flex-wrap gap-2">
                                                <span class="badge text-bg-dark">#{{ $rowNumber }}</span>
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

                                        @include('questions.partials.report-form', [
                                            'question' => $question,
                                            'context' => 'search_',
                                            'suggestedCorrectOption' => $question->correct_option,
                                        ])
                                    </div>
                                @empty
                                    <div class="text-muted">
                                        @if($term !== '')
                                            Bu kelimenin gectigi aktif soru bulunamadi.
                                        @elseif($stuckOnly ?? false)
                                            Secili derste takildigin soru bulunamadi.
                                        @else
                                            Secili derste aktif soru bulunamadi.
                                        @endif
                                    </div>
                                @endforelse
                            </div>

                            @if($questionResults->hasPages())
                                <div class="mt-4 d-flex justify-content-center">
                                    {{ $questionResults->onEachSide(1)->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    <style>
        .sb-difficulty-range {
            --sb-difficulty-track: linear-gradient(
                90deg,
                #22c55e 0%,
                #22c55e 50%,
                #f59e0b 62%,
                #ef4444 100%
            );
            height: 0.45rem;
        }

        .sb-difficulty-range::-webkit-slider-runnable-track {
            background: var(--sb-difficulty-track);
            height: 0.45rem;
            border-radius: 999px;
        }

        .sb-difficulty-range::-moz-range-track {
            background: var(--sb-difficulty-track);
            height: 0.45rem;
            border-radius: 999px;
        }

        .sb-difficulty-range::-webkit-slider-thumb {
            margin-top: -0.34rem;
        }

        .sb-difficulty-scale {
            display: flex;
            justify-content: space-between;
            margin-top: 0.2rem;
            font-size: 0.74rem;
            color: #6b7280;
            user-select: none;
        }
    </style>
    <script>
        (function () {
            var toggle = document.getElementById('use_difficulty');
            var row = document.getElementById('difficulty-range-row');
            var minSlider = document.getElementById('min_difficulty');
            var maxSlider = document.getElementById('max_difficulty');
            var minOutput = document.getElementById('minDifficultyValue');
            var maxOutput = document.getElementById('maxDifficultyValue');

            if (!toggle || !row) {
                return;
            }

            function syncDifficultyVisibility() {
                row.classList.toggle('d-none', !toggle.checked);
            }

            toggle.addEventListener('change', syncDifficultyVisibility);
            syncDifficultyVisibility();

            if (minSlider && maxSlider) {
                var syncRangeValues = function () {
                    if (parseFloat(minSlider.value) > parseFloat(maxSlider.value)) {
                        maxSlider.value = minSlider.value;
                    }

                    if (minOutput) {
                        minOutput.textContent = Number(minSlider.value).toFixed(1);
                    }
                    if (maxOutput) {
                        maxOutput.textContent = Number(maxSlider.value).toFixed(1);
                    }
                };

                minSlider.addEventListener('input', syncRangeValues);
                maxSlider.addEventListener('input', syncRangeValues);
                syncRangeValues();
            }
        })();
    </script>
</x-app-layout>
