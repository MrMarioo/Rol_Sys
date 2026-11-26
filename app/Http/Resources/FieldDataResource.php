<?php

namespace App\Http\Resources;

use App\Models\FieldData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FieldData
 */
final class FieldDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'field_id' => $this->field_id,
            'field' => $this->whenLoaded(
                relationship: 'field',
                value: fn () => FieldResource::make($this->field)->resolve(),
            ),
            'collection_date' => $this->collection_date->format('Y-m-d'),
            'data_type' => $this->data_type,
            'data' => $this->data,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
        ];
    }
}
