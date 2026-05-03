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
                        Henuz snapshot uretilmemis.
                    @endif
                </p>

                @if($myGlobalRank)
                    <div class="alert alert-info">
                        Senin global siran: <strong>#{{ $myGlobalRank->rank }}</strong> ({{ $myGlobalRank->score }} puan)
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sira</th>
                                <th>Kullanici</th>
                                <th>Puan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($globalRows as $row)
                                <tr>
                                    <td>#{{ $row->rank }}</td>
                                    <td>{{ $row->user?->name ?? 'Kullanici' }}</td>
                                    <td>{{ $row->score }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted">Gosterilecek global veri yok.</td>
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
                        Secili ders icin henuz snapshot yok.
                    @endif
                </p>

                @if($mySubjectRank)
                    <div class="alert alert-info">
                        Secili derste siran: <strong>#{{ $mySubjectRank->rank }}</strong> ({{ $mySubjectRank->score }} puan)
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sira</th>
                                <th>Kullanici</th>
                                <th>Puan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subjectRows as $row)
                                <tr>
                                    <td>#{{ $row->rank }}</td>
                                    <td>{{ $row->user?->name ?? 'Kullanici' }}</td>
                                    <td>{{ $row->score }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted">Gosterilecek ders verisi yok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
