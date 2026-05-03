@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Soru Öner</h3>
                    <small>Sistemde yayınlanacak yeni sorular önerebilirsiniz.</small>
                </div>
                <div class="card-body">
                    {{-- Info Message --}}
                    <div class="alert alert-info" role="alert">
                        <strong>Bilgi:</strong> Öneriniz moderatör tarafından incelenecektir. 
                        Onaylanan her soru için <strong>+10 puan</strong> kazanacaksınız. 
                        Günlük maksimum 20 soru önerebilirsiniz.
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h5>Lütfen hataları düzeltiniz:</h5>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('questions.store') }}" method="POST">
                        @csrf

                        {{-- Subject Selection --}}
                        <div class="mb-4">
                            <label for="subject_id" class="form-label">
                                <strong>Ders Seçin</strong>
                            </label>
                            <select class="form-select @error('subject_id') is-invalid @enderror" 
                                    id="subject_id" name="subject_id" required>
                                <option value="">-- Ders Seçiniz --</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}" 
                                            {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('subject_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Question Text --}}
                        <div class="mb-4">
                            <label for="question_text" class="form-label">
                                <strong>Soru Metni</strong>
                                <small class="text-muted">(20-4000 karakter)</small>
                            </label>
                            <textarea class="form-control @error('question_text') is-invalid @enderror" 
                                      id="question_text" name="question_text" rows="4" 
                                      placeholder="Soruyu yazınız..." required>{{ old('question_text') }}</textarea>
                            @error('question_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1">
                                <span id="char-count">0</span> / 4000 karakter
                            </small>
                        </div>

                        {{-- Options --}}
                        <div class="mb-4">
                            <label class="form-label"><strong>Şıklar (A, B, C, D, E)</strong></label>

                            @foreach (['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D', 'E' => 'E'] as $key => $label)
                                <div class="mb-2">
                                    <label for="option_{{ strtolower($key) }}" class="form-label">
                                        <strong>Şık {{ $label }}</strong>
                                    </label>
                                    <input type="text" 
                                           class="form-control @error("option_" . strtolower($key)) is-invalid @enderror" 
                                           id="option_{{ strtolower($key) }}" 
                                           name="option_{{ strtolower($key) }}" 
                                           placeholder="Şık {{ $label }}"
                                           value="{{ old("option_" . strtolower($key)) }}"
                                           maxlength="500" required>
                                    @error("option_" . strtolower($key))
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endforeach
                        </div>

                        {{-- Correct Option --}}
                        <div class="mb-4">
                            <label for="correct_option" class="form-label">
                                <strong>Doğru Cevap</strong>
                            </label>
                            <select class="form-select @error('correct_option') is-invalid @enderror" 
                                    id="correct_option" name="correct_option" required>
                                <option value="">-- Doğru Cevabı Seçiniz --</option>
                                @foreach (['A', 'B', 'C', 'D', 'E'] as $option)
                                    <option value="{{ $option }}" 
                                            {{ old('correct_option') == $option ? 'selected' : '' }}>
                                        {{ $option }}
                                    </option>
                                @endforeach
                            </select>
                            @error('correct_option')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Explanation --}}
                        <div class="mb-4">
                            <label for="explanation_text" class="form-label">
                                <strong>Açıklama</strong>
                                <small class="text-muted">(20-2000 karakter)</small>
                            </label>
                            <textarea class="form-control @error('explanation_text') is-invalid @enderror" 
                                      id="explanation_text" name="explanation_text" rows="4" 
                                      placeholder="Sorunun cevabı ve açıklamasını yazınız..." 
                                      required>{{ old('explanation_text') }}</textarea>
                            @error('explanation_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1">
                                <span id="explanation-count">0</span> / 2000 karakter
                            </small>
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-lg">
                                İptal
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                Soru Gönder
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Recent Submissions Info --}}
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Önerilerim</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">
                        Önerilerinizi görmek için <a href="{{ route('questions.submitted') }}">buraya tıklayınız</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('question_text').addEventListener('input', function() {
    document.getElementById('char-count').textContent = this.value.length;
});

document.getElementById('explanation_text').addEventListener('input', function() {
    document.getElementById('explanation-count').textContent = this.value.length;
});

// Set initial counts
document.getElementById('char-count').textContent = 
    document.getElementById('question_text').value.length;
document.getElementById('explanation-count').textContent = 
    document.getElementById('explanation_text').value.length;
</script>
@endsection
