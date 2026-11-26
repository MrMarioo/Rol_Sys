<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Report - {{ $report->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2933; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        .meta { color: #52606d; margin: 0 0 2px; }
        .section { margin-top: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 1px solid #d9e2ec; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f0f4f8; width: 30%; }
    </style>
</head>
<body>
<h1>{{ $report->title }}</h1>
<p class="meta"><strong>Type:</strong> {{ $report->typeLabel() }}</p>
<p class="meta"><strong>Created:</strong> {{ $report->created_at->format('Y-m-d H:i') }}</p>
@if ($report->description)
    <p class="meta"><strong>Description:</strong> {{ $report->description }}</p>
@endif

<div class="section">
    @if (! empty($report->content))
        @include('reports.pdf-value', ['value' => $report->content])
    @else
        <p>This report has not been generated yet.</p>
    @endif
</div>
</body>
</html>
