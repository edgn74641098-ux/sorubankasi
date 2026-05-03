<x-app-layout>
    <x-slot name="header">
        <h1 class="sb-page-title">Test Sonucu</h1>
    </x-slot>

    <div class="container sb-page">
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
                        <div class="sb-stat-card">
                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                                <div class="fw-semibold">Soru {{ $index + 1 }}</div>
                                <div>
                                    @if($item->user_answer === null)
                                        <span class="badge text-bg-secondary">Bos</span>
                                    @elseif($item->is_correct)
                                        <span class="badge text-bg-success">Dogru</span>
                                    @else
                                        <span class="badge text-bg-danger">Yanlis</span>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3">{{ $item->question->question_text }}</div>
                            <div class="mt-3 small text-muted">
                                Verilen cevap: {{ $item->user_answer ?? 'Bos' }} · Dogru cevap: {{ $item->question->correct_option }} · Puan: {{ $item->awarded_points }}
                            </div>
                            <div class="mt-3 small">
                                <span class="fw-semibold">Aciklama:</span> {{ $item->question->explanation_text }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
