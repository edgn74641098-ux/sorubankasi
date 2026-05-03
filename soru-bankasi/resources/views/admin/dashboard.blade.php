@extends('layouts.admin', ['pageTitle' => 'Yonetim Paneli', 'title' => 'Yonetim Paneli'])

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Yonetim Paneli</h1>
        <p class="text-muted mb-0">Sistemin guncel operasyon durumunu buradan izleyin.</p>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            'Kullanici' => $stats['users'],
            'Ders' => $stats['subjects'],
            'Aktif Soru' => $stats['active_questions'],
            'Bekleyen Oneri' => $stats['pending_submissions'],
            'Bugunku Test' => $stats['tests_today'],
            'Onizleme Import' => $stats['imports_pending'],
        ] as $label => $value)
            <div class="col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="display-6 fw-semibold">{{ $value }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Son Testler</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Kullanici</th>
                                    <th>Ders</th>
                                    <th>Durum</th>
                                    <th>Puan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTests as $test)
                                    <tr>
                                        <td>{{ $test->user?->name }}</td>
                                        <td>{{ $test->subject?->name }}</td>
                                        <td><span class="badge text-bg-secondary">{{ $test->status }}</span></td>
                                        <td>{{ $test->score ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">Test kaydi yok.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Son Soru Onerileri</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Kullanici</th>
                                    <th>Ders</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSubmissions as $submission)
                                    <tr>
                                        <td>{{ $submission->user?->name }}</td>
                                        <td>{{ $submission->subject?->name }}</td>
                                        <td><span class="badge text-bg-secondary">{{ $submission->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted">Oneri kaydi yok.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
