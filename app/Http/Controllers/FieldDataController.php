<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFieldDataRequest;
use App\Http\Requests\UpdateFieldDataRequest;
use App\Http\Resources\FieldDataResource;
use App\Http\Resources\FieldResource;
use App\Models\FieldData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->allowedFilters(filters: [
                AllowedFilter::exact(name: 'field_id'),
                AllowedFilter::exact(name: 'data_type'),
                AllowedFilter::callback(
                    name: 'search',
                    callback: function (Builder $query, mixed $value) {
                        $term = implode(',', (array) $value);

                        $query->where(
                            fn (Builder $searchQuery) => $searchQuery
                                ->where('data_type', 'like', "%{$term}%")
                                ->orWhereHas('field', fn (Builder $fieldQuery) => $fieldQuery->where('name', 'like', "%{$term}%"))
                        );
                    },
                ),
                AllowedFilter::callback(
                    name: 'date_from',
                    callback: fn (Builder $query, string $value) => $query->whereDate('collection_date', '>=', $value),
                ),
                AllowedFilter::callback(
                    name: 'date_to',
                    callback: fn (Builder $query, string $value) => $query->whereDate('collection_date', '<=', $value),
                ),
            ])
            ->allowedSorts(sorts: ['collection_date', 'created_at', 'data_type'])
            ->defaultSort(sorts: ['-collection_date'])
            ->with(relations: ['field'])
            ->ownedBy(user: $request->user())
            ->paginate(
                perPage: $this->perPage(request: $request),
            )
            ->withQueryString();

        $fields = $request->user()->fields()->orderBy('name')->get();

        $dataTypes = FieldData::query()
            ->ownedBy(user: $request->user())
            ->select('data_type')
            ->distinct()
            ->pluck('data_type');

        return Inertia::render(
            component: 'FieldData/Index',
            props: [
                'fieldData' => $fieldData->through(fn (FieldData $data) => FieldDataResource::make($data)->resolve()),
                'fields' => FieldResource::collection(resource: $fields)->resolve(),
                'dataTypes' => $dataTypes,
                'filters' => $request->collect(key: 'filter')->only([
                    'search',
                    'field_id',
                    'data_type',
                    'date_from',
                    'date_to',
                ]),
            ]
        );
    }

    public function create(Request $request): Response
    {
        $fields = $request->user()->fields()->orderBy('name')->get();

        return Inertia::render(
            component: 'FieldData/CreateForm',
            props: [
                'fields' => FieldResource::collection(resource: $fields)->resolve(),
            ]
        );
    }

    public function store(StoreFieldDataRequest $request): RedirectResponse
    {
        /** @var FieldData $fieldData */
        $fieldData = $this->fieldData->create(attributes: $request->decodedData());

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

    public function edit(Request $request, FieldData $fieldData): Response
    {
        $this->authorize('update', $fieldData);

        $fields = $request->user()->fields()->orderBy('name')->get();

        return Inertia::render(
            component: 'FieldData/Edit',
            props: [
                'fieldData' => FieldDataResource::make(resource: $fieldData)->resolve(),
                'fields' => FieldResource::collection(resource: $fields)->resolve(),
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
