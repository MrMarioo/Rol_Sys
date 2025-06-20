<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Raport - {{ $report->title }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { margin-bottom: 20px; }
        .content { line-height: 1.6; }
    </style>
</head>
<body>
<div class="header">
    <h1>{{ $report->title }}</h1>
    <p><strong>Typ:</strong> {{ $report->type }}</p>
    <p><strong>Data utworzenia:</strong> {{ $report->created_at->format('d.m.Y H:i') }}</p>
    @if($report->description)
        <p><strong>Opis:</strong> {{ $report->description }}</p>
    @endif
</div>

<div class="content">
    @if($report->content)
        {!! is_array($report->content) ? json_encode($report->content, JSON_PRETTY_PRINT) : $report->content !!}
    @else
        <p>Brak wygenerowanej treści raportu.</p>
    @endif
</div>
</body>
</html>
