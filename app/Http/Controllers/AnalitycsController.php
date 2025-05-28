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
        ]);

        $query = FieldData::where("field_id", $field->id);

        if($request->has('start_date')) {
            $query->where('collection_date', '>=', $request->start_date);
        }

        if($request->has('end_date')) {
            $query->where('collection_date', '<=', $request->end_date);
        }

        $fieldData = $query->get();

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

            logger()->info("Data being sent to AI:", [
                'count' => count($formattedData),
                'types' => collect($formattedData)->pluck('data_type')->unique()->toArray(),
                'sample_ndvi' => collect($formattedData)->where('data_type', 'ndvi')->first(),
                'sample_moisture' => collect($formattedData)->where('data_type', 'soil_moisture')->first()
            ]);

            $response = Http::post("http://ai-service:5000/analyze/field/{$field->id}", [
                'field_data' => $formattedData,
                'parameters' => [
                    'sensitivity' => $validated['sensitivity'] ?? 'medium'
                ]
            ]);

            logger()->info("AI Response Status: " . $response->status());

            if (!$response->successful()) {
                logger()->error("AI Error Response: " . $response->body());
                return redirect()->back()->with('error', 'AI analysis error: ' . $response->body());
            }

            $results = $response->json();

            $analytics = new Analytics([
                'field_id' => $field->id,
                'analysis_type' => 'ndvi_test',
                'analysis_date' => now(),
                'results' => $results,
                'recommendations' => $results['recommendations'] ?? null,
                'parameters' => json_encode([
                    'sensitivity' => $validated['sensitivity'] ?? 'medium',
                    'data_count' => count($formattedData),
                    'test_mode' => 'ndvi_only',
                    'date_range' => [
                        'from' => $request->start_date,
                        'to' => $request->end_date
                    ]
                ])
            ]);

            $analytics->save();

            return Inertia::render('Analytics/Results', [
                'field' => $field,
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
