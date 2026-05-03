<x-app-layout>
    <x-slot name="header">
        <h1 class="sb-page-title">Leaderboard</h1>
    </x-slot>

    <div class="container sb-page">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 fw-bold mb-0">Global Sıralama</h2>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-4">
                    @if($globalSnapshotAt)
                        Son guncelleme: {{ \Illuminate\Support\Carbon::parse($globalSnapshotAt)->format('d.m.Y H:i') }}
                    @else
                        Henuz siralama hesaplanmamis. Tamamladiginiz bir testten sonra liste otomatik guncellenir; yine de bos kalirsa <code class="user-select-all">php artisan leaderboard:snapshot</code> calistirin veya zamanlayiciyi acin.
                    @endif
                </p>
                <p class="text-muted small mb-3">
                    Siralamaya girmek icin son {{ $leaderboardWindowDays }} gunde, her biri 20 soruluk ve tamamlanmis en az <strong>{{ $leaderboardMinTests }}</strong> testiniz olmalidir (puanlar bu testlerin toplamidir). Tek tek soru cozmek veya yeterli sayida tamamlanmis test olmamasi listeye cikmanizi engeller.
                </p>

                @if($myGlobalRank)
                    <div class="alert alert-info">
                        Senin global siran: <strong>#{{ $myGlobalRank->rank }}</strong> ({{ $myGlobalRank->score }} puan).
                        Siralamaya giren testlerde toplam <strong>{{ $myGlobalRank->questions_total }}</strong> soru,
                        <strong>{{ $myGlobalRank->correct_total }}</strong> dogru,
                        <strong>{{ $myGlobalRank->wrong_total }}</strong> yanlis.
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sira</th>
                                <th>Kullanici</th>
                                <th>Puan</th>
                                <th class="text-end">Cozulen soru</th>
                                <th class="text-end">Dogru</th>
                                <th class="text-end">Yanlis</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($globalRows as $row)
                                <tr>
                                    <td>#{{ $row->rank }}</td>
                                    <td>{{ $row->user?->name ?? 'Kullanici' }}</td>
                                    <td>{{ $row->score }}</td>
                                    <td class="text-end">{{ $row->questions_total }}</td>
                                    <td class="text-end">{{ $row->correct_total }}</td>
                                    <td class="text-end">{{ $row->wrong_total }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted">
                                        Gosterilecek kayit yok. En az {{ $leaderboardMinTests }} tamamlanmis (20 soruluk) test sartini saglayan kullanici yoksa tablo bos kalir; admin ayarindan bu esigi dusurebilirsiniz.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <h2 class="h5 fw-bold mb-0">Ders Bazlı Sıralama</h2>
                <form method="GET" action="{{ route('leaderboard.index') }}" class="d-flex align-items-center gap-2">
                    <label for="subject_id" class="form-label small text-muted mb-0">Ders</label>
                    <select id="subject_id" name="subject_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected($selectedSubjectId === $subject->id)>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-4">
                    @if($subjectSnapshotAt)
                        Son guncelleme: {{ \Illuminate\Support\Carbon::parse($subjectSnapshotAt)->format('d.m.Y H:i') }}
                    @else
                        Bu ders icin henuz siralama yok. Test bitince otomatik hesaplanir; gerekiyorsa <code class="user-select-all">php artisan leaderboard:snapshot</code> calistirin.
                    @endif
                </p>

                @if($mySubjectRank)
                    <div class="alert alert-info">
                        Secili derste siran: <strong>#{{ $mySubjectRank->rank }}</strong> ({{ $mySubjectRank->score }} puan).
                        Bu derste siralamaya giren testlerde toplam <strong>{{ $mySubjectRank->questions_total }}</strong> soru,
                        <strong>{{ $mySubjectRank->correct_total }}</strong> dogru,
                        <strong>{{ $mySubjectRank->wrong_total }}</strong> yanlis.
                    </div>
                @endif

                @if($selectedSubjectId)
                    <div class="mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div class="text-muted small">
                            Bu derste en çok yanlış verdiğin soru kaydı: <strong>{{ $wrongQuestionCount }}</strong>.
                            @if($wrongQuestionCount === 0)
                                Takıldıklarım testini başlatmak için önce bazı soruları yanlış yanıtlamanız gerekiyor.
                            @endif
                        </div>
                        @if($wrongQuestionCount > 0)
                            <a href="{{ route('tests.create', ['subject_id' => $selectedSubjectId, 'mode' => 'WEAKNESSES']) }}"
                               class="btn btn-primary">
                                Takıldıklarım Testi Başlat
                            </a>
                        @else
                            <span class="btn btn-primary disabled" tabindex="-1" aria-disabled="true">
                                Takıldıklarım Testi Başlat
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
                                <th class="text-end">Cozulen soru</th>
                                <th class="text-end">Dogru</th>
                                <th class="text-end">Yanlis</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subjectRows as $row)
                                <tr>
                                    <td>#{{ $row->rank }}</td>
                                    <td>{{ $row->user?->name ?? 'Kullanici' }}</td>
                                    <td>{{ $row->score }}</td>
                                    <td class="text-end">{{ $row->questions_total }}</td>
                                    <td class="text-end">{{ $row->correct_total }}</td>
                                    <td class="text-end">{{ $row->wrong_total }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted">
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
