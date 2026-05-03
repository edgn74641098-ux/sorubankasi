<x-app-layout>
    <x-slot name="header">
        <h1 class="sb-page-title">Test Baslat</h1>
    </x-slot>

    <div class="container sb-page">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if ($errors->has('test'))
                    <div class="alert alert-danger">
                        {{ $errors->first('test') }}
                        @if ($errors->has('error_code'))
                            <div class="small mt-1">Kod: {{ $errors->first('error_code') }}</div>
                        @endif
                    </div>
                @endif

                @if($activeTest)
                    <div class="alert alert-warning d-flex align-items-center justify-content-between gap-3">
                        <div>Su anda aktif bir testiniz var.</div>
                        <a href="{{ route('tests.show', $activeTest) }}" class="btn btn-warning">Teste Devam Et</a>
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('tests.start') }}" class="row g-4">
                            @csrf

                            <div class="col-12">
                                <label for="subject_id" class="form-label">Ders</label>
                                <select id="subject_id" name="subject_id" class="form-select" required>
                                    <option value="">Ders secin</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}" @selected((string) old('subject_id', request('subject_id')) === (string) $subject->id)>
                                            {{ $subject->name }} ({{ $subject->active_questions_count }} soru)
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <div class="form-label">Mod</div>
                                <div class="row g-3">
                                    @foreach([
                                        'RANDOM' => ['Rastgele', 'Dersteki aktif soru havuzundan rastgele 20 soru secer.'],
                                        'DIFFICULTY_RANGE' => ['Zorluk Araligi', 'Sectiginiz araliktan toplar, gerekirse ders havuzundan tamamlar.'],
                                        'WEAKNESSES' => ['Takildiklarim', 'Yanlis cozdugunuz sorulari onceliklendirir.'],
                                    ] as $value => [$label, $description])
                                        <div class="col-md-4">
                                            <label class="sb-option-card h-100">
                                                <input type="radio" name="mode" value="{{ $value }}" class="form-check-input me-2" @checked(old('mode', 'RANDOM') === $value)>
                                                <span class="fw-semibold">{{ $label }}</span>
                                                <span class="d-block text-muted small mt-1">{{ $description }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="min_difficulty" class="form-label">Minimum zorluk</label>
                                <input type="range" id="min_difficulty" name="min_difficulty" min="1" max="10" value="{{ old('min_difficulty', 3) }}" class="form-range">
                                <div class="text-muted small">Secim: <span id="minDifficultyValue">{{ old('min_difficulty', 3) }}</span></div>
                            </div>
                            <div class="col-md-6">
                                <label for="max_difficulty" class="form-label">Maksimum zorluk</label>
                                <input type="range" id="max_difficulty" name="max_difficulty" min="1" max="10" value="{{ old('max_difficulty', 7) }}" class="form-range">
                                <div class="text-muted small">Secim: <span id="maxDifficultyValue">{{ old('max_difficulty', 7) }}</span></div>
                            </div>

                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Testi Baslat</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const minSlider = document.getElementById('min_difficulty');
        const maxSlider = document.getElementById('max_difficulty');
        const minOutput = document.getElementById('minDifficultyValue');
        const maxOutput = document.getElementById('maxDifficultyValue');

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
