@extends('layouts.admin', ['pageTitle' => 'Arama', 'title' => 'Arama'])

@section('content')
    <section class="admin-search-panel">
        <div class="admin-search-panel__head">
            <div>
                <h1>Arama</h1>
                <p>Yonetim panelinde ders, soru, itiraz ve kullanici kayitlarini bulun.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.search') }}" class="row g-3 align-items-end mb-4">
            <div class="col-md-8">
                <label for="q" class="form-label">Arama metni</label>
                <input type="search" id="q" name="q" class="form-control" value="{{ $query }}" placeholder="Soru metni, ders adi, e-posta veya itiraz notu">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Ara</button>
            </div>
        </form>

        @if($query === '')
            <div class="admin-search-empty-state">Arama yapmak icin bir metin girin.</div>
        @else
            <div class="admin-search-grid">
                <div class="admin-search-column">
                    <h2>Dersler</h2>
                    @forelse($subjects as $subject)
                        <a href="{{ route('admin.subjects.edit', $subject) }}" class="admin-search-result-card">
                            <strong>{{ $subject->name }}</strong>
                            <span>{{ $subject->slug }}</span>
                        </a>
                    @empty
                        <div class="admin-search-empty-state">Sonuc yok.</div>
                    @endforelse
                </div>

                <div class="admin-search-column">
                    <h2>Sorular</h2>
                    @forelse($questions as $question)
                        <a href="{{ route('admin.questions.edit', $question) }}" class="admin-search-result-card">
                            <strong>{{ $question->subject?->name ?? '-' }}</strong>
                            <span>{{ str($question->question_text)->limit(100) }}</span>
                        </a>
                    @empty
                        <div class="admin-search-empty-state">Sonuc yok.</div>
                    @endforelse
                </div>

                <div class="admin-search-column">
                    <h2>Itirazlar</h2>
                    @forelse($reports as $report)
                        <a href="{{ route('admin.reports.index', ['status' => $report->status]) }}" class="admin-search-result-card">
                            <strong>{{ $report->user?->name ?? '-' }} - {{ $report->status }}</strong>
                            <span>{{ str($report->question?->question_text ?? $report->note)->limit(100) }}</span>
                        </a>
                    @empty
                        <div class="admin-search-empty-state">Sonuc yok.</div>
                    @endforelse
                </div>

                <div class="admin-search-column">
                    <h2>Testler</h2>
                    @forelse($tests as $test)
                        <div class="admin-search-result-card">
                            <strong>{{ $test->user?->name ?? '-' }} - {{ $test->subject?->name ?? '-' }}</strong>
                            <span>{{ $test->created_at?->format('d.m.Y H:i') }} - {{ $test->status }} - {{ $test->score ?? '-' }} puan</span>
                        </div>
                    @empty
                        <div class="admin-search-empty-state">Sonuc yok.</div>
                    @endforelse
                </div>

                @if(auth()->user()->isAdmin())
                    <div class="admin-search-column">
                        <h2>Kullanicilar</h2>
                        @forelse($users as $user)
                            <a href="{{ route('admin.users.index', ['search' => $user->email]) }}" class="admin-search-result-card">
                                <strong>{{ $user->name }}</strong>
                                <span>{{ $user->email }}</span>
                            </a>
                        @empty
                            <div class="admin-search-empty-state">Sonuc yok.</div>
                        @endforelse
                    </div>
                @endif
            </div>
        @endif
    </section>
@endsection
