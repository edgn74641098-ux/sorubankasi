<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Soru Ara PDF</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 10px; }
        .meta { margin-bottom: 14px; color: #4b5563; }
        .question { margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #e5e7eb; }
        .question-title { font-weight: 700; margin-bottom: 6px; }
        .badge { display: inline-block; font-size: 10px; padding: 2px 6px; border-radius: 4px; background: #eef2ff; color: #3730a3; margin-bottom: 6px; }
        .option { margin: 2px 0; }
        .correct { color: #166534; font-weight: 700; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <h1>Soru Arama Sonuclari</h1>
    <div class="meta">
        <div><strong>Ders:</strong> {{ $selectedSubject?->name ?? 'Tum dersler' }}</div>
        <div><strong>Arama:</strong> {{ $term !== '' ? $term : 'Yok' }}</div>
        <div><strong>Kayit:</strong> {{ $questions->count() }}</div>
        <div><strong>Uretim:</strong> {{ $generatedAt->format('d.m.Y H:i') }}</div>
    </div>

    @forelse($questions as $index => $question)
        <div class="question">
            <div class="badge">{{ $question->subject?->name ?? 'Ders yok' }}</div>
            <div class="question-title">{{ $index + 1 }}. {{ $question->question_text }}</div>
            @foreach(['A', 'B', 'C', 'D', 'E'] as $option)
                @php($field = 'option_' . strtolower($option))
                <div class="option {{ $question->correct_option === $option ? 'correct' : '' }}">
                    {{ $option }}) {{ $question->{$field} }}
                    @if($question->correct_option === $option)
                        (Dogru)
                    @endif
                </div>
            @endforeach
            @if($question->explanation_text)
                <div class="muted"><strong>Aciklama:</strong> {{ $question->explanation_text }}</div>
            @endif
        </div>
    @empty
        <div>Filtreye uygun aktif soru bulunamadi.</div>
    @endforelse
</body>
</html>
