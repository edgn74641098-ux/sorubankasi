<x-app-layout>
    <x-slot name="header">
        <h1 class="sb-page-title">{{ $test->subject->name }} Testi</h1>
    </x-slot>

    <div class="container sb-page">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                @php($feedback = session('answer_feedback'))

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if (session('info'))
                    <div class="alert alert-info">{{ session('info') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

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
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                            <h2 class="h5 fw-bold mb-0">{{ $item->question->question_text }}</h2>
                            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#reportModal" title="Bu soruya itiraz et">
                                Itiraz
                            </button>
                        </div>

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
                                            @disabled(in_array($test->feedback_mode, ['INSTANT_FEEDBACK_LOCKED', 'NO_FEEDBACK'], true) && $item->answered_at)
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

    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('questions.report') }}">
                    @csrf
                    <input type="hidden" name="question_id" value="{{ $item->question_id }}">

                    <div class="modal-header">
                        <h2 class="modal-title h5" id="reportModalLabel">Soru Itirazi</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="reportCategory" class="form-label">Kategori</label>
                            <select id="reportCategory" name="category" class="form-select" required>
                                <option value="WRONG_ANSWER">Yanlis cevap</option>
                                <option value="UNCLEAR_WORDING">Ifade belirsiz</option>
                                <option value="TYPO">Yazim hatasi</option>
                                <option value="OTHER">Diger</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="suggestedCorrectOption" class="form-label">Dogru sik oneriniz</label>
                            <select id="suggestedCorrectOption" name="suggested_correct_option" class="form-select" required>
                                @foreach(['A', 'B', 'C', 'D', 'E'] as $option)
                                    @php($field = 'option_' . strtolower($option))
                                    <option value="{{ $option }}" @selected(($item->user_answer ?: $item->question->correct_option) === $option)>
                                        {{ $option }} - {{ \Illuminate\Support\Str::limit($item->question->{$field}, 55) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="reportNote" class="form-label">Not</label>
                            <textarea id="reportNote" name="note" rows="4" maxlength="500" class="form-control" placeholder="Sorunun neden itiraz edildigini aciklayin..."></textarea>
                            <div class="form-text small mt-1"><span id="charCount">0</span>/500</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Vazgec</button>
                        <button type="submit" class="btn btn-warning">Gonder</button>
                    </div>
                </form>
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

        const noteTextarea = document.getElementById('reportNote');
        const charCount = document.getElementById('charCount');

        noteTextarea.addEventListener('input', () => {
            charCount.textContent = noteTextarea.value.length;
        });
    </script>
</x-app-layout>
