<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DroneStoreFieldDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'field_id' => ['required', 'exists:fields,id'],
            'collection_date' => ['required', 'date'],
            'data_type' => ['required', 'string'],
            'data' => ['required', 'array'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}