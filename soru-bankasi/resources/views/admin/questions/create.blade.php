@extends('layouts.admin', ['pageTitle' => 'Yeni Soru', 'title' => 'Yeni Soru'])

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Yeni Soru Ekle</h1>
        <p class="text-muted mb-0">Ders seçip soru detaylarını eksiksiz girin.</p>
    </div>

    @include('admin.questions.partials.form', [
        'action' => route('admin.questions.store'),
        'method' => 'POST',
        'question' => null,
        'submitLabel' => 'Kaydet',
    ])
@endsection