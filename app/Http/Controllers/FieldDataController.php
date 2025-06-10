<?php

namespace App\Http\Controllers;

use App\Models\Field;
use App\Models\FieldData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use App\Jobs\AnalyzeFieldDataJob;

class FieldDataController extends Controller
{
    public function index(): Response
    {
        $fieldData = FieldData::with('field')
            ->querySearch()
            ->orderBy('collection_date', 'desc')
            ->paginate(config('pagination.list', 10))
            ->withQueryString();

        $fields = Field::where('user_id', Auth::id())->get();

        $dataTypes = FieldData::select('data_type')
            ->distinct()
            ->whereHas('field', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->pluck('data_type');

        return Inertia::render('FieldData/Index', [
            'fieldData' => $fieldData,
            'fields' => $fields,
            'dataTypes' => $dataTypes,
            'filters' => request()->only([
                'search',
                'field_id',
                'data_type',
                'date_from',
                'date_to',
            ]),
        ]);
    }

    public function create()
    {
        $fields = Field::where('user_id', Auth::id())->get();

        return Inertia::render('FieldData/CreateForm', [
            'fields' => $fields,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'field_id' => 'required|exists:fields,id',
            'collection_date' => 'required|date',
            'data_type' => 'required|string|max:255',
            'data_json' => 'required|json',
            'metadata_json' => 'nullable|json',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $data = json_decode($validated['data_json'], true);
        $metadata = !empty($validated['metadata_json']) ? json_decode($validated['metadata_json'], true) : null;

        $fieldData = new FieldData([
            'field_id' => $validated['field_id'],
            'collection_date' => $validated['collection_date'],
            'data_type' => $validated['data_type'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'data' => $data,
            'metadata' => $metadata,
        ]);

        $fieldData->save();

        return redirect()->route('field-data.index')->with('success', 'Field data added successfully');
    }
    public function show(FieldData $fieldData)
    {
        return Inertia::render('FieldData/Show', [
            'fieldData' => $fieldData->load('field'),
        ]);
    }

    public function edit(FieldData $fieldData)
    {
        $fields = Field::where('user_id', Auth::id())->get();

        return Inertia::render('FieldData/Edit', [
            'fieldData' => $fieldData,
            'fields' => $fields,
        ]);
    }

    public function update(Request $request, FieldData $fieldData)
    {
        $validated = $request->validate([
            'field_id' => 'required|exists:fields,id',
            'collection_date' => 'required|date',
            'data_type' => 'required|string|max:255',
            'data' => 'required|array',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'metadata' => 'nullable|array',
        ]);

        $field = Field::where('id', $validated['field_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $fieldData->update($validated);

        return redirect()
            ->route('field-data.index')
            ->with('success', 'Field data was sucesfully updated.');
    }

    public function destroy(FieldData $fieldData)
    {
        $fieldData->delete();

        return redirect()
            ->route('field-data.index')
            ->with('success', 'Field Data was deleted.');
    }

      /****************************/
     // API FOR EXTERNAL DEVICES //
    /****************************/
    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'field_id' => 'required|exists:fields,id',
            'collection_date' => 'required|date',
            'data_type' => 'required|in:ndvi,soil_moisture',
            'data.ndvi_values' => 'required_if:data_type,ndvi|array|size:100',
            'data.ndvi_values.*' => 'numeric|between:0,1',
            'data.moisture_values' => 'required_if:data_type,soil_moisture|array|size:100',
            'data.moisture_values.*' => 'numeric|between:0,1',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'metadata' => 'required|array',
            'metadata.device' => 'required|string',
            'metadata.camera' => 'string',
            'metadata.height' => 'numeric|min:0',
            'metadata.weather' => 'array'
        ]);

        $fieldData = FieldData::create($validated);

        AnalyzeFieldDataJob::dispatch($fieldData);

        return response()->json([
            'status' => 'success',
            'data' => $fieldData,
            'message' => 'Field data stored and analysis queued'
        ], 201);
    }

    public function droneStore(Request $request)
    {
        $validated = $request->validate([
            'field_id' => 'required|exists:fields,id',
            'collection_date' => 'required|date',
            'data_type' => 'required|string',
            'data' => 'required|array',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'metadata' => 'nullable|array'
        ]);

        $fieldData = FieldData::create($validated);

        return response()->json([
            'success' => true,
            'id' => $fieldData->id,
            'message' => 'Data received from drone'
        ]);
    }

    public function apiShow(FieldData $fieldData)
    {
        return response()->json($fieldData->load('field'));
    }

    public function apiFieldData(Field $field)
    {
        $fieldData = $field->fieldData()
            ->when(request('data_type'), function ($query, $type) {
                $query->where('data_type', $type);
            })
            ->when(request('date_from'), function ($query, $date) {
                $query->whereDate('collection_date', '>=', $date);
            })
            ->when(request('date_to'), function ($query, $date) {
                $query->whereDate('collection_date', '<=', $date);
            })
            ->orderBy('collection_date', 'desc')
            ->get();

        return response()->json([
            'field' => $field,
            'data' => $fieldData,
            'count' => $fieldData->count()
        ]);
    }
}
