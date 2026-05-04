<x-app-layout>
    <x-slot name="header">
        <h1 class="sb-page-title">Panel</h1>
    </x-slot>

    <div class="container sb-page">
        @php
            $perfMaxQuestions = max(1, collect($performanceTrend)->max('questions'));
            $perfMaxTests = max(1, collect($performanceTrend)->max('tests'));
            $perfTotals = [
                'tests' => collect($performanceTrend)->sum('tests'),
                'questions' => collect($performanceTrend)->sum('questions'),
                'correct' => collect($performanceTrend)->sum('correct'),
                'accuracy' => null,
            ];
            $perfTotals['accuracy'] = $perfTotals['questions'] > 0 ? round(($perfTotals['correct'] / $perfTotals['questions']) * 100, 1) : null;
            $perfChart = [
                'width' => 900,
                'height' => 300,
                'left' => 48,
                'right' => 24,
                'top' => 24,
                'bottom' => 44,
            ];
            $perfChart['plot_width'] = $perfChart['width'] - $perfChart['left'] - $perfChart['right'];
            $perfChart['plot_height'] = $perfChart['height'] - $perfChart['top'] - $perfChart['bottom'];
            $perfChart['base_y'] = $perfChart['height'] - $perfChart['bottom'];
            $perfCount = max(1, count($performanceTrend));
            $accuracyPoints = collect($performanceTrend)->map(function ($day, $index) use ($perfChart, $perfCount) {
                $accuracy = $day['accuracy'] ?? 0;
                $x = $perfChart['left'] + ($perfCount === 1 ? 0 : ($index * ($perfChart['plot_width'] / ($perfCount - 1))));
                $y = $perfChart['base_y'] - (($accuracy / 100) * $perfChart['plot_height']);

                return round($x, 1) . ',' . round($y, 1);
            })->implode(' ');
            $testPoints = collect($performanceTrend)->map(function ($day, $index) use ($perfChart, $perfCount, $perfMaxTests) {
                $x = $perfChart['left'] + ($perfCount === 1 ? 0 : ($index * ($perfChart['plot_width'] / ($perfCount - 1))));
                $y = $perfChart['base_y'] - (($day['tests'] / $perfMaxTests) * $perfChart['plot_height']);

                return round($x, 1) . ',' . round($y, 1);
            })->implode(' ');
        @endphp

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card sb-dashboard-card sb-dashboard-card--brand">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="bi bi-rocket-takeoff fs-4"></i>
                            </div>
                            <div>
                                <h2 class="h5 fw-bold mb-2">Yarisa hazir misin?</h2>
                                <p class="text-muted mb-4">Bugunku testin siralamadaki yerini degistirebilir. Hedefini sec, teste gir, farki kapat.</p>

                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-primary" href="{{ route('subjects.index') }}">
                                        <i class="bi bi-grid-3x3-gap me-1"></i> Dersleri Gor
                                    </a>
                                    <a class="btn btn-outline-primary" href="{{ route('subjects.index') }}">
                                        <i class="bi bi-play-circle me-1"></i> Test Baslat
                                    </a>
                                    @if($activeTest)
                                        <a class="btn btn-warning" href="{{ route('tests.show', $activeTest) }}">
                                            <i class="bi bi-arrow-clockwise me-1"></i> Aktif Teste Devam Et
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--gold">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted small">Toplam Puan</div>
                            <div class="display-6 fw-bold">{{ $totalScore }}</div>
                        </div>
                        <div class="rounded bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px;">
                            <i class="bi bi-stars fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4 sb-dashboard-card sb-dashboard-card--brand">
            <div class="card-header bg-white">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1"><i class="bi bi-activity me-2 text-primary"></i>Performans Grafigi</h2>
                        <div class="text-muted small">Son 30 gunde cozdunuz test, soru ve dogruluk oraniniz.</div>
                    </div>
                    <div class="sb-performance-summary">
                        <span><strong>{{ number_format($perfTotals['tests']) }}</strong> test</span>
                        <span><strong>{{ number_format($perfTotals['questions']) }}</strong> soru</span>
                        <span><strong>{{ $perfTotals['accuracy'] !== null ? '%' . number_format($perfTotals['accuracy'], 1) : '-' }}</strong> dogruluk</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="sb-performance-legend">
                    <span><i class="sb-performance-dot sb-performance-dot--questions"></i> Cozulen soru</span>
                    <span><i class="sb-performance-dot sb-performance-dot--tests"></i> Cozulen test</span>
                    <span><i class="sb-performance-dot sb-performance-dot--accuracy"></i> Dogruluk orani</span>
                </div>

                <div class="sb-performance-chart-wrap">
                    <svg class="sb-performance-chart" viewBox="0 0 {{ $perfChart['width'] }} {{ $perfChart['height'] }}" role="img" aria-labelledby="performance-title performance-desc">
                        <title id="performance-title">Kisisel performans grafigi</title>
                        <desc id="performance-desc">Yesil kolonlar soru sayisini, mavi cizgi test sayisini, turuncu cizgi dogruluk oranini gosterir.</desc>

                        @for($i = 0; $i <= 4; $i++)
                            @php
                                $gridY = $perfChart['top'] + ($i * ($perfChart['plot_height'] / 4));
                            @endphp
                            <line x1="{{ $perfChart['left'] }}" y1="{{ $gridY }}" x2="{{ $perfChart['width'] - $perfChart['right'] }}" y2="{{ $gridY }}" class="sb-performance-grid-line" />
                        @endfor

                        @foreach($performanceTrend as $day)
                            @php
                                $x = $perfChart['left'] + ($perfCount === 1 ? 0 : (($loop->index) * ($perfChart['plot_width'] / ($perfCount - 1))));
                                $barWidth = max(8, min(18, ($perfChart['plot_width'] / $perfCount) * 0.46));
                                $questionHeight = max($day['questions'] > 0 ? 8 : 2, ($day['questions'] / $perfMaxQuestions) * $perfChart['plot_height']);
                                $barX = $x - ($barWidth / 2);
                                $barY = $perfChart['base_y'] - $questionHeight;
                                $accuracyY = $perfChart['base_y'] - ((($day['accuracy'] ?? 0) / 100) * $perfChart['plot_height']);
                                $testY = $perfChart['base_y'] - (($day['tests'] / $perfMaxTests) * $perfChart['plot_height']);
                                $showLabel = $loop->first || $loop->last || (($loop->iteration - 1) % 5 === 0);
                            @endphp
                            <g>
                                <title>{{ $day['label'] }}: {{ $day['tests'] }} test, {{ $day['questions'] }} soru, {{ $day['correct'] }} dogru, {{ $day['wrong'] }} yanlis, {{ $day['accuracy'] !== null ? '%' . number_format($day['accuracy'], 1) : 'oran yok' }}</title>
                                <rect x="{{ round($barX, 1) }}" y="{{ round($barY, 1) }}" width="{{ round($barWidth, 1) }}" height="{{ round($questionHeight, 1) }}" rx="4" class="sb-performance-bar" />
                                <circle cx="{{ round($x, 1) }}" cy="{{ round($testY, 1) }}" r="{{ $day['tests'] > 0 ? 3.8 : 2.8 }}" class="sb-performance-point sb-performance-point--tests" />
                                <circle cx="{{ round($x, 1) }}" cy="{{ round($accuracyY, 1) }}" r="{{ $day['accuracy'] !== null ? 3.8 : 2.8 }}" class="sb-performance-point sb-performance-point--accuracy" />
                                @if($showLabel)
                                    <text x="{{ round($x, 1) }}" y="{{ $perfChart['height'] - 16 }}" text-anchor="middle" class="sb-performance-label">{{ $day['label'] }}</text>
                                @endif
                            </g>
                        @endforeach

                        <polyline points="{{ $testPoints }}" class="sb-performance-line sb-performance-line--tests" />
                        <polyline points="{{ $accuracyPoints }}" class="sb-performance-line sb-performance-line--accuracy" />
                        <line x1="{{ $perfChart['left'] }}" y1="{{ $perfChart['base_y'] }}" x2="{{ $perfChart['width'] - $perfChart['right'] }}" y2="{{ $perfChart['base_y'] }}" class="sb-performance-axis" />
                    </svg>
                </div>

                <div class="sb-performance-detail-grid">
                    @foreach($performanceTrend as $day)
                        @if($loop->last || $loop->remaining < 7)
                            <div>
                                <span>{{ $day['label'] }}</span>
                                <strong>{{ $day['questions'] }} soru</strong>
                                <small>{{ $day['tests'] }} test · {{ $day['accuracy'] !== null ? '%' . number_format($day['accuracy'], 1) : '-' }} dogru</small>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-xl-4 col-md-6">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--green">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                            <div class="text-muted small">Global Siran</div>
                            <div class="rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                                <i class="bi bi-trophy fs-5"></i>
                            </div>
                        </div>
                        @if($globalRace['my_rank'])
                            <div class="display-6 fw-bold">#{{ $globalRace['my_rank'] }}</div>
                            <div class="text-muted small">
                                {{ $globalRace['my_score'] }} puan
                                @if($globalRace['accuracy'] !== null)
                                    · %{{ number_format($globalRace['accuracy'], 1) }} dogruluk
                                @endif
                            </div>
                        @else
                            <div class="h4 fw-bold mb-2">Listeye gir</div>
                            <div class="text-muted small">Siralamaya girmek icin yeterli tamamlanmis test yok.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--red">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                            <div class="text-muted small">Yakin Rakip</div>
                            <div class="rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                                <i class="bi bi-bullseye fs-5"></i>
                            </div>
                        </div>
                        @if($globalRace['above'])
                            <div class="h4 fw-bold mb-1">{{ $globalRace['above']->user?->name ?? 'Rakip' }}</div>
                            <div class="text-muted small">
                                Bir ust siraya cikmak icin <strong>{{ $globalRace['points_to_next'] }}</strong> puan gerekiyor.
                            </div>
                        @elseif($globalRace['my_rank'] === 1)
                            <div class="h4 fw-bold mb-1">Zirvedesin</div>
                            <div class="text-muted small">
                                Alttaki rakiple farki acmak icin yeni test coz.
                            </div>
                        @else
                            <div class="h4 fw-bold mb-1">Ilk hedef: liste</div>
                            <div class="text-muted small">
                                Bir test daha tamamlayarak rekabete dahil ol.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-12">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--blue">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                            <div class="text-muted small">Bu Hafta</div>
                            <div class="rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                                <i class="bi bi-graph-up-arrow fs-5"></i>
                            </div>
                        </div>
                        <div class="display-6 fw-bold">{{ $weeklyStats['score_total'] }}</div>
                        <div class="text-muted small">
                            {{ $weeklyStats['test_count'] }} test
                            @if($weeklyStats['accuracy'] !== null)
                                · %{{ number_format($weeklyStats['accuracy'], 1) }} dogruluk
                            @else
                                · henuz tamamlanan test yok
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-lg-7">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--gold">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                    <i class="bi {{ $dailyGoal['icon'] }} fs-4"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">{{ $dailyGoal['title'] }}</div>
                                    <div class="h5 fw-bold mb-1">{{ $dailyGoal['text'] }}</div>
                                    @if($globalRace['above'])
                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between small text-muted mb-1">
                                                <span>{{ $globalRace['above']->user?->name ?? 'Rakip' }} hedefine ilerleme</span>
                                                <span>%{{ $globalRace['progress_to_next'] }}</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $globalRace['progress_to_next'] }}%;" aria-valuenow="{{ $globalRace['progress_to_next'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ $dailyGoal['url'] }}" class="btn btn-primary flex-shrink-0">
                                <i class="bi bi-play-fill me-1"></i> {{ $dailyGoal['button'] }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--violet">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="text-muted small">Zirve Panosu</div>
                                <div class="h5 fw-bold mb-0">Ilk 3</div>
                            </div>
                            <div class="rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                                <i class="bi bi-award fs-5"></i>
                            </div>
                        </div>

                        @if($topGlobalRows->isEmpty())
                            <div class="text-muted small">Zirve panosu icin henuz yeterli sonuc yok.</div>
                        @else
                            <div class="vstack gap-2">
                                @foreach($topGlobalRows as $row)
                                    <div class="d-flex align-items-center justify-content-between border rounded px-3 py-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge text-bg-{{ $row->rank === 1 ? 'warning' : ($row->rank === 2 ? 'secondary' : 'info') }}">#{{ $row->rank }}</span>
                                            <span class="fw-semibold">{{ $row->user?->name ?? 'Kullanici' }}</span>
                                        </div>
                                        <span class="text-muted small">{{ $row->score }} puan</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($bestSubjectRace)
            <div class="card mt-4 sb-dashboard-card sb-dashboard-card--brand">
                <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="bi bi-mortarboard fs-4"></i>
                        </div>
                        <div>
                            <div class="text-muted small">En iyi ders yarisin</div>
                            <div class="h5 fw-bold mb-1">
                                {{ $bestSubjectRace['subject']?->name }} · #{{ $bestSubjectRace['rank'] }}
                            </div>
                            <div class="text-muted small">
                                {{ $bestSubjectRace['score'] }} puan
                                @if($bestSubjectRace['accuracy'] !== null)
                                    · %{{ number_format($bestSubjectRace['accuracy'], 1) }} dogruluk
                                @endif
                                @if($bestSubjectRace['points_to_next'] > 0)
                                    · Bir ust sira icin {{ $bestSubjectRace['points_to_next'] }} puan
                                @else
                                    · Bu derste zirvedesin
                                @endif
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('leaderboard.index', ['subject_id' => $bestSubjectRace['subject']?->id]) }}" class="btn btn-outline-primary">
                        <i class="bi bi-list-ol me-1"></i> Ders Siralamasini Gor
                    </a>
                </div>
            </div>
        @endif

        <div class="row g-4 mt-1">
            <div class="col-lg-6">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--red">
                    <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded bg-danger-subtle text-danger d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="bi bi-crosshair fs-4"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Zayif Ders Onerisi</div>
                                @if($weaknessSubject)
                                    <div class="h5 fw-bold mb-1">{{ $weaknessSubject['subject']->name }}</div>
                                    <div class="text-muted small">{{ $weaknessSubject['question_count'] }} soru · {{ $weaknessSubject['wrong_total'] }} yanlis kaydi</div>
                                @else
                                    <div class="h5 fw-bold mb-1">Henuz netlesmedi</div>
                                    <div class="text-muted small">Test cozdükce zayif dersin burada belirecek.</div>
                                @endif
                            </div>
                        </div>
                        @if($weaknessSubject)
                            <a href="{{ route('subjects.index', ['subject_id' => $weaknessSubject['subject']->id, 'mode' => 'WEAKNESSES']) }}" class="btn btn-outline-primary">Calis</a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--blue">
                    <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded bg-info-subtle text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="bi bi-chat-square-text fs-4"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Itiraz Durumu</div>
                                <div class="h5 fw-bold mb-1">{{ $reportSummary['pending'] }} bekleyen · {{ $reportSummary['approved'] }} kabul</div>
                                <div class="text-muted small">{{ $reportSummary['rejected'] }} reddedilen itiraz bulunuyor.</div>
                            </div>
                        </div>
                        <a href="{{ route('questions.reports') }}" class="btn btn-outline-primary">Itirazlarim</a>
                    </div>
                </div>
            </div>
        </div>

        <div id="son-testler" class="card mt-4 sb-dashboard-card sb-dashboard-card--neutral">
            <div class="card-header bg-white d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <div>
                    <h2 class="h5 fw-bold mb-1"><i class="bi bi-clock-history me-2 text-primary"></i>Son Testler</h2>
                    <div class="text-muted small">Tamamlanan testleri 10'ar kayit halinde inceleyin.</div>
                </div>
                @if($recentTests->total() > 0)
                    <span class="badge text-bg-light text-dark border">{{ $recentTests->total() }} test</span>
                @endif
            </div>
            <div class="card-body">
                @if($recentTests->isEmpty())
                    <div class="text-muted">Henuz tamamlanmis test bulunmuyor.</div>
                @else
                    <div class="sb-recent-test-list">
                        @foreach($recentTests as $recentTest)
                            @php($accuracy = $recentTest->question_count > 0 ? round(($recentTest->correct_count / $recentTest->question_count) * 100, 1) : null)
                            <div class="sb-recent-test-row">
                                <div class="sb-recent-test-row__main">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <span class="fw-semibold">{{ $recentTest->subject?->name }}</span>
                                        <span class="badge text-bg-primary">{{ $recentTest->score }}/100</span>
                                        @if($accuracy !== null)
                                            <span class="badge text-bg-success">%{{ number_format($accuracy, 1) }}</span>
                                        @endif
                                    </div>
                                    <div class="text-muted small">
                                        Puan: {{ $recentTest->score }}/100 · Dogru: {{ $recentTest->correct_count }} · Yanlis: {{ $recentTest->wrong_count }} · Bos: {{ $recentTest->blank_count }}
                                    </div>
                                </div>
                                <a href="{{ route('tests.review', $recentTest) }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-search me-1"></i> Incele
                                </a>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $recentTests->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('click', async (event) => {
            const link = event.target.closest('#son-testler .pagination a');

            if (! link) {
                return;
            }

            event.preventDefault();

            const section = document.getElementById('son-testler');
            section.setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(link.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (! response.ok) {
                    window.location.href = link.href;
                    return;
                }

                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const nextSection = doc.getElementById('son-testler');

                if (! nextSection) {
                    window.location.href = link.href;
                    return;
                }

                section.replaceWith(nextSection);
                window.history.pushState({}, '', link.href);
            } catch (error) {
                window.location.href = link.href;
            }
        });
    </script>
</x-app-layout>
