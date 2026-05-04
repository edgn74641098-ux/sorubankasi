@extends('layouts.admin', ['pageTitle' => 'Ders Yonetimi', 'title' => 'Ders Yonetimi'])

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Dersler</h1>
            <p class="text-muted mb-0">Aktif ve pasif dersleri yonetin. Arsivlenen dersler ayri Arsiv bolumunde izlenir.</p>
        </div>
        <a href="{{ route('admin.subjects.create') }}" class="btn btn-primary">Yeni Ders Ekle</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.subjects.index') }}" class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="status" class="form-label">Durum Filtresi</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Tumu</option>
                        <option value="active" @selected($status === 'active')>Aktif</option>
                        <option value="inactive" @selected($status === 'inactive')>Pasif</option>
                    </select>
                </div>
                <div class="col-md-8 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-outline-primary">Filtrele</button>
                    <a href="{{ route('admin.subjects.index') }}" class="btn btn-outline-secondary">Sifirla</a>
                </div>
            </form>

            @if($subjects->isEmpty())
                <div class="alert alert-info mb-0">Kayitli ders bulunamadi.</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Ders</th>
                                <th>Slug</th>
                                <th>Soru Sayisi</th>
                                <th>Durum</th>
                                <th class="text-end">Islemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subjects as $subject)
                                <tr>
                                    <td class="fw-semibold">{{ $subject->name }}</td>
                                    <td><code>{{ $subject->slug }}</code></td>
                                    <td>{{ $subject->questions_count }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $subject->is_active ? 'success' : 'secondary' }}">
                                            {{ $subject->is_active ? 'Aktif' : 'Pasif' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.subjects.edit', $subject) }}" class="btn btn-sm btn-outline-primary">Duzenle</a>

                                        @can('delete', $subject)
                                            <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu dersi arsive tasimak istediginize emin misiniz? Dersin sorulari da arsivlenir.')">Arsive Tasi</button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $subjects->links('pagination::bootstrap-5') }}
            @endif
        </div>
    </div>
@endsection
