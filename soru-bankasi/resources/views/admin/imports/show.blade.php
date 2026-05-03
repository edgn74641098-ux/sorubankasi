@extends('layouts.admin', ['pageTitle' => 'Import Detayı', 'title' => 'Import Detayı'])

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Import #{{ $batch->id }}</h1>
            <p class="text-muted mb-0">{{ $batch->file_name }} - {{ $batch->status }}</p>
        </div>
        <a href="{{ route('admin.imports.index') }}" class="btn btn-outline-secondary">Geri</a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Toplam</div><div class="h4 mb-0">{{ $batch->total_rows }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Önizleme Başarılı</div><div class="h4 mb-0">{{ $batch->success_rows }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Önizleme Hatalı</div><div class="h4 mb-0">{{ $batch->failed_rows }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Yükleyen</div><div class="h6 mb-0">{{ $batch->uploadedBy?->name }}</div></div></div></div>
    </div>

    @if($batch->status === 'preview')
        <form method="POST" action="{{ route('admin.imports.confirm', $batch) }}">
            @csrf
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Önizleme Satırları</h2>
                        <button class="btn btn-primary">Onayla ve Uygula</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Satır</th>
                                    <th>Hash</th>
                                    <th>Eşleşen Soru</th>
                                    <th>Aksiyon</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($batch->rows as $row)
                                    <tr>
                                        <td>#{{ $loop->iteration }}</td>
                                        <td><code>{{ $row->question_hash }}</code></td>
                                        <td>
                                            @if($row->matchedQuestion)
                                                {{ \Illuminate\Support\Str::limit($row->matchedQuestion->question_text, 80) }}
                                            @else
                                                <span class="text-success">Yeni kayıt</span>
                                            @endif
                                        </td>
                                        <td class="sb-min-220">
                                            <select name="actions[{{ $row->id }}]" class="form-select form-select-sm">
                                                @if($row->matchedQuestion)
                                                    <option value="skip" selected>Skip</option>
                                                    <option value="merge">Merge</option>
                                                    <option value="manual_review">Manual Review</option>
                                                @else
                                                    <option value="insert" selected>Insert</option>
                                                    <option value="manual_review">Manual Review</option>
                                                    <option value="skip">Skip</option>
                                                @endif
                                            </select>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">Önizleme satırı yok.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">Hatalar</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Satır</th>
                            <th>Mesaj</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batch->errors as $error)
                            <tr>
                                <td>{{ $error->row_number }}</td>
                                <td>{{ $error->error_message }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-muted">Hata yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
