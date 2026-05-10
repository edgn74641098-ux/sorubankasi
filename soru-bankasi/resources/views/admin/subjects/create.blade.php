@extends('layouts.admin', ['pageTitle' => 'Yeni Ders', 'title' => 'Yeni Ders'])

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Yeni Ders Ekle</h1>
        <p class="text-muted mb-0">Yeni bir ders kaydi olusturun.</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.subjects.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Ders Adi</label>
                    <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="255">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="term" class="form-label">Donem</label>
                    <select id="term" name="term" class="form-select @error('term') is-invalid @enderror" required>
                        <option value="1" @selected((string) old('term', '1') === '1')>1. Donem</option>
                        <option value="2" @selected((string) old('term') === '2')>2. Donem</option>
                    </select>
                    @error('term')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                    <a href="{{ route('admin.subjects.index') }}" class="btn btn-outline-secondary">Iptal</a>
                </div>
            </form>
        </div>
    </div>
@endsection

