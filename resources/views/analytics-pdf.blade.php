<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Analysis - {{ $analytics->field->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2933; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin: 18px 0 4px; }
        .meta { color: #52606d; margin: 0 0 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 1px solid #d9e2ec; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f0f4f8; width: 30%; }
    </style>
</head>
<body>
<h1>Field analysis: {{ $analytics->field->name }}</h1>
<p class="meta"><strong>Analysis type:</strong> {{ \Illuminate\Support\Str::headline($analytics->analysis_type) }}</p>
<p class="meta"><strong>Analysis date:</strong> {{ $analytics->analysis_date->format('Y-m-d H:i') }}</p>
<p class="meta"><strong>Field size:</strong> {{ $analytics->field->size }} ha</p>

<h2>Recommendations</h2>
@if (is_array($analytics->recommendations) && $analytics->recommendations !== [])
    @include('reports.pdf-value', ['value' => $analytics->recommendations])
@else
    <p>No recommendations.</p>
@endif

<h2>Results</h2>
@include('reports.pdf-value', ['value' => $analytics->results ?? []])

<h2>Parameters</h2>
@include('reports.pdf-value', ['value' => $analytics->parameters ?? []])
</body>
</html>
