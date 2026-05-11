<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiStoreFieldDataRequest;
use App\Http\Requests\DroneStoreFieldDataRequest;
use App\Http\Resources\FieldDataResource;
use App\Jobs\AnalyzeFieldDataJob;
use App\Models\Field;
use App\Models\FieldData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class FieldDataController extends Controller
{
    public function __construct(
        private readonly FieldData $fieldData,
    ) {}

    public function store(ApiStoreFieldDataRequest $request): JsonResponse
    {
        /** @var FieldData $fieldData */
        $fieldData = $this->fieldData->create($request->validated());

        AnalyzeFieldDataJob::dispatch($fieldData);

        return response()->json(
            data: [
                'message' => 'Field data stored and analysis queued.',
                'data' => FieldDataResource::make(resource: $fieldData)->resolve(),
            ],
            status: HttpResponse::HTTP_CREATED,
        );
    }

    public function drone(DroneStoreFieldDataRequest $request): JsonResponse
    {
        /** @var FieldData $fieldData */
        $fieldData = $this->fieldData->create($request->validated());

        return response()->json(
            data: [
                'message' => 'Data received from drone.',
                'data' => FieldDataResource::make(resource: $fieldData)->resolve(),
            ],
            status: HttpResponse::HTTP_CREATED,
        );
    }

    public function show(FieldData $fieldData): JsonResponse
    {
        return response()->json(
            data: ['data' => FieldDataResource::make(resource: $fieldData->load('field'))->resolve()],
            status: HttpResponse::HTTP_OK,
        );
    }

    public function forField(Request $request, Field $field): JsonResponse
    {
        $fieldData = $field->fieldData()
            ->when(
                $request->filled('data_type'),
                fn ($q) => $q->where('data_type', $request->input(key: 'data_type')),
            )
            ->when(
                $request->filled('date_from'),
                fn ($q) => $q->whereDate('collection_date', '>=', $request->input(key: 'date_from')),
            )
            ->when(
                $request->filled('date_to'),
                fn ($q) => $q->whereDate('collection_date', '<=', $request->input(key: 'date_to')),
            )
            ->orderBy('collection_date', 'desc')
            ->get();

        return response()->json(
            data: [
                'field' => $field,
                'data' => FieldDataResource::collection(resource: $fieldData),
                'count' => $fieldData->count(),
            ],
            status: HttpResponse::HTTP_OK,
        );
    }
}