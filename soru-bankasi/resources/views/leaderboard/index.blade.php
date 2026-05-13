<x-app-layout>
    <x-slot name="header">
        <h1 class="sb-page-title">Leaderboard</h1>
    </x-slot>

    <div class="container sb-page">
        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--brand">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="text-muted small">Global Lider</div>
                            <div class="rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                <i class="bi bi-trophy fs-5"></i>
                            </div>
                        </div>
                        @if($globalLeader)
                            <div class="h4 fw-bold mb-1">{{ $globalLeader->user?->name ?? 'Kullanici' }}</div>
                            <div class="text-muted small">#1 · {{ $globalLeader->score }} puan</div>
                        @else
                            <div class="h5 fw-bold mb-1">Zirve bos</div>
                            <div class="text-muted small">Ilk siralama icin testleri tamamla.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--green">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="text-muted small">Senin Global Durumun</div>
                            <div class="rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                <i class="bi bi-person-check fs-5"></i>
                            </div>
                        </div>
                        @if($myGlobalRank)
                            <div class="display-6 fw-bold">#{{ $myGlobalRank->rank }}</div>
                            <div class="text-muted small">
                                {{ $myGlobalRank->score }} puan
                                @if($globalAccuracy !== null)
                                    · %{{ number_format($globalAccuracy, 1) }} dogruluk
                                @endif
                            </div>
                        @else
                            <div class="h5 fw-bold mb-1">Listeye gir</div>
                            <div class="text-muted small">Son {{ $leaderboardWindowDays }} gunde en az {{ $leaderboardMinTests }} test tamamla.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--gold">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="text-muted small">Sonraki Hedef</div>
                            <div class="rounded bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                <i class="bi bi-bullseye fs-5"></i>
                            </div>
                        </div>
                        @if($myGlobalRank && $globalPointsToNext > 0)
                            <div class="h4 fw-bold mb-1">{{ $globalPointsToNext }} puan</div>
                            <div class="text-muted small">Bir ust sirayi zorlamak icin gereken fark.</div>
                        @elseif($myGlobalRank && $globalPointsToNext === 0)
                            <div class="h4 fw-bold mb-1">Zirvedesin</div>
                            <div class="text-muted small">Yeni testle farki ac.</div>
                        @else
                            <div class="h4 fw-bold mb-1">Baslangic hedefi</div>
                            <div class="text-muted small">Siralama icin tamamlanmis test sayini artir.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--gold">
                    <div class="card-header bg-white fw-bold"><i class="bi bi-calendar-week me-2 text-warning"></i>Haftalik Liderler <span class="badge text-bg-warning ms-2">Ilk 5</span></div>
                    <div class="card-body">
                        @forelse($weeklyLeaders as $row)
                            <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                                <div class="fw-semibold">{{ $loop->iteration }}. {{ $row->name }}</div>
                                <div class="text-muted small">{{ (int) $row->score_total }} puan · {{ $row->test_count }} test</div>
                            </div>
                        @empty
                            <div class="text-muted small">Bu hafta henuz tamamlanmis test yok.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100 sb-dashboard-card sb-dashboard-card--green">
                    <div class="card-header bg-white fw-bold"><i class="bi bi-graph-up-arrow me-2 text-success"></i>En Iyi Form <span class="badge text-bg-success ms-2">Ilk 5</span></div>
                    <div class="card-body">
                        @forelse($mostImprovedRows as $row)
                            <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                                <div class="fw-semibold">{{ $loop->iteration }}. {{ $row->name }}</div>
                                <div class="text-muted small">%{{ number_format((float) $row->average_score, 1) }} ortalama · {{ $row->test_count }} test</div>
                            </div>
                        @empty
                            <div class="text-muted small">Form listesi icin en az iki test tamamlanmali.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4 sb-dashboard-card sb-dashboard-card--neutral">
            <div class="card-header bg-white d-flex align-items-center justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold mb-0"><i class="bi bi-globe2 me-2 text-primary"></i>Global Siralama</h2>
                    <div class="text-muted small mt-1">
                        @if($globalSnapshotAt)
                            Son guncelleme: {{ \Illuminate\Support\Carbon::parse($globalSnapshotAt)->format('d.m.Y H:i') }}
                        @else
                            Henuz siralama hesaplanmamis.
                        @endif
                    </div>
                </div>
                <span class="badge text-bg-primary">Ilk 20 · Son {{ $leaderboardWindowDays }} gun</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Siralamaya girmek icin her biri 20 soruluk ve tamamlanmis en az <strong>{{ $leaderboardMinTests }}</strong> test gerekir.
                </p>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sira</th>
                                <th>Kullanici</th>
                                <th>Puan</th>
                                <th class="text-end">Dogruluk</th>
                                <th class="text-end">Cozulen soru</th>
                                <th class="text-end">Dogru</th>
                                <th class="text-end">Yanlis</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($globalRows as $row)
                                @php($isMe = $row->user_id === auth()->id())
                                @php($accuracy = $row->questions_total > 0 ? round(($row->correct_total / $row->questions_total) * 100, 1) : null)
                                <tr class="{{ $isMe ? 'table-info' : '' }}">
                                    <td>
                                        <span class="badge text-bg-{{ $row->rank === 1 ? 'warning' : ($row->rank === 2 ? 'secondary' : ($row->rank === 3 ? 'info' : 'light')) }}">#{{ $row->rank }}</span>
                                    </td>
                                    <td>
                                        {{ $row->user?->name ?? 'Kullanici' }}
                                        @if($isMe)
                                            <span class="badge text-bg-primary ms-2">Sen</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ $row->score }}</td>
                                    <td class="text-end">{{ $accuracy !== null ? '%' . number_format($accuracy, 1) : '-' }}</td>
                                    <td class="text-end">{{ $row->questions_total }}</td>
                                    <td class="text-end">{{ $row->correct_total }}</td>
                                    <td class="text-end">{{ $row->wrong_total }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-muted">
                                        Gosterilecek kayit yok. Test tamamlandikca bu alan canlanacak.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card sb-dashboard-card sb-dashboard-card--brand">
            <div class="card-header bg-white d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold mb-0"><i class="bi bi-mortarboard me-2 text-primary"></i>Ders Bazli Siralama</h2>
                    <div class="text-muted small mt-1">
                        @if($subjectSnapshotAt)
                            Son guncelleme: {{ \Illuminate\Support\Carbon::parse($subjectSnapshotAt)->format('d.m.Y H:i') }}
                        @else
                            Bu ders icin henuz siralama yok.
                        @endif
                    </div>
                </div>
                <form method="GET" action="{{ route('leaderboard.index') }}" class="d-flex align-items-center gap-2">
                    <label for="subject_id" class="form-label small text-muted mb-0">Ders</label>
                    <select id="subject_id" name="subject_id" class="form-select form-select-sm" data-autosubmit onchange="this.form.submit()">
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected($selectedSubjectId === $subject->id)>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-lg-4">
                        <div class="border rounded p-3 bg-white h-100">
                            <div class="text-muted small">Ders lideri</div>
                            <div class="fw-bold">{{ $subjectLeader?->user?->name ?? 'Henuz yok' }}</div>
                            <div class="text-muted small">{{ $subjectLeader ? $subjectLeader->score . ' puan' : 'Testler tamamlandikca olusur' }}</div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="border rounded p-3 bg-white h-100">
                            <div class="text-muted small">Senin siran</div>
                            <div class="fw-bold">{{ $mySubjectRank ? '#' . $mySubjectRank->rank : 'Liste disi' }}</div>
                            <div class="text-muted small">
                                @if($mySubjectRank)
                                    {{ $mySubjectRank->score }} puan
                                    @if($subjectAccuracy !== null)
                                        · %{{ number_format($subjectAccuracy, 1) }} dogruluk
                                    @endif
                                @else
                                    Bu derste yeterli test yok
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="border rounded p-3 bg-white h-100">
                            <div class="text-muted small">Antrenman firsati</div>
                            <div class="fw-bold">{{ $wrongQuestionCount }} takildigin soru</div>
                            <div class="text-muted small">Zayif sorulari kapat, siralamayi zorla.</div>
                        </div>
                    </div>
                </div>

                @if($selectedSubjectId)
                    <div class="mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div class="text-muted small">
                            @if($mySubjectRank && $subjectPointsToNext > 0)
                                Bu derste bir ust sira icin <strong>{{ $subjectPointsToNext }}</strong> puan gerekiyor.
                            @elseif($mySubjectRank && $subjectPointsToNext === 0)
                                Bu derste zirvedesin; yeni testle farki acabilirsin.
                            @else
                                Bu derste siralamaya girmek icin test tamamla.
                            @endif
                        </div>
                        @if($wrongQuestionCount > 0)
                            <a href="{{ route('subjects.index', ['subject_id' => $selectedSubjectId, 'mode' => 'WEAKNESSES']) }}"
                               class="btn btn-primary">
                                <i class="bi bi-lightning-charge me-1"></i> Takildiklarim Testi Baslat
                            </a>
                        @else
                            <span class="btn btn-primary disabled" tabindex="-1" aria-disabled="true">
                                <i class="bi bi-lightning-charge me-1"></i> Takildiklarim Testi Baslat
                            </span>
                        @endif
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sira</th>
                                <th>Kullanici</th>
                                <th>Puan</th>
                                <th class="text-end">Dogruluk</th>
                                <th class="text-end">Cozulen soru</th>
                                <th class="text-end">Dogru</th>
                                <th class="text-end">Yanlis</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subjectRows as $row)
                                @php($isMe = $row->user_id === auth()->id())
                                @php($accuracy = $row->questions_total > 0 ? round(($row->correct_total / $row->questions_total) * 100, 1) : null)
                                <tr class="{{ $isMe ? 'table-info' : '' }}">
                                    <td><span class="badge text-bg-light">#{{ $row->rank }}</span></td>
                                    <td>
                                        {{ $row->user?->name ?? 'Kullanici' }}
                                        @if($isMe)
                                            <span class="badge text-bg-primary ms-2">Sen</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ $row->score }}</td>
                                    <td class="text-end">{{ $accuracy !== null ? '%' . number_format($accuracy, 1) : '-' }}</td>
                                    <td class="text-end">{{ $row->questions_total }}</td>
                                    <td class="text-end">{{ $row->correct_total }}</td>
                                    <td class="text-end">{{ $row->wrong_total }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-muted">
                                        Bu derste siralama icin son {{ $leaderboardWindowDays }} gunde en az {{ $leaderboardMinTests }} tamamlanmis test sartini saglayan kullanici yok.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
