<x-app-layout>
    <x-slot name="header">
        <h1 class="sb-page-title">Dersler</h1>
    </x-slot>

    <div class="container sb-page">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif

        @if($activeTest)
            <div class="alert alert-warning d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>Devam eden bir testiniz var. Once onu tamamlayabilirsiniz.</div>
                <a href="{{ route('tests.show', $activeTest) }}" class="btn btn-warning">Aktif Teste Git</a>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                @if($subjects->isEmpty())
                    <div class="text-muted">Henuz aktif ders yok.</div>
                @else
                    <div class="row g-3">
                        @foreach($subjects as $subject)
                            <div class="col-md-6">
                                <div class="sb-stat-card h-100">
                                    <div class="d-flex align-items-start justify-content-between gap-3">
                                        <div>
                                            <h2 class="h5 fw-bold mb-1">{{ $subject->name }}</h2>
                                            <div class="text-muted small">Slug: {{ $subject->slug }}</div>
                                            <div class="text-muted small">Onayli soru sayisi: {{ $subject->approved_questions_count }}</div>
                                        </div>
                                        <span class="badge text-bg-light">{{ $subject->approved_questions_count }} soru</span>
                                    </div>
                                    <div class="mt-3">
                                        <a
                                            href="{{ route('tests.create', ['subject_id' => $subject->id]) }}"
                                            class="btn btn-primary {{ $subject->approved_questions_count < 20 ? 'disabled' : '' }}"
                                        >
                                            Test Baslat
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
