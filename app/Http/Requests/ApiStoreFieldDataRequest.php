<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApiStoreFieldDataRequest extends FormRequest
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
            'data_type' => ['required', 'in:ndvi,soil_moisture'],
            'data.ndvi_values' => ['required_if:data_type,ndvi', 'array', 'size:100'],
            'data.ndvi_values.*' => ['numeric', 'between:0,1'],
            'data.moisture_values' => ['required_if:data_type,soil_moisture', 'array', 'size:100'],
            'data.moisture_values.*' => ['numeric', 'between:0,1'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'metadata' => ['required', 'array'],
            'metadata.device' => ['required', 'string'],
            'metadata.camera' => ['sometimes', 'string'],
            'metadata.height' => ['sometimes', 'numeric', 'min:0'],
            'metadata.weather' => ['sometimes', 'array'],
        ];
    }
}