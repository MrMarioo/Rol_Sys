<?php

namespace App\Http\Controllers;

use App\Enums\Statuses;
use App\Http\Requests\StoreFieldRequest;
use App\Http\Requests\UpdateFieldRequest;
use App\Http\Resources\FieldResource;
use App\Models\Field;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class FieldController extends Controller
{
    public function __construct(
        private readonly Field $field,
    ) {}

    public function index(Request $request): Response
    {
        $fields = QueryBuilder::for(subject: Field::class)
            ->where('user_id', Auth::id())
            ->allowedFilters(filters: [
                'name',
                'location',
                AllowedFilter::exact(name: 'status'),
            ])
            ->allowedSorts(sorts: ['name', 'size', 'created_at'])
            ->defaultSort(sorts: ['-created_at'])
            ->paginate(
                perPage: $request->integer(key: 'per_page', default: config('pagination.list')),
            )
            ->withQueryString();

        return Inertia::render(
            component: 'Field/Index',
            props: [
                'fields' => FieldResource::collection(resource: $fields),
                'user' => Auth::user(),
                'statuses' => array_column(Statuses::cases(), 'value'),
            ]
        );
    }

    public function show(Field $field): Response
    {
        $this->authorize('view', $field);

        return Inertia::render(
            component: 'Field/Show',
            props: [
                'field' => FieldResource::make(resource: $field)->resolve(),
            ]
        );
    }

    public function edit(Field $field): Response
    {
        $this->authorize('update', $field);

        return Inertia::render(
            component: 'Field/Edit',
            props: [
                'field' => FieldResource::make(resource: $field)->resolve(),
                'statuses' => array_column(Statuses::cases(), 'value'),
            ]
        );
    }

    public function store(StoreFieldRequest $request): RedirectResponse
    {
        /** @var Field $field */
        $field = $this->field->create($request->validated());

        return redirect()
            ->route(route: 'fields.index')
            ->with(
                key: 'success',
                value: sprintf('Field %s has been created.', $field->name),
            );
    }

    public function update(UpdateFieldRequest $request, Field $field): RedirectResponse
    {
        $field->update($request->validated());

        return redirect()
            ->route(route: 'fields.index')
            ->with(
                key: 'success',
                value: sprintf('Field %s has been updated.', $field->name),
            );
    }

    public function destroy(Field $field): RedirectResponse
    {
        $this->authorize('delete', $field);

        $name = $field->name;
        $field->delete();

        return redirect()
            ->route(route: 'fields.index')
            ->with(
                key: 'success',
                value: sprintf('Field %s has been deleted.', $name),
            );
    }

    public function data(Field $field): RedirectResponse
    {
        $this->authorize('viewData', $field);

        return redirect()->route(
            route: 'field-data.index',
            parameters: ['field_id' => $field->id],
        );
    }

    public function analytics(Field $field): RedirectResponse
    {
        $this->authorize('view', $field);

        return redirect()->route(
            route: 'analytics.index',
            parameters: ['filter[field_id]' => $field->id],
        );
    }
}