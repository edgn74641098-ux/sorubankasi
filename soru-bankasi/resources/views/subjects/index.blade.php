<x-app-layout>
    <x-slot name="header">
        <h1 class="sb-page-title">Dersler</h1>
    </x-slot>

    <div class="container sb-page">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif

        @if ($errors->has('test'))
            <div class="alert alert-danger">
                {{ $errors->first('test') }}
                @if ($errors->has('error_code'))
                    <div class="small mt-1">Kod: {{ $errors->first('error_code') }}</div>
                @endif
            </div>
        @endif

        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--brand">
                    <div class="card-body d-flex align-items-start gap-3">
                        <div class="rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px;">
                            <i class="bi bi-journal-check fs-3"></i>
                        </div>
                        <div>
                            <h2 class="h4 fw-bold mb-2">Dersini sec, teste basla</h2>
                            <p class="text-muted mb-3">Dersler ve test baslatma artik tek yerde. Havuzu sec, modu belirle, yarisa gir.</p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge text-bg-primary">20 soru</span>
                                <span class="badge text-bg-info">30 dakika</span>
                                <span class="badge text-bg-warning">+5 puan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--gold">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted small">Aktif soru havuzu</div>
                            <div class="display-6 fw-bold">{{ $subjects->sum('approved_questions_count') }}</div>
                            <div class="text-muted small">{{ $subjects->count() }} aktif ders.</div>
                        </div>
                        <div class="rounded bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px;">
                            <i class="bi bi-collection fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($activeTest)
            <div class="card mb-4 sb-dashboard-card sb-dashboard-card--gold">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                            <i class="bi bi-hourglass-split fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">Devam eden testiniz var</div>
                            <div class="text-muted small">Once aktif testi tamamlayin, sonra yeni bir test baslatin.</div>
                        </div>
                    </div>
                    <a href="{{ route('tests.show', $activeTest) }}" class="btn btn-warning">
                        <i class="bi bi-arrow-clockwise me-1"></i> Aktif Teste Git
                    </a>
                </div>
            </div>
        @endif

        <div class="card sb-dashboard-card sb-dashboard-card--neutral mb-4">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <div class="fw-semibold">Donem Secimi</div>
                    <div class="text-muted small">Son sectiginiz donem profilde kayitli kalir.</div>
                </div>
                <div class="btn-group" role="group" aria-label="Donem secimi">
                    <a href="{{ route('subjects.index', ['term' => 1, 'subject_id' => request('subject_id'), 'mode' => old('mode', request('mode', $preferredMode ?? 'RANDOM'))]) }}"
                       class="btn btn-sm {{ (int) ($selectedTerm ?? 1) === 1 ? 'btn-primary' : 'btn-outline-primary' }}">
                        1. Donem
                    </a>
                    <a href="{{ route('subjects.index', ['term' => 2, 'subject_id' => request('subject_id'), 'mode' => old('mode', request('mode', $preferredMode ?? 'RANDOM'))]) }}"
                       class="btn btn-sm {{ (int) ($selectedTerm ?? 1) === 2 ? 'btn-primary' : 'btn-outline-primary' }}">
                        2. Donem
                    </a>
                </div>
            </div>
        </div>

        @if($subjects->isEmpty())
            <div class="card sb-dashboard-card sb-dashboard-card--neutral">
                <div class="card-body text-muted">Bu donemde aktif ders yok.</div>
            </div>
        @else

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card sb-dashboard-card sb-dashboard-card--neutral h-100">
                        <div class="card-header bg-white">
                            <h2 class="h5 fw-bold mb-0"><i class="bi bi-grid-3x3-gap me-2 text-primary"></i>Ders Havuzu</h2>
                        </div>
                        <div class="card-body">
                            <div class="vstack gap-3">
                                @foreach($subjects as $subject)
                                    <a
                                        href="{{ route('subjects.index', ['term' => ($selectedTerm ?? 1), 'subject_id' => $subject->id, 'mode' => old('mode', request('mode', $preferredMode ?? 'RANDOM'))]) }}"
                                        class="text-decoration-none text-reset"
                                    >
                                        <div class="border rounded p-3 bg-white {{ (string) request('subject_id') === (string) $subject->id ? 'border-primary border-2' : '' }}">
                                            <div class="d-flex align-items-start justify-content-between gap-3">
                                                <div>
                                                    <div class="fw-semibold">{{ $subject->name }}</div>
                                                    <div class="mt-2">
                                                        <span class="badge text-bg-success">
                                                            Basarim: {{ $subject->success_rate !== null ? '%' . number_format($subject->success_rate, 1) : '-' }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <span class="badge text-bg-{{ $subject->approved_questions_count >= 20 ? 'primary' : 'secondary' }}">
                                                    {{ $subject->approved_questions_count }} soru
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card sb-dashboard-card sb-dashboard-card--brand">
                        <div class="card-header bg-white">
                            <h2 class="h5 fw-bold mb-0"><i class="bi bi-sliders me-2 text-primary"></i>Test Ayarlari</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('tests.start') }}" class="row g-4">
                                @csrf
                                @php
                                    $selectedSubjectId = old('subject_id', request('subject_id', $preferredSubjectId ?? $subjects->first()?->id));
                                @endphp

                                <div class="col-12">
                                    <label for="subject_id" class="form-label">Ders</label>
                                    <select id="subject_id" name="subject_id" class="form-select form-select-lg" required>
                                        <option value="">Ders secin</option>
                                        @foreach($subjects as $subject)
                                            <option
                                                value="{{ $subject->id }}"
                                                data-weak-count="{{ $subject->weak_questions_count }}"
                                                data-solved-unique-count="{{ $subject->solved_unique_count }}"
                                                data-remaining-unique-count="{{ $subject->remaining_unique_count }}"
                                                @selected((string) $selectedSubjectId === (string) $subject->id)
                                            >
                                                {{ $subject->name }} ({{ $subject->approved_questions_count }} soru)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @php
                                    $selectedMode = old('mode', request('mode', $preferredMode ?? 'RANDOM'));
                                    $excludeSolvedQuestions = (bool) old('exclude_solved_questions', $preferredExcludeSolvedQuestions ?? false);
                                    $selectedSubject = $subjects->firstWhere('id', (int) $selectedSubjectId) ?? $subjects->first();
                                    $selectedWeakQuestionCount = (int) ($selectedSubject?->weak_questions_count ?? 0);
                                    $modes = [
                                        'RANDOM' => ['Rastgele', 'Dersteki aktif soru havuzundan rastgele 20 soru secer.', 'bi-shuffle', 'sb-dashboard-card--brand'],
                                        'DIFFICULTY_RANGE' => ['Zorluk Araligi', 'Sectiginiz araliktan toplar, gerekirse ders havuzundan tamamlar.', 'bi-speedometer2', 'sb-dashboard-card--blue'],
                                        'WEAKNESSES' => ['Takildiklarim', 'Yanlis cozdugunuz sorulari onceliklendirir.', 'bi-lightning-charge', 'sb-dashboard-card--gold'],
                                    ];
                                @endphp
                                <div class="col-12">
                                    <div class="form-label">Mod</div>
                                    <div class="row g-3">
                                        @foreach($modes as $value => [$label, $description, $icon, $cardClass])
                                            <div class="col-md-4">
                                                <label class="sb-option-card sb-dashboard-card {{ $cardClass }} h-100">
                                                    <div class="d-flex align-items-start gap-3">
                                                        <input type="radio" name="mode" value="{{ $value }}" class="form-check-input mt-2" @checked($selectedMode === $value)>
                                                        <div>
                                                            <div class="rounded bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center mb-2" style="width: 38px; height: 38px;">
                                                                <i class="bi {{ $icon }}"></i>
                                                            </div>
                                                            @if($value === 'WEAKNESSES')
                                                                <span class="badge text-bg-warning d-inline-flex align-items-center gap-1 mb-2">
                                                                    <i class="bi bi-lightning-charge"></i>
                                                                    <span id="weakQuestionModeCount">{{ $selectedWeakQuestionCount }}</span> takildigim soru
                                                                </span>
                                                            @endif
                                                            <span class="fw-semibold d-block">{{ $label }}</span>
                                                            <span class="d-block text-muted small mt-1">{{ $description }}</span>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="exclude_solved_questions"
                                            name="exclude_solved_questions"
                                            value="1"
                                            @checked($excludeSolvedQuestions)
                                        >
                                        <label class="form-check-label fw-semibold" for="exclude_solved_questions">
                                            Cozdugum sorular yeni testte gelmesin
                                        </label>
                                        <div class="text-muted small mt-1">
                                            Bu ayar acikken daha once cozdugunuz sorular yeni test havuzundan cikarilir.
                                        </div>
                                        <div class="mt-2 small">
                                            <span class="badge text-bg-primary-subtle text-primary-emphasis me-1">
                                                Benzersiz cozulen: <span id="solvedUniqueCount">{{ (int) ($selectedSubject?->solved_unique_count ?? 0) }}</span>
                                            </span>
                                            <span class="badge text-bg-success-subtle text-success-emphasis">
                                                Kalan benzersiz: <span id="remainingUniqueCount">{{ (int) ($selectedSubject?->remaining_unique_count ?? 0) }}</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 js-difficulty-range-block {{ $selectedMode === 'DIFFICULTY_RANGE' ? '' : 'd-none' }}">
                                    <div class="card sb-dashboard-card sb-dashboard-card--blue">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center gap-2 mb-3">
                                                <i class="bi bi-speedometer2 text-primary"></i>
                                                <div class="fw-semibold">Zorluk araligi</div>
                                            </div>
                                            <div class="row g-4">
                                                <div class="col-md-6">
                                                    <label for="min_difficulty" class="form-label">Minimum zorluk</label>
                                                    <input type="range" id="min_difficulty" name="min_difficulty" min="1" max="10" value="{{ old('min_difficulty', $preferredMinDifficulty ?? 3) }}" class="form-range">
                                                    <div class="text-muted small">Secim: <span id="minDifficultyValue">{{ old('min_difficulty', $preferredMinDifficulty ?? 3) }}</span></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="max_difficulty" class="form-label">Maksimum zorluk</label>
                                                    <input type="range" id="max_difficulty" name="max_difficulty" min="1" max="10" value="{{ old('max_difficulty', $preferredMaxDifficulty ?? 7) }}" class="form-range">
                                                    <div class="text-muted small">Secim: <span id="maxDifficultyValue">{{ old('max_difficulty', $preferredMaxDifficulty ?? 7) }}</span></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                    <div class="text-muted small">
                                        Hazirsan ritmi kacirma. Test baslayinca sure islemeye baslar.
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-play-fill me-1"></i> Testi Baslat
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        const minSlider = document.getElementById('min_difficulty');
        const maxSlider = document.getElementById('max_difficulty');
        const minOutput = document.getElementById('minDifficultyValue');
        const maxOutput = document.getElementById('maxDifficultyValue');
        const difficultyBlock = document.querySelector('.js-difficulty-range-block');
        const modeInputs = document.querySelectorAll('input[name="mode"]');
        const subjectSelect = document.getElementById('subject_id');
        const weakQuestionModeCount = document.getElementById('weakQuestionModeCount');
        const solvedUniqueCount = document.getElementById('solvedUniqueCount');
        const remainingUniqueCount = document.getElementById('remainingUniqueCount');

        const syncDifficultyVisibility = () => {
            const selected = document.querySelector('input[name="mode"]:checked');
            if (! difficultyBlock || ! selected) {
                return;
            }
            difficultyBlock.classList.toggle('d-none', selected.value !== 'DIFFICULTY_RANGE');
        };

        modeInputs.forEach((input) => input.addEventListener('change', syncDifficultyVisibility));
        syncDifficultyVisibility();

        const syncWeakQuestionCount = () => {
            if (! subjectSelect || ! weakQuestionModeCount) {
                return;
            }

            const selectedOption = subjectSelect.options[subjectSelect.selectedIndex];
            weakQuestionModeCount.textContent = selectedOption?.dataset.weakCount || '0';
            if (solvedUniqueCount) {
                solvedUniqueCount.textContent = selectedOption?.dataset.solvedUniqueCount || '0';
            }
            if (remainingUniqueCount) {
                remainingUniqueCount.textContent = selectedOption?.dataset.remainingUniqueCount || '0';
            }
        };

        subjectSelect?.addEventListener('change', syncWeakQuestionCount);
        syncWeakQuestionCount();

        if (minSlider && maxSlider) {
            const syncValues = () => {
                if (parseInt(minSlider.value, 10) > parseInt(maxSlider.value, 10)) {
                    maxSlider.value = minSlider.value;
                }

                minOutput.textContent = minSlider.value;
                maxOutput.textContent = maxSlider.value;
            };

            minSlider.addEventListener('input', syncValues);
            maxSlider.addEventListener('input', syncValues);
            syncValues();
        }
    </script>
</x-app-layout>
