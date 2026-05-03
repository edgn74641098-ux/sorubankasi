@extends('layouts.admin', ['pageTitle' => 'Kullanicilar', 'title' => 'Kullanicilar'])

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Kullanici Yonetimi</h1>
        <p class="text-muted mb-0">Kullanicilari arayin ve rollerini yonetin.</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="search" class="form-label">Ara</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Ad veya e-posta">
                </div>
                <div class="col-md-4">
                    <label for="role_id" class="form-label">Rol</label>
                    <select name="role_id" id="role_id" class="form-select">
                        <option value="">Tum roller</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected((string) $filters['role_id'] === (string) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-outline-primary">Filtrele</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Temizle</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Ad</th>
                            <th>E-posta</th>
                            <th>Rol</th>
                            <th>Test</th>
                            <th>Gonderim</th>
                            <th class="text-end">Islem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->role?->name }}</td>
                                <td>{{ $user->tests_count }}</td>
                                <td>{{ $user->submitted_questions_count }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.users.update-role', $user) }}" class="d-inline-flex gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role_id" class="form-select form-select-sm sb-width-130">
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" @selected($user->role_id === $role->id)>{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Kaydet</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted">Kullanici bulunamadi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $users->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
