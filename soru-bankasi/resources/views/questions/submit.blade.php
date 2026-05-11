<x-app-layout>
    <x-slot name="header">
        <h1 class="sb-page-title">Soru Oner</h1>
    </x-slot>

    <div class="container sb-page">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card sb-dashboard-card sb-dashboard-card--brand">
                    <div class="card-body d-flex align-items-start gap-3">
                        <div class="rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px;">
                            <i class="bi bi-lightbulb fs-3"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Kullanici katkisi</div>
                            <h2 class="h4 fw-bold mb-2">Yeni soru onerisi gonder</h2>
                            <p class="text-muted mb-0">Oneriniz moderasyon ekibi tarafindan incelenir. Onaylanan soru icin puan kazanirsiniz.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--gold">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Kurallar</div>
                        <ul class="mb-0 ps-3 small">
                            <li>Soru metni: 20-4000 karakter</li>
                            <li>Aciklama: 20-2000 karakter</li>
                            <li>Gunluk en fazla 20 onerim</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4 sb-dashboard-card sb-dashboard-card--neutral">
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h2 class="h6 mb-2">Lutfen hatalari duzeltin</h2>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                    </div>
                @endif

                <form action="{{ route('questions.store') }}" method="POST" class="row g-3">
                    @csrf

                    <div class="col-12">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="small text-muted">Donem:</span>
                            <a href="{{ route('questions.create', ['term' => 1]) }}"
                               class="btn btn-sm {{ (int) ($selectedTerm ?? 1) === 1 ? 'btn-primary' : 'btn-outline-primary' }}">
                                1. Donem
                            </a>
                            <a href="{{ route('questions.create', ['term' => 2]) }}"
                               class="btn btn-sm {{ (int) ($selectedTerm ?? 1) === 2 ? 'btn-primary' : 'btn-outline-primary' }}">
                                2. Donem
                            </a>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="subject_id" class="form-label fw-semibold">Ders</label>
                        <select class="form-select @error('subject_id') is-invalid @enderror" id="subject_id" name="subject_id" required>
                            <option value="">Ders seciniz</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected(old('subject_id') == $subject->id)>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('subject_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="question_text" class="form-label fw-semibold">Soru metni <span class="text-muted small">(20-4000)</span></label>
                        <textarea class="form-control @error('question_text') is-invalid @enderror" id="question_text" name="question_text" rows="4" maxlength="4000" required>{{ old('question_text') }}</textarea>
                        @error('question_text')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="text-muted small mt-1"><span id="char-count">0</span> / 4000</div>
                    </div>

                    @foreach (['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'e' => 'E'] as $key => $label)
                        <div class="col-12">
                            <label for="option_{{ $key }}" class="form-label fw-semibold">Sik {{ $label }}</label>
                            <input type="text" class="form-control @error('option_'.$key) is-invalid @enderror" id="option_{{ $key }}" name="option_{{ $key }}" value="{{ old('option_'.$key) }}" maxlength="500" required>
                            @error('option_'.$key)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach

                    <div class="col-md-4">
                        <label for="correct_option" class="form-label fw-semibold">Dogru cevap</label>
                        <select class="form-select @error('correct_option') is-invalid @enderror" id="correct_option" name="correct_option" required>
                            <option value="">Seciniz</option>
                            @foreach (['A', 'B', 'C', 'D', 'E'] as $option)
                                <option value="{{ $option }}" @selected(old('correct_option') === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('correct_option')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="explanation_text" class="form-label fw-semibold">Aciklama <span class="text-muted small">(20-2000)</span></label>
                        <textarea class="form-control @error('explanation_text') is-invalid @enderror" id="explanation_text" name="explanation_text" rows="4" maxlength="2000" required>{{ old('explanation_text') }}</textarea>
                        @error('explanation_text')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="text-muted small mt-1"><span id="explanation-count">0</span> / 2000</div>
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Iptal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i> Oneriyi Gonder
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4 sb-dashboard-card sb-dashboard-card--neutral">
            <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Onerilerim</h2>
                    <p class="text-muted mb-0">Gonderdiginiz soru onerilerinin durumunu tek listede takip edin.</p>
                </div>
                <a href="{{ route('questions.submitted') }}" class="btn btn-outline-primary">
                    <i class="bi bi-list-check me-1"></i> Onerilerimi Ac
                </a>
            </div>
        </div>
    </div>

    <script>
    const questionText = document.getElementById('question_text');
    const explanationText = document.getElementById('explanation_text');
    const charCount = document.getElementById('char-count');
    const explanationCount = document.getElementById('explanation-count');

    function syncCounts() {
        charCount.textContent = questionText.value.length;
        explanationCount.textContent = explanationText.value.length;
    }

    questionText.addEventListener('input', syncCounts);
    explanationText.addEventListener('input', syncCounts);
    syncCounts();
    </script>
</x-app-layout>
