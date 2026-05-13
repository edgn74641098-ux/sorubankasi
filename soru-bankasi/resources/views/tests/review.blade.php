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

                            @if(! $item->question->trashed())
                            @include('questions.partials.report-form', [
                                'question' => $item->question,
                                'context' => 'review_',
                                'suggestedCorrectOption' => $item->user_answer ?: $item->question->correct_option,
                            ])

                            <details class="sb-report-details mt-2" id="unnecessaryQuestion{{ $item->question_id }}" @if($errors->has('reason')) open @endif>
                                <summary class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash3 me-1"></i> Gereksiz soru olarak raporla
                                </summary>

                                <form method="POST" action="{{ route('questions.report-unnecessary', $item->question) }}" class="border rounded bg-white p-3 mt-3">
                                    @csrf
                                    <label for="unnecessary_reason_{{ $item->question_id }}" class="form-label small fw-semibold">Raporlama nedeni</label>
                                    <textarea id="unnecessary_reason_{{ $item->question_id }}" name="reason" rows="2" maxlength="500" class="form-control form-control-sm" required placeholder="Bu sorunun neden gereksiz oldugunu kisaca aciklayin.">{{ old('reason') }}</textarea>
                                    <div class="form-text">En fazla 500 karakter.</div>
                                    @error('reason')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror

                                    <button type="submit" class="btn btn-danger btn-sm mt-3" data-confirm="Bu soruyu gereksiz soru olarak raporlamak istediginize emin misiniz?">
                                        <i class="bi bi-send me-1"></i> Bildirimi Gonder
                                    </button>
                                </form>
                            </details>
                            @else
                                <div class="alert alert-secondary small mt-3 mb-0">
                                    Bu soru arsivden kaldirilmis. Gecmis test detaylari korunur, yeni itiraz veya gereksiz soru bildirimi olusturulamaz.
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
