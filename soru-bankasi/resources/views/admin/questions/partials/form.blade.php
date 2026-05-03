<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="POST" action="{{ $action }}">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="subject_id" class="form-label">Ders</label>
                    <select name="subject_id" id="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                        <option value="">Ders seçin</option>
                        @foreach($subjects as $subjectItem)
                            <option value="{{ $subjectItem->id }}" @selected((string) old('subject_id', $question?->subject_id) === (string) $subjectItem->id)>{{ $subjectItem->name }}</option>
                        @endforeach
                    </select>
                    @error('subject_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="difficulty_score" class="form-label">Zorluk Puanı</label>
                    <input type="number" min="1" max="10" id="difficulty_score" name="difficulty_score" class="form-control @error('difficulty_score') is-invalid @enderror" value="{{ old('difficulty_score', $question ? (int) $question->difficulty_score : 5) }}" required>
                    @error('difficulty_score')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="status" class="form-label">Durum</label>
                    <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $question?->status ?? 'draft') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="question_text" class="form-label">Soru Metni</label>
                    <textarea name="question_text" id="question_text" rows="5" class="form-control @error('question_text') is-invalid @enderror" required>{{ old('question_text', $question?->question_text) }}</textarea>
                    @error('question_text')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="option_a" class="form-label">A Şıkkı</label>
                    <textarea name="option_a" id="option_a" rows="2" class="form-control @error('option_a') is-invalid @enderror" required>{{ old('option_a', $question?->option_a) }}</textarea>
                    @error('option_a')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="option_b" class="form-label">B Şıkkı</label>
                    <textarea name="option_b" id="option_b" rows="2" class="form-control @error('option_b') is-invalid @enderror" required>{{ old('option_b', $question?->option_b) }}</textarea>
                    @error('option_b')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="option_c" class="form-label">C Şıkkı</label>
                    <textarea name="option_c" id="option_c" rows="2" class="form-control @error('option_c') is-invalid @enderror" required>{{ old('option_c', $question?->option_c) }}</textarea>
                    @error('option_c')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="option_d" class="form-label">D Şıkkı</label>
                    <textarea name="option_d" id="option_d" rows="2" class="form-control @error('option_d') is-invalid @enderror" required>{{ old('option_d', $question?->option_d) }}</textarea>
                    @error('option_d')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="option_e" class="form-label">E Şıkkı</label>
                    <textarea name="option_e" id="option_e" rows="2" class="form-control @error('option_e') is-invalid @enderror" required>{{ old('option_e', $question?->option_e) }}</textarea>
                    @error('option_e')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="correct_option" class="form-label">Doğru Cevap</label>
                    <select name="correct_option" id="correct_option" class="form-select @error('correct_option') is-invalid @enderror" required>
                        @foreach(['A', 'B', 'C', 'D', 'E'] as $option)
                            <option value="{{ $option }}" @selected(old('correct_option', $question?->correct_option) === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('correct_option')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="explanation" class="form-label">Açıklama</label>
                    <textarea name="explanation" id="explanation" rows="4" class="form-control @error('explanation') is-invalid @enderror" required>{{ old('explanation', $question?->explanation_text) }}</textarea>
                    @error('explanation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
                <a href="{{ route('admin.questions.index') }}" class="btn btn-outline-secondary">İptal</a>
            </div>
        </form>
    </div>
</div>