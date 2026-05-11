<?php

namespace App\Http\Resources;

use App\Models\Analytics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Analytics
 */
class AnalyticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'field_id' => $this->field_id,
            'field' => FieldResource::make($this->whenLoaded('field')),
            'analysis_type' => $this->analysis_type,
            'analysis_date' => $this->analysis_date?->format('Y-m-d'),
            'results' => $this->results,
            'recommendations' => $this->recommendations,
            'parameters' => $this->parameters,
            'created_at' => $this->created_at,
        ];
    }
}