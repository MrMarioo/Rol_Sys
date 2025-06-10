<?php

namespace App\Http\Controllers;

use App\Models\Analytics;
use App\Models\Field;
use App\Models\FieldData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class AnalitycsController extends Controller
{
    public function index()
    {
        $analytics = Analytics::with('field')
            ->whereHas('field', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->orderBy('analysis_date', 'desc')
            ->paginate(10);
        $fields = Field::where('user_id', auth()->id())->get();

        return Inertia::render('Analytics/Index', [
            'analytics' => $analytics,
            'fields' => $fields,
            'sensitivities' => ['low', 'medium', 'high']
        ]);
    }

    public function show(Analytics $analytics)
    {
        if ($analytics->field->user_id !== auth()->id()) {
            return redirect('/analytics')->with('error', 'You do not have permission to view this analysis.');
        }

        return Inertia::render('Analytics/Show', [
            'analytics' => $analytics,
            'field' => $analytics->field
        ]);
    }

    public function download(Analytics $analytics)
    {
        if ($analytics->field->user_id !== auth()->id()) {
            return redirect('/analytics')->with('error', 'You do not have permission to download this report.');
        }

        $pdf = "Analysis report for field {$analytics->field->name}";

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=analysis-report-{$analytics->id}.pdf");
    }

    public function destroy(Analytics $analytics)
    {
        if ($analytics->field->user_id !== auth()->id()) {
            return redirect('/analytics')->with('error', 'You do not have permission to delete this analysis.');
        }

        $analytics->delete();

        return redirect('/analytics')->with('success', 'Analysis deleted successfully.');
    }

    public function analyze(Request $request, Field $field)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'sensitivity' => 'nullable|string|in:low,medium,high',
            'include_growth_prediction' => 'nullable|boolean',
            'prediction_days' => 'nullable|integer|min:1|max:30',
        ]);

        $query = FieldData::where("field_id", $field->id);

        if($request->has('start_date')) {
            $query->where('collection_date', '>=', $request->start_date);
        }

        if($request->has('end_date')) {
            $query->where('collection_date', '<=', $request->end_date);
        }

        $fieldData = $query->orderBy('collection_date', 'desc')->get();

        try {
            $formattedData = $fieldData->map(function($item) {
                $dataArray = is_string($item->data) ? json_decode($item->data, true) : $item->data;
                $metadataArray = is_string($item->metadata) ? json_decode($item->metadata, true) : $item->metadata;

                return [
                    'id' => $item->id,
                    'field_id' => $item->field_id,
                    'collection_date' => $item->collection_date ? $item->collection_date->format('Y-m-d') : null,
                    'data_type' => $item->data_type,
                    'data' => $dataArray,
                    'latitude' => $item->latitude,
                    'longitude' => $item->longitude,
                    'metadata' => $metadataArray
                ];
            })->toArray();

            // Informacje o polu i uprawie dla predykcji
            $activeCrop = $field->crops()
                ->wherePivot('status', 'active')
                ->whereNull('actual_harvest_date')
                ->first();

            $fieldInfo = [
                'size' => $field->size,
                'planting_date' => $activeCrop?->pivot->planting_date,
                'crop_type' => $activeCrop?->name,
            ];

            logger()->info("Data being sent to AI:", [
                'count' => count($formattedData),
                'types' => collect($formattedData)->pluck('data_type')->unique()->toArray(),
                'include_prediction' => $validated['include_growth_prediction'] ?? false,
                'field_info' => $fieldInfo
            ]);

            // Wysłanie danych do AI - teraz z opcjonalną predykcją
            $aiPayload = [
                'field_data' => $formattedData,
                'field_info' => $fieldInfo,
                'parameters' => [
                    'sensitivity' => $validated['sensitivity'] ?? 'medium',
                    'include_growth_prediction' => $validated['include_growth_prediction'] ?? false,
                    'prediction_days' => $validated['prediction_days'] ?? 7
                ]
            ];

            $response = Http::timeout(60)->post("http://ai-service:5000/analyze/field/{$field->id}", $aiPayload);

            logger()->info("AI Response Status: " . $response->status());

            if (!$response->successful()) {
                logger()->error("AI Error Response: " . $response->body());
                return redirect()->back()->with('error', 'AI analysis error: ' . $response->body());
            }

            $results = $response->json();

            $analytics = new Analytics([
                'field_id' => $field->id,
                'analysis_type' => $validated['include_growth_prediction'] ? 'comprehensive_with_prediction' : 'standard_analysis',
                'analysis_date' => now(),
                'results' => $results,
                'recommendations' => $results['recommendations'] ?? null,
                'parameters' => [
                    'sensitivity' => $validated['sensitivity'] ?? 'medium',
                    'data_count' => count($formattedData),
                    'include_prediction' => $validated['include_growth_prediction'] ?? false,
                    'prediction_days' => $validated['prediction_days'] ?? 7,
                    'date_range' => [
                        'from' => $request->start_date,
                        'to' => $request->end_date
                    ]
                ]
            ]);

            $analytics->save();

            return Inertia::render('Analytics/Show', [
                'field' => $field,
                'activeCrop' => $activeCrop,
                'analytics' => [
                    'id' => $analytics->id,
                    'analysis_date' => $analytics->analysis_date->format('Y-m-d'),
                    'analysis_type' => $analytics->analysis_type,
                    'results' => $analytics->results,
                    'recommendations' => $analytics->recommendations,
                    'parameters' => $analytics->parameters
                ]
            ]);

        } catch (\Exception $e) {
            logger()->error("AI Analysis Exception: " . $e->getMessage());
            return redirect()->back()->with('error', 'Error during analysis: ' . $e->getMessage());
        }
    }
}
