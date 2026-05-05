@extends('layouts.admin', ['pageTitle' => 'Dashboard', 'title' => 'Yonetim Paneli'])

@section('content')
    @php
        $totalActionCount = $actionCenter['pending_reports']
            + $actionCenter['pending_submissions']
            + $actionCenter['imports_pending']
            + $actionCenter['archive_expiring'];

        $priorityCards = [
            ['label' => 'Bekleyen Itiraz', 'value' => $actionCenter['pending_reports'], 'hint' => 'Cevap ve sik kontrolu bekliyor', 'url' => route('admin.reports.index'), 'tone' => 'danger', 'icon' => 'bi-chat-square-text'],
            ['label' => 'Bekleyen Oneri', 'value' => $actionCenter['pending_submissions'], 'hint' => 'Kullanici sorulari moderasyonda', 'url' => route('admin.submissions.pending'), 'tone' => 'warning', 'icon' => 'bi-lightbulb'],
            ['label' => 'Import Onizleme', 'value' => $actionCenter['imports_pending'], 'hint' => 'CSV import onayi bekliyor', 'url' => route('admin.imports.index'), 'tone' => 'primary', 'icon' => 'bi-upload'],
            ['label' => '24 Saatte Silinecek', 'value' => $actionCenter['archive_expiring'], 'hint' => 'Arsivde kritik kayit', 'url' => route('admin.archive.index'), 'tone' => 'dark', 'icon' => 'bi-archive'],
        ];

        $systemCards = [
            ['label' => 'Aktif Soru', 'value' => $stats['active_questions'], 'hint' => 'Yayindaki soru havuzu', 'url' => route('admin.questions.index', ['status' => 'active']), 'icon' => 'bi-question-circle'],
            ['label' => 'Taslak Soru', 'value' => $stats['draft_questions'], 'hint' => 'Yayina hazirlanacak', 'url' => route('admin.questions.index', ['status' => 'draft']), 'icon' => 'bi-pencil-square'],
            ['label' => 'Ders', 'value' => $stats['subjects'], 'hint' => 'Aktif ders basligi', 'url' => route('admin.subjects.index'), 'icon' => 'bi-grid-3x3-gap'],
            ['label' => 'Kullanici', 'value' => $stats['users'], 'hint' => 'Kayitli hesap', 'url' => auth()->user()->isAdmin() ? route('admin.users.index') : null, 'icon' => 'bi-people'],
        ];

        $todayCards = [
            ['label' => 'Bugunku Test', 'value' => $todayStats['tests_started'], 'hint' => $todayStats['tests_finished'] . ' test tamamlandi', 'icon' => 'bi-play-circle'],
            ['label' => 'Bugunku Ortalama', 'value' => $todayStats['avg_score'] ?: 0, 'hint' => 'Tamamlanan test puani', 'icon' => 'bi-speedometer2'],
            ['label' => 'Bu Hafta Test', 'value' => $weekStats['tests_finished'], 'hint' => 'Haftalik tamamlanan', 'icon' => 'bi-graph-up-arrow'],
            ['label' => 'Yeni Kullanici', 'value' => $todayStats['new_users'], 'hint' => 'Bugun kayit olan', 'icon' => 'bi-person-plus'],
        ];

        $maxTests = max(1, collect($operationTrend)->max('tests'));
        $maxQuestions = max(1, collect($operationTrend)->max('questions'));
        $trendTotals = [
            'tests' => collect($operationTrend)->sum('tests'),
            'questions' => collect($operationTrend)->sum('questions'),
            'active_days' => collect($operationTrend)->filter(fn ($day) => $day['tests'] > 0 || $day['questions'] > 0)->count(),
        ];
        $chart = [
            'width' => 900,
            'height' => 300,
            'left' => 48,
            'right' => 24,
            'top' => 24,
            'bottom' => 44,
        ];
        $chart['plot_width'] = $chart['width'] - $chart['left'] - $chart['right'];
        $chart['plot_height'] = $chart['height'] - $chart['top'] - $chart['bottom'];
        $chart['base_y'] = $chart['height'] - $chart['bottom'];
        $trendCount = max(1, count($operationTrend));
        $linePoints = collect($operationTrend)->map(function ($day, $index) use ($chart, $trendCount, $maxTests) {
            $x = $chart['left'] + ($trendCount === 1 ? 0 : ($index * ($chart['plot_width'] / ($trendCount - 1))));
            $y = $chart['base_y'] - (($day['tests'] / $maxTests) * $chart['plot_height']);

            return round($x, 1) . ',' . round($y, 1);
        })->implode(' ');
    @endphp

    <section class="admin-command-hero">
        <div>
            <p class="eyebrow">Operasyon Merkezi</p>
            <h1>Tekrar hos geldiniz</h1>
            <p>Bugun once neye bakmaniz gerektigini, test hareketini, kalite risklerini ve son islemleri tek ekranda takip edin.</p>
        </div>
        <div class="admin-command-score">
            <span>Aksiyon Gerekenler</span>
            <strong>{{ number_format($totalActionCount) }}</strong>
            <small>itiraz, oneri, import ve arsiv riski</small>
        </div>
    </section>

    <section class="admin-priority-grid" aria-label="Aksiyon gerekenler">
        @foreach($priorityCards as $card)
            <a href="{{ $card['url'] }}" class="admin-priority-card admin-priority-card--{{ $card['tone'] }}">
                <div class="admin-priority-card__icon"><i class="bi {{ $card['icon'] }}"></i></div>
                <div>
                    <span>{{ $card['label'] }}</span>
                    <strong>{{ number_format($card['value']) }}</strong>
                    <p>{{ $card['hint'] }}</p>
                </div>
            </a>
        @endforeach
    </section>

    <section class="admin-ops-grid">
        <article class="admin-panel admin-panel--large">
            <div class="admin-panel__head">
                <div>
                    <h2>30 Gunluk Hareket</h2>
                    <p>Gunluk tamamlanan test ve cozulen soru sayisini izleyin.</p>
                </div>
            </div>
            <div class="admin-panel__content admin-chart-panel">
                <div class="admin-chart-toolbar">
                    <div class="admin-chart-legend">
                        <span><i class="admin-dot admin-dot--tests"></i> Cozulen Test</span>
                        <span><i class="admin-dot admin-dot--questions"></i> Cozulen Soru</span>
                    </div>
                    <div class="admin-chart-summary">
                        <span><strong>{{ number_format($trendTotals['tests']) }}</strong> test</span>
                        <span><strong>{{ number_format($trendTotals['questions']) }}</strong> soru</span>
                        <span><strong>{{ number_format($trendTotals['active_days']) }}</strong> aktif gun</span>
                    </div>
                </div>
                <div class="admin-combo-chart-wrap" aria-label="30 gunluk test ve soru grafigi">
                    <svg class="admin-combo-chart" viewBox="0 0 {{ $chart['width'] }} {{ $chart['height'] }}" role="img" aria-labelledby="admin-combo-title admin-combo-desc">
                        <title id="admin-combo-title">30 gunluk test ve soru grafigi</title>
                        <desc id="admin-combo-desc">Yesil kolonlar cozulen soru sayisini, mavi cizgi tamamlanan test sayisini gosterir.</desc>

                        @for($i = 0; $i <= 4; $i++)
                            @php
                                $gridY = $chart['top'] + ($i * ($chart['plot_height'] / 4));
                            @endphp
                            <line x1="{{ $chart['left'] }}" y1="{{ $gridY }}" x2="{{ $chart['width'] - $chart['right'] }}" y2="{{ $gridY }}" class="admin-chart-grid-line" />
                        @endfor

                        @foreach($operationTrend as $day)
                            @php
                                $x = $chart['left'] + ($trendCount === 1 ? 0 : (($loop->index) * ($chart['plot_width'] / ($trendCount - 1))));
                                $barWidth = max(8, min(18, ($chart['plot_width'] / $trendCount) * 0.46));
                                $questionHeight = max($day['questions'] > 0 ? 8 : 2, ($day['questions'] / $maxQuestions) * $chart['plot_height']);
                                $barX = $x - ($barWidth / 2);
                                $barY = $chart['base_y'] - $questionHeight;
                                $testY = $chart['base_y'] - (($day['tests'] / $maxTests) * $chart['plot_height']);
                                $showLabel = $loop->first || $loop->last || (($loop->iteration - 1) % 5 === 0);
                            @endphp
                            <g>
                                <title>{{ $day['label'] }}: {{ $day['tests'] }} test, {{ $day['questions'] }} soru</title>
                                <rect x="{{ round($barX, 1) }}" y="{{ round($barY, 1) }}" width="{{ round($barWidth, 1) }}" height="{{ round($questionHeight, 1) }}" rx="4" class="admin-combo-bar" />
                                <circle cx="{{ round($x, 1) }}" cy="{{ round($testY, 1) }}" r="{{ $day['tests'] > 0 ? 4 : 3 }}" class="admin-combo-point" />
                                @if($showLabel)
                                    <text x="{{ round($x, 1) }}" y="{{ $chart['height'] - 16 }}" text-anchor="middle" class="admin-combo-label">{{ $day['label'] }}</text>
                                @endif
                            </g>
                        @endforeach

                        <polyline points="{{ $linePoints }}" class="admin-combo-line" />
                        <line x1="{{ $chart['left'] }}" y1="{{ $chart['base_y'] }}" x2="{{ $chart['width'] - $chart['right'] }}" y2="{{ $chart['base_y'] }}" class="admin-chart-axis" />
                    </svg>
                </div>
                <div class="admin-chart-detail-grid">
                    @foreach($operationTrend as $day)
                        @if($loop->last || $loop->remaining < 7)
                            <div>
                                <span>{{ $day['label'] }}</span>
                                <strong>{{ $day['tests'] }}</strong>
                                <small>{{ $day['questions'] }} soru</small>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </article>

        <aside class="admin-panel admin-panel--compact">
            <div class="admin-panel__head">
                <h2>Bugun</h2>
                <p>Gunun canli ozeti.</p>
            </div>
            <div class="admin-today-grid">
                @foreach($todayCards as $card)
                    <div class="admin-today-card">
                        <i class="bi {{ $card['icon'] }}"></i>
                        <span>{{ $card['label'] }}</span>
                        <strong>{{ number_format((float) $card['value'], is_float($card['value']) && floor($card['value']) != $card['value'] ? 1 : 0) }}</strong>
                        <small>{{ $card['hint'] }}</small>
                    </div>
                @endforeach
            </div>
        </aside>
    </section>

    <section class="admin-system-grid">
        @foreach($systemCards as $card)
            @if($card['url'])
                <a href="{{ $card['url'] }}" class="admin-system-card">
            @else
                <div class="admin-system-card">
            @endif
                <i class="bi {{ $card['icon'] }}"></i>
                <div>
                    <span>{{ $card['label'] }}</span>
                    <strong>{{ number_format($card['value']) }}</strong>
                    <p>{{ $card['hint'] }}</p>
                </div>
            @if($card['url'])
                </a>
            @else
                </div>
            @endif
        @endforeach
    </section>

    <section class="admin-lower-grid">
        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h2>Son Itirazlar</h2>
                    <p>Kullanici bildirimleri ve cevap onerileri.</p>
                </div>
                <a href="{{ route('admin.reports.index') }}" class="admin-link">Tumunu ac</a>
            </div>
            <div class="admin-feed">
                @forelse($recentReports as $report)
                    <div class="admin-feed-row">
                        <div>
                            <strong>{{ $report->user?->name ?? 'Kullanici' }}</strong>
                            <p>{{ str($report->question?->question_text ?? '-')->limit(90) }}</p>
                            <small>{{ $report->question?->subject?->name ?? '-' }} · Oneri: {{ $report->suggested_correct_option ?? '-' }}</small>
                        </div>
                        <span class="admin-status admin-status--{{ $report->status }}">{{ $report->status_label }}</span>
                    </div>
                @empty
                    <p class="admin-empty-state">Itiraz kaydi yok.</p>
                @endforelse
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h2>Son Oneriler</h2>
                    <p>Moderator bekleyen ve yeni gelen sorular.</p>
                </div>
                <a href="{{ route('admin.submissions.pending') }}" class="admin-link">Incele</a>
            </div>
            <div class="admin-feed">
                @forelse($recentSubmissions as $submission)
                    <div class="admin-feed-row">
                        <div>
                            <strong>{{ $submission->user?->name ?? 'Kullanici' }}</strong>
                            <p>{{ str($submission->payload_json['question_text'] ?? '-')->limit(90) }}</p>
                            <small>{{ $submission->subject?->name ?? '-' }} · {{ $submission->created_at->format('d.m.Y H:i') }}</small>
                        </div>
                        <span class="admin-status admin-status--{{ $submission->status }}">{{ $submission->status }}</span>
                    </div>
                @empty
                    <p class="admin-empty-state">Oneri kaydi yok.</p>
                @endforelse
            </div>
        </article>
    </section>

@endsection
