<x-app-layout>
    <x-slot name="header">
        <h1 class="sb-page-title">Panel</h1>
    </x-slot>

    <div class="container sb-page">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h5 fw-bold mb-2">Hos geldiniz</h2>
                        <p class="text-muted mb-4">Ders secip yeni test baslatabilir veya onceki sonuclarinizi inceleyebilirsiniz.</p>

                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-primary" href="{{ route('subjects.index') }}">Dersleri Gor</a>
                            <a class="btn btn-outline-primary" href="{{ route('tests.create') }}">Test Baslat</a>
                            @if($activeTest)
                                <a class="btn btn-warning" href="{{ route('tests.show', $activeTest) }}">Aktif Teste Devam Et</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Toplam Puan</div>
                        <div class="display-6 fw-bold">{{ $totalScore }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-white">
                <h2 class="h5 fw-bold mb-0">Son Testler</h2>
            </div>
            <div class="card-body">
                @if($recentTests->isEmpty())
                    <div class="text-muted">Henuz tamamlanmis test bulunmuyor.</div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($recentTests as $recentTest)
                            <div class="list-group-item px-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                <div>
                                    <div class="fw-semibold">{{ $recentTest->subject?->name }}</div>
                                    <div class="text-muted small">
                                        Puan: {{ $recentTest->score }}/100 · Dogru: {{ $recentTest->correct_count }} · Yanlis: {{ $recentTest->wrong_count }} · Bos: {{ $recentTest->blank_count }}
                                    </div>
                                </div>
                                <a href="{{ route('tests.review', $recentTest) }}" class="btn btn-outline-secondary btn-sm">Incele</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
