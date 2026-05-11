<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFieldDataRequest;
use App\Http\Requests\UpdateFieldDataRequest;
use App\Http\Resources\FieldDataResource;
use App\Http\Resources\FieldResource;
use App\Models\Field;
use App\Models\FieldData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class FieldDataController extends Controller
{
    public function __construct(
        private readonly FieldData $fieldData,
    ) {}

    public function index(Request $request): Response
    {
        $fieldData = QueryBuilder::for(subject: FieldData::class)
            ->with(relations: ['field'])
            ->whereHas('field', fn ($q) => $q->where('user_id', Auth::id()))
            ->allowedFilters(filters: [
                AllowedFilter::exact(name: 'field_id'),
                AllowedFilter::exact(name: 'data_type'),
            ])
            ->allowedSorts(sorts: ['collection_date', 'created_at', 'data_type'])
            ->defaultSort(sorts: ['-collection_date'])
            ->paginate(
                perPage: $request->integer(key: 'per_page', default: config('pagination.list', 10)),
            )
            ->withQueryString();

        $fields = Field::where('user_id', Auth::id())->get();

        $dataTypes = FieldData::query()
            ->whereHas('field', fn ($q) => $q->where('user_id', Auth::id()))
            ->select('data_type')
            ->distinct()
            ->pluck('data_type');

        return Inertia::render(
            component: 'FieldData/Index',
            props: [
                'fieldData' => FieldDataResource::collection(resource: $fieldData),
                'fields' => FieldResource::collection(resource: $fields),
                'dataTypes' => $dataTypes,
                'filters' => $request->only([
                    'search',
                    'field_id',
                    'data_type',
                    'date_from',
                    'date_to',
                ]),
            ]
        );
    }

    public function create(): Response
    {
        $fields = Field::where('user_id', Auth::id())->get();

        return Inertia::render(
            component: 'FieldData/CreateForm',
            props: [
                'fields' => FieldResource::collection(resource: $fields),
            ]
        );
    }

    public function store(StoreFieldDataRequest $request): RedirectResponse
    {
        /** @var FieldData $fieldData */
        $fieldData = $this->fieldData->create($request->decodedData());

        return redirect()
            ->route(route: 'field-data.index')
            ->with(
                key: 'success',
                value: sprintf('Field data #%d has been created.', $fieldData->id),
            );
    }

    public function show(FieldData $fieldData): Response
    {
        $this->authorize('view', $fieldData);

        return Inertia::render(
            component: 'FieldData/Show',
            props: [
                'fieldData' => FieldDataResource::make(resource: $fieldData->load('field'))->resolve(),
            ]
        );
    }

    public function edit(FieldData $fieldData): Response
    {
        $this->authorize('update', $fieldData);

        $fields = Field::where('user_id', Auth::id())->get();

        return Inertia::render(
            component: 'FieldData/Edit',
            props: [
                'fieldData' => FieldDataResource::make(resource: $fieldData)->resolve(),
                'fields' => FieldResource::collection(resource: $fields),
            ]
        );
    }

    public function update(UpdateFieldDataRequest $request, FieldData $fieldData): RedirectResponse
    {
        $fieldData->update($request->validated());

        return redirect()
            ->route(route: 'field-data.index')
            ->with(
                key: 'success',
                value: sprintf('Field data #%d has been updated.', $fieldData->id),
            );
    }

    public function destroy(FieldData $fieldData): RedirectResponse
    {
        $this->authorize('delete', $fieldData);

        $id = $fieldData->id;
        $fieldData->delete();

        return redirect()
            ->route(route: 'field-data.index')
            ->with(
                key: 'success',
                value: sprintf('Field data #%d has been deleted.', $id),
            );
    }
}