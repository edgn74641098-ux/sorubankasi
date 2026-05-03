@extends('layouts.admin', ['pageTitle' => 'Audit Log', 'title' => 'Audit Log'])

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Audit Log</h1>
        <p class="text-muted mb-0">Kritik sistem islemlerini filtreleyin ve inceleyin.</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="action" class="form-label">Aksiyon</label>
                    <input type="text" name="action" id="action" class="form-control" value="{{ $filters['action'] }}">
                </div>
                <div class="col-md-4">
                    <label for="entity_type" class="form-label">Kaynak tipi</label>
                    <input type="text" name="entity_type" id="entity_type" class="form-control" value="{{ $filters['entity_type'] }}">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-outline-primary">Filtrele</button>
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary">Temizle</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Kullanici</th>
                            <th>Aksiyon</th>
                            <th>Kaynak</th>
                            <th>Gerekce</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $log->actor?->name ?? 'Sistem' }}</td>
                                <td><span class="badge text-bg-secondary">{{ $log->action }}</span></td>
                                <td>{{ $log->entity_type }} @if($log->entity_id)#{{ $log->entity_id }}@endif</td>
                                <td>{{ \Illuminate\Support\Str::limit($log->reason, 120) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">Kayit bulunamadi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
