@extends('layouts.admin', ['pageTitle' => 'Soru Surumleri', 'title' => 'Soru Surumleri'])

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h3 mb-1">Soru Surumleri</h1>
            <p class="text-muted mb-0">{{ $question->subject?->name }} icindeki soru icin geri alma gecmisi.</p>
        </div>
        <a href="{{ route('admin.questions.edit', $question) }}" class="btn btn-outline-secondary">Soruya Don</a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="fw-semibold mb-2">Guncel Soru v{{ $question->current_version }}</div>
            <div>{{ $question->question_text }}</div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Surum</th>
                            <th>Tarih</th>
                            <th>Degistiren</th>
                            <th>Ozet</th>
                            <th class="text-end">Islem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($versions as $version)
                            <tr>
                                <td>v{{ $version->version_no }}</td>
                                <td>{{ $version->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $version->changedBy?->name ?? 'Sistem' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($version->payload_json['question_text'] ?? '', 100) }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.questions.versions.rollback', [$question, $version]) }}" class="d-inline-flex gap-2">
                                        @csrf
                                        <input type="text" name="reason" class="form-control form-control-sm sb-width-220" placeholder="Gerekce" required>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu surume geri almak istediginize emin misiniz?')">Geri Al</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">Bu soru icin henuz surum kaydi yok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $versions->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
