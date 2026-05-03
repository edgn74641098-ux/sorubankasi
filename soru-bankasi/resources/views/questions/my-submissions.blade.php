@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Soru Önerilerim</h3>
                    <a href="{{ route('questions.create') }}" class="btn btn-light btn-sm">
                        Yeni Soru Öner
                    </a>
                </div>

                <div class="card-body">
                    @if ($submissions->count() === 0)
                        <div class="alert alert-info" role="alert">
                            Henüz soru önermedeydiniz. 
                            <a href="{{ route('questions.create') }}">Yeni soru öner</a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ders</th>
                                        <th>Soru</th>
                                        <th>Durum</th>
                                        <th>Gönderim Tarihi</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($submissions as $submission)
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    {{ $submission->subject->name }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="text-truncate sb-truncate-200">
                                                    {{ Str::limit($submission->payload_json['question_text'], 50) }}
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $statusClass = [
                                                        'pending' => 'warning',
                                                        'approved' => 'success',
                                                        'rejected' => 'danger',
                                                        'admin_review' => 'info',
                                                    ][$submission->status] ?? 'secondary';
                                                    
                                                    $statusLabel = [
                                                        'pending' => 'Beklemede',
                                                        'approved' => 'Onaylandı',
                                                        'rejected' => 'Reddedildi',
                                                        'admin_review' => 'Admin İncelemesi',
                                                    ][$submission->status] ?? 'Bilinmiyor';
                                                @endphp
                                                <span class="badge bg-{{ $statusClass }}">
                                                    {{ $statusLabel }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $submission->created_at->format('d.m.Y H:i') }}
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#detailModal{{ $submission->id }}">
                                                    Detay
                                                </button>
                                            </td>
                                        </tr>

                                        {{-- Detail Modal --}}
                                        <div class="modal fade" 
                                             id="detailModal{{ $submission->id }}" 
                                             tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Soru Detayları</h5>
                                                        <button type="button" class="btn-close" 
                                                                data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h6>Ders</h6>
                                                        <p>{{ $submission->subject->name }}</p>

                                                        <h6>Soru</h6>
                                                        <p>{{ $submission->payload_json['question_text'] }}</p>

                                                        <h6>Şıklar</h6>
                                                        <ul>
                                                            @foreach (['A', 'B', 'C', 'D', 'E'] as $option)
                                                                <li>
                                                                    <strong>{{ $option }}:</strong> 
                                                                    {{ $submission->payload_json['options'][$option] }}
                                                                    @if ($submission->payload_json['correct_option'] === $option)
                                                                        <span class="badge bg-success ms-2">Doğru Cevap</span>
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>

                                                        <h6>Açıklama</h6>
                                                        <p>{{ $submission->payload_json['explanation_text'] }}</p>

                                                        @if ($submission->status === 'rejected' && $submission->review_note)
                                                            <div class="alert alert-danger" role="alert">
                                                                <strong>Ret Sebebi:</strong>
                                                                {{ $submission->review_note }}
                                                            </div>
                                                        @endif

                                                        <h6>Durum</h6>
                                                        <p>
                                                            <span class="badge bg-{{ $statusClass }}">
                                                                {{ $statusLabel }}
                                                            </span>
                                                        </p>

                                                        @if ($submission->reviewed_at)
                                                            <h6>İnceleme Tarihi</h6>
                                                            <p>{{ $submission->reviewed_at->format('d.m.Y H:i') }}</p>
                                                        @endif
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" 
                                                                data-bs-dismiss="modal">
                                                            Kapat
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        <div class="d-flex justify-content-center mt-4">
                            {{ $submissions->links() }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Stats Card --}}
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Toplam Öneriler</h5>
                            <h2 class="text-primary">{{ $submissions->total() }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Onaylanan Sorular</h5>
                            <h2 class="text-success">
                                {{ $submissions->getCollection()->where('status', 'approved')->count() }}
                            </h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
