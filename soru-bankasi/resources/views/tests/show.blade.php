<x-app-layout>
    <x-slot name="header">
        <h1 class="sb-page-title">{{ $test->subject->name }} Testi</h1>
    </x-slot>

    <div class="container sb-page">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                @php($feedback = session('answer_feedback'))

                <div class="card sb-sticky-toolbar mb-3">
                    <div class="card-body py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div class="fw-semibold">Soru {{ $currentIndex }} / {{ $totalItems }}</div>
                        <div class="text-danger fw-bold">Kalan: <span id="remainingTimer">{{ gmdate('i:s', $remainingSeconds) }}</span></div>
                        <div class="d-flex align-items-center gap-2">
                            <label for="fontSizeSelect" class="form-label small text-muted mb-0">Yazi boyutu</label>
                            <select id="fontSizeSelect" class="form-select form-select-sm">
                                <option value="base">Normal</option>
                                <option value="large">Buyuk</option>
                                <option value="xlarge">Cok buyuk</option>
                            </select>
                        </div>
                    </div>
                </div>

                @if($feedback && $feedback['feedback_mode'] === 'INSTANT_FEEDBACK_LOCKED')
                    <div class="alert {{ $feedback['is_correct'] ? 'alert-success' : 'alert-danger' }}">
                        <div class="fw-semibold">{{ $feedback['is_correct'] ? 'Dogru cevap.' : 'Yanlis cevap.' }}</div>
                        <div class="mt-1">{{ $feedback['explanation'] }}</div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-body" id="questionContainer">
                        <div class="text-muted small mb-3">Geri bildirim modu: {{ $test->feedback_mode }}</div>
                        <h2 class="h5 fw-bold mb-4">{{ $item->question->question_text }}</h2>

                        <form method="POST" action="{{ route('tests.answer', $test) }}">
                            @csrf
                            <input type="hidden" name="test_item_id" value="{{ $item->id }}">
                            <input type="hidden" name="current_index" value="{{ $currentIndex }}">

                            <div class="vstack gap-3">
                                @foreach(['A', 'B', 'C', 'D', 'E'] as $option)
                                    @php($field = 'option_' . strtolower($option))
                                    <label class="sb-option-card">
                                        <input
                                            type="radio"
                                            name="answer"
                                            value="{{ $option }}"
                                            class="form-check-input me-2"
                                            @checked(old('answer', $item->user_answer) === $option)
                                            @disabled($test->feedback_mode === 'INSTANT_FEEDBACK_LOCKED' && $item->answered_at)
                                        >
                                        <span class="fw-semibold me-2">{{ $option }}.</span>
                                        <span>{{ $item->question->{$field} }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <div class="d-flex flex-wrap justify-content-between gap-3 pt-4">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="submit" name="action" value="prev" class="btn btn-outline-secondary">Geri</button>
                                    <button type="submit" name="action" value="stay" class="btn btn-outline-primary">Cevabi Kaydet</button>
                                    <button type="submit" name="action" value="next" class="btn btn-primary">Ileri</button>
                                </div>

                                <button type="button" class="btn btn-danger" onclick="document.getElementById('finishTestForm').submit();">
                                    Testi Bitir
                                </button>
                            </div>
                        </form>

                        <form id="finishTestForm" method="POST" action="{{ route('tests.finish', $test) }}" class="d-none">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const container = document.getElementById('questionContainer');
        const fontSizeSelect = document.getElementById('fontSizeSelect');
        const timer = document.getElementById('remainingTimer');
        const storageKey = 'test-font-size';

        const applyFontSize = (value) => {
            container.classList.remove('fs-6', 'fs-5', 'fs-4');

            if (value === 'large') {
                container.classList.add('fs-5');
            } else if (value === 'xlarge') {
                container.classList.add('fs-4');
            } else {
                container.classList.add('fs-6');
            }
        };

        const saved = localStorage.getItem(storageKey) || 'base';
        fontSizeSelect.value = saved;
        applyFontSize(saved);

        fontSizeSelect.addEventListener('change', () => {
            localStorage.setItem(storageKey, fontSizeSelect.value);
            applyFontSize(fontSizeSelect.value);
        });

        let remainingSeconds = {{ $remainingSeconds }};
        const renderTimer = () => {
            const minutes = String(Math.floor(remainingSeconds / 60)).padStart(2, '0');
            const seconds = String(remainingSeconds % 60).padStart(2, '0');
            timer.textContent = `${minutes}:${seconds}`;
        };

        renderTimer();

        const interval = setInterval(() => {
            if (remainingSeconds <= 0) {
                clearInterval(interval);
                window.location.reload();
                return;
            }

            remainingSeconds -= 1;
            renderTimer();
        }, 1000);
    </script>
</x-app-layout>
