@extends('layouts.admin', ['pageTitle' => 'Ders Düzenle', 'title' => 'Ders Düzenle'])

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Ders Düzenle</h1>
        <p class="text-muted mb-0">{{ $subject->name }} dersini güncelleyin.</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.subjects.update', $subject) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">Ders Adı</label>
                    <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $subject->name) }}" required maxlength="255">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="is_active" class="form-label">Durum</label>
                    <select name="is_active" id="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
                        <option value="1" @selected((string) old('is_active', (int) $subject->is_active) === '1')>Aktif</option>
                        <option value="0" @selected((string) old('is_active', (int) $subject->is_active) === '0')>Pasif</option>
                    </select>
                    @error('is_active')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                    <a href="{{ route('admin.subjects.index') }}" class="btn btn-outline-secondary">İptal</a>
                </div>
            </form>
        </div>
    </div>
@endsection