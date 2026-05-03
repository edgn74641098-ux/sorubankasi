@extends('layouts.admin', ['pageTitle' => 'Soru Düzenle', 'title' => 'Soru Düzenle'])

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Soru Düzenle</h1>
        <p class="text-muted mb-0">Soru içeriğini ve durumunu güncelleyin.</p>
    </div>

    @include('admin.questions.partials.form', [
        'action' => route('admin.questions.update', $question),
        'method' => 'PUT',
        'question' => $question,
        'submitLabel' => 'Güncelle',
    ])
@endsection