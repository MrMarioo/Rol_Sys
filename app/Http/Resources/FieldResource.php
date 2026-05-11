<?php

namespace App\Http\Resources;

use App\Models\Field;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Field
 */
class FieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'location' => $this->location,
            'size' => $this->size,
            'description' => $this->description,
            'boundaries' => $this->boundaries,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}