<x-app-layout>
    <x-slot name="header">
        <h1 class="sb-page-title">Test Sonucu</h1>
    </x-slot>

    <div class="container sb-page">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="sb-stat-card">
                            <div class="text-muted small">Puan</div>
                            <div class="display-6 fw-bold">{{ $test->score }}/100</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="sb-stat-card">
                            <div class="text-muted small">Dogru</div>
                            <div class="h3 fw-bold mb-0">{{ $test->correct_count }} <span class="fs-6 text-muted">({{ $percentages['correct'] }}%)</span></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="sb-stat-card">
                            <div class="text-muted small">Yanlis</div>
                            <div class="h3 fw-bold mb-0">{{ $test->wrong_count }} <span class="fs-6 text-muted">({{ $percentages['wrong'] }}%)</span></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="sb-stat-card">
                            <div class="text-muted small">Bos</div>
                            <div class="h3 fw-bold mb-0">{{ $test->blank_count }} <span class="fs-6 text-muted">({{ $percentages['blank'] }}%)</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <h2 class="h5 fw-bold mb-0">Soru Detaylari</h2>
            </div>
            <div class="card-body">
                <div class="vstack gap-3">
                    @foreach($test->items as $index => $item)
                        @php
                            $formatAnswer = function (?string $answer) use ($item): string {
                                if ($answer === null) {
                                    return 'Bos';
                                }

                                $field = 'option_' . strtolower($answer);
                                $text = $item->question->{$field} ?? null;

                                return $text ? "{$answer} - {$text}" : $answer;
                            };
                        @endphp
                        <div class="sb-stat-card">
                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                                <div class="fw-semibold">Soru {{ $index + 1 }}</div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @if($test->feedback_mode !== 'NO_FEEDBACK')
                                        @if($item->user_answer === null)
                                            <span class="badge text-bg-secondary">Bos</span>
                                        @elseif($item->is_correct)
                                            <span class="badge text-bg-success">Dogru</span>
                                        @else
                                            <span class="badge text-bg-danger">Yanlis</span>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3">{{ $item->question->question_text }}</div>
                            @if($test->feedback_mode !== 'NO_FEEDBACK')
                                <div class="mt-3 d-flex flex-wrap gap-2 small">
                                    <span class="badge rounded-pill text-bg-{{ $item->is_correct ? 'success' : 'danger' }} text-wrap text-start">
                                        Verilen cevap: {{ $formatAnswer($item->user_answer) }}
                                    </span>
                                    <span class="badge rounded-pill text-bg-success text-wrap text-start">
                                        Dogru cevap: {{ $formatAnswer($item->question->correct_option) }}
                                    </span>
                                    <span class="badge rounded-pill text-bg-light text-dark border">
                                        Puan: {{ $item->awarded_points }}
                                    </span>
                                </div>
                                <div class="mt-3 small">
                                    <span class="fw-semibold">Aciklama:</span> {{ $item->question->explanation_text }}
                                </div>
                            @else
                                <div class="mt-3 small text-muted">
                                    Puan: {{ $item->awarded_points }}
                                </div>
                            @endif

                            <details class="sb-report-details mt-3" id="reportQuestion{{ $item->question_id }}" @if((string) old('question_id') === (string) $item->question_id) open @endif>
                                <summary class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-exclamation-triangle me-1"></i> Itiraz Et
                                </summary>

                                <form method="POST" action="{{ route('questions.report') }}" class="border rounded bg-white p-3 mt-3">
                                    @csrf
                                    <input type="hidden" name="question_id" value="{{ $item->question_id }}">

                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="report_category_{{ $item->question_id }}" class="form-label small fw-semibold">Itiraz sebebi</label>
                                            <select id="report_category_{{ $item->question_id }}" name="category" class="form-select form-select-sm" required>
                                                <option value="WRONG_ANSWER" @selected(old('category') === 'WRONG_ANSWER')>Yanlis cevap</option>
                                                <option value="UNCLEAR_WORDING" @selected(old('category') === 'UNCLEAR_WORDING')>Ifade belirsiz</option>
                                                <option value="TYPO" @selected(old('category') === 'TYPO')>Yazim hatasi</option>
                                                <option value="OTHER" @selected(old('category') === 'OTHER')>Diger</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="suggested_correct_option_{{ $item->question_id }}" class="form-label small fw-semibold">Dogru sik oneriniz</label>
                                            <select id="suggested_correct_option_{{ $item->question_id }}" name="suggested_correct_option" class="form-select form-select-sm" required>
                                                @foreach(['A', 'B', 'C', 'D', 'E'] as $option)
                                                    @php($field = 'option_' . strtolower($option))
                                                    <option value="{{ $option }}" @selected(old('suggested_correct_option', $item->user_answer ?: $item->question->correct_option) === $option)>
                                                        {{ $option }} - {{ \Illuminate\Support\Str::limit($item->question->{$field}, 45) }}
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
                                            <label for="report_note_{{ $item->question_id }}" class="form-label small fw-semibold">Not</label>
                                            <textarea id="report_note_{{ $item->question_id }}" name="note" rows="2" maxlength="500" class="form-control form-control-sm" placeholder="Soruda neyin hatali oldugunu kisaca aciklayin.">{{ (string) old('question_id') === (string) $item->question_id ? old('note') : '' }}</textarea>
                                            <div class="form-text">En fazla 500 karakter.</div>
                                        </div>
                                    </div>
                                </form>
                            </details>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
