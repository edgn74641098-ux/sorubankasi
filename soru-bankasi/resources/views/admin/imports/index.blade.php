@extends('layouts.admin', ['pageTitle' => 'Soru Import', 'title' => 'Soru Import'])

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Soru Import</h1>
            <p class="text-muted mb-0">CSV dosyasını yükleyin, önizleyin, sonra onaylayın.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.imports.store') }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                <div class="col-md-8">
                    <label for="file" class="form-label">CSV dosyası</label>
                    <input id="file" name="file" type="file" class="form-control @error('file') is-invalid @enderror" accept=".csv" required>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Önizleme Oluştur</button>
                </div>
            </form>
            <div class="small text-muted mt-3">
                Beklenen kolonlar: <code>subject,question_text,option_a,option_b,option_c,option_d,option_e,correct_option,explanation_text</code>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">Geçmiş Importlar</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Dosya</th>
                            <th>Durum</th>
                            <th>Başarılı</th>
                            <th>Hatalı</th>
                            <th>Yükleyen</th>
                            <th>Tarih</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $batch)
                            <tr>
                                <td>#{{ $batch->id }}</td>
                                <td>{{ $batch->file_name }}</td>
                                <td><span class="badge text-bg-secondary">{{ $batch->status }}</span></td>
                                <td>{{ $batch->success_rows }}</td>
                                <td>{{ $batch->failed_rows }}</td>
                                <td>{{ $batch->uploadedBy?->name }}</td>
                                <td>{{ $batch->created_at?->format('d.m.Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.imports.show', $batch) }}" class="btn btn-sm btn-outline-primary">Detay</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted">Henüz import yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $batches->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection

