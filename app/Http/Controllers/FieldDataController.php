<?php

namespace App\Http\Controllers;

use App\Models\Field;
use App\Models\FieldData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

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
            'data' => 'required|array',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'metadata' => 'nullable|array',
        ]);

        $field = Field::where('id', $validated['field_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        FieldData::create($validated);

        return redirect()
            ->route('field-data.index')
            ->with('success', 'Field data was added.');
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
}
