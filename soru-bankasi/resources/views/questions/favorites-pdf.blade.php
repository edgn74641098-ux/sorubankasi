<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Favori Sorular PDF</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 10px; }
        .meta { margin-bottom: 14px; color: #4b5563; }
        .question { margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #e5e7eb; }
        .badge { display: inline-block; font-size: 10px; padding: 2px 6px; border-radius: 4px; background: #eef2ff; color: #3730a3; margin-bottom: 6px; }
        .title { font-weight: 700; margin-bottom: 6px; }
        .option { margin: 2px 0; }
        .correct { color: #166534; font-weight: 700; }
        .note { margin-top: 8px; color: #374151; background: #f9fafb; border: 1px solid #e5e7eb; padding: 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Favori Sorular</h1>
    <div class="meta">
        <div><strong>Kullanici:</strong> {{ $user->name }}</div>
        <div><strong>Kayit:</strong> {{ $favorites->count() }}</div>
        <div><strong>Uretim:</strong> {{ $generatedAt->format('d.m.Y H:i') }}</div>
    </div>

    @foreach($favorites as $index => $favorite)
        @php($question = $favorite->question)
        <div class="question">
            <div class="badge">{{ $question?->subject?->name ?? 'Ders yok' }}</div>
            <div class="title">{{ $index + 1 }}. {{ $question?->question_text ?? 'Soru silinmis' }}</div>
            @if($question)
                @foreach(['A', 'B', 'C', 'D', 'E'] as $option)
                    @php($field = 'option_' . strtolower($option))
                    <div class="option {{ $question->correct_option === $option ? 'correct' : '' }}">
                        {{ $option }}) {{ $question->{$field} }}
                        @if($question->correct_option === $option)
                            (Dogru)
                        @endif
                    </div>
                @endforeach
            @endif

            @if($favorite->note)
                <div class="note"><strong>Not:</strong> {{ $favorite->note }}</div>
            @endif
        </div>
    @endforeach
</body>
</html>
