@php
    $reportQuestion = $question;
    $reportContext = $context ?? 'default';
    $reportSuggested = $suggestedCorrectOption ?? $reportQuestion->correct_option;
    $reportDetailsId = "reportQuestion{$reportContext}{$reportQuestion->id}";
    $reportSelectedCategory = old('category', 'WRONG_ANSWER');
    $reportSuggestedSubjectId = old('suggested_subject_id');
    $reportSuggestedQuestionText = old('suggested_question_text', $reportQuestion->question_text);
    $reportSuggestedOptionA = old('suggested_option_a', $reportQuestion->option_a);
    $reportSuggestedOptionB = old('suggested_option_b', $reportQuestion->option_b);
    $reportSuggestedOptionC = old('suggested_option_c', $reportQuestion->option_c);
    $reportSuggestedOptionD = old('suggested_option_d', $reportQuestion->option_d);
    $reportSuggestedOptionE = old('suggested_option_e', $reportQuestion->option_e);
    $reportSuggestedExplanation = old('suggested_explanation_text', $reportQuestion->explanation_text);
    static $reportSubjects = null;
    if ($reportSubjects === null) {
        $reportSubjects = \App\Models\Subject::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
@endphp

<details class="sb-report-details js-report-details mt-3" id="{{ $reportDetailsId }}" @if((string) old('question_id') === (string) $reportQuestion->id) open @endif>
    <summary class="btn btn-sm btn-outline-warning">
        <i class="bi bi-exclamation-triangle me-1"></i> Itiraz Et
    </summary>

    <form method="POST" action="{{ route('questions.report') }}" class="border rounded bg-white p-3 mt-3">
        @csrf
        <input type="hidden" name="question_id" value="{{ $reportQuestion->id }}">

        <div class="row g-3">
            <div class="col-md-4">
                <label for="report_category_{{ $reportContext }}_{{ $reportQuestion->id }}" class="form-label small fw-semibold">Itiraz sebebi</label>
                <select id="report_category_{{ $reportContext }}_{{ $reportQuestion->id }}" name="category" class="form-select form-select-sm" required>
                    <option value="WRONG_ANSWER" @selected($reportSelectedCategory === 'WRONG_ANSWER')>Yanlis cevap</option>
                    <option value="UNCLEAR_WORDING" @selected($reportSelectedCategory === 'UNCLEAR_WORDING')>Ifade belirsiz</option>
                    <option value="TYPO" @selected($reportSelectedCategory === 'TYPO')>Yazim hatasi</option>
                    <option value="WRONG_SUBJECT" @selected($reportSelectedCategory === 'WRONG_SUBJECT')>Yanlis ders</option>
                    <option value="OTHER" @selected($reportSelectedCategory === 'OTHER')>Diger</option>
                </select>
            </div>

            <div class="col-md-4 js-correct-option-wrap">
                <label for="suggested_correct_option_{{ $reportContext }}_{{ $reportQuestion->id }}" class="form-label small fw-semibold">Dogru sik oneriniz</label>
                <select id="suggested_correct_option_{{ $reportContext }}_{{ $reportQuestion->id }}" name="suggested_correct_option" class="form-select form-select-sm js-correct-option-field" required>
                    @foreach(['A', 'B', 'C', 'D', 'E'] as $option)
                        @php($field = 'option_' . strtolower($option))
                        <option value="{{ $option }}" @selected(old('suggested_correct_option', $reportSuggested) === $option)>
                            {{ $option }} - {{ \Illuminate\Support\Str::limit($reportQuestion->{$field}, 45) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 js-subject-wrap d-none">
                <label for="suggested_subject_{{ $reportContext }}_{{ $reportQuestion->id }}" class="form-label small fw-semibold">Dogru ders oneriniz</label>
                <select id="suggested_subject_{{ $reportContext }}_{{ $reportQuestion->id }}" name="suggested_subject_id" class="form-select form-select-sm js-subject-field">
                    <option value="">Ders secin</option>
                    @foreach($reportSubjects as $reportSubject)
                        <option value="{{ $reportSubject->id }}" @selected((string) $reportSuggestedSubjectId === (string) $reportSubject->id)>
                            {{ $reportSubject->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-warning btn-sm w-100">
                    <i class="bi bi-send me-1"></i> Itirazi Gonder
                </button>
            </div>

            <div class="col-12">
                <label for="report_note_{{ $reportContext }}_{{ $reportQuestion->id }}" class="form-label small fw-semibold">Not</label>
                <textarea id="report_note_{{ $reportContext }}_{{ $reportQuestion->id }}" name="note" rows="2" maxlength="500" class="form-control form-control-sm" placeholder="Soruda neyin hatali oldugunu kisaca aciklayin.">{{ (string) old('question_id') === (string) $reportQuestion->id ? old('note') : '' }}</textarea>
                <div class="form-text">En fazla 500 karakter.</div>
            </div>

            <div class="col-12 js-typo-wrap d-none">
                <div class="border rounded p-3 bg-light-subtle">
                    <div class="fw-semibold mb-3">Duzenlenmis Soru Oneriniz</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="suggested_question_text_{{ $reportContext }}_{{ $reportQuestion->id }}" class="form-label small fw-semibold">Soru metni</label>
                            <textarea id="suggested_question_text_{{ $reportContext }}_{{ $reportQuestion->id }}" name="suggested_question_text" rows="3" class="form-control form-control-sm js-typo-field">{{ $reportSuggestedQuestionText }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="suggested_option_a_{{ $reportContext }}_{{ $reportQuestion->id }}" class="form-label small fw-semibold">A</label>
                            <input id="suggested_option_a_{{ $reportContext }}_{{ $reportQuestion->id }}" type="text" name="suggested_option_a" value="{{ $reportSuggestedOptionA }}" class="form-control form-control-sm js-typo-field">
                        </div>
                        <div class="col-md-6">
                            <label for="suggested_option_b_{{ $reportContext }}_{{ $reportQuestion->id }}" class="form-label small fw-semibold">B</label>
                            <input id="suggested_option_b_{{ $reportContext }}_{{ $reportQuestion->id }}" type="text" name="suggested_option_b" value="{{ $reportSuggestedOptionB }}" class="form-control form-control-sm js-typo-field">
                        </div>
                        <div class="col-md-6">
                            <label for="suggested_option_c_{{ $reportContext }}_{{ $reportQuestion->id }}" class="form-label small fw-semibold">C</label>
                            <input id="suggested_option_c_{{ $reportContext }}_{{ $reportQuestion->id }}" type="text" name="suggested_option_c" value="{{ $reportSuggestedOptionC }}" class="form-control form-control-sm js-typo-field">
                        </div>
                        <div class="col-md-6">
                            <label for="suggested_option_d_{{ $reportContext }}_{{ $reportQuestion->id }}" class="form-label small fw-semibold">D</label>
                            <input id="suggested_option_d_{{ $reportContext }}_{{ $reportQuestion->id }}" type="text" name="suggested_option_d" value="{{ $reportSuggestedOptionD }}" class="form-control form-control-sm js-typo-field">
                        </div>
                        <div class="col-md-6">
                            <label for="suggested_option_e_{{ $reportContext }}_{{ $reportQuestion->id }}" class="form-label small fw-semibold">E</label>
                            <input id="suggested_option_e_{{ $reportContext }}_{{ $reportQuestion->id }}" type="text" name="suggested_option_e" value="{{ $reportSuggestedOptionE }}" class="form-control form-control-sm js-typo-field">
                        </div>
                        <div class="col-md-6">
                            <label for="suggested_explanation_text_{{ $reportContext }}_{{ $reportQuestion->id }}" class="form-label small fw-semibold">Aciklama</label>
                            <textarea id="suggested_explanation_text_{{ $reportContext }}_{{ $reportQuestion->id }}" name="suggested_explanation_text" rows="2" class="form-control form-control-sm js-typo-field">{{ $reportSuggestedExplanation }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</details>
