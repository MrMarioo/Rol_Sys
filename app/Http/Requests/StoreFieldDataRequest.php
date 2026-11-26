<?php

namespace App\Http\Requests;

use App\Enums\FieldDataType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFieldDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'field_id' => [
                'required',
                Rule::exists(table: 'fields', column: 'id')->where('user_id', $this->user()->id),
            ],
            'collection_date' => ['required', 'date'],
            'data_type' => ['required', Rule::enum(type: FieldDataType::class)],
            'data_json' => ['required', 'json'],
            'metadata_json' => ['nullable', 'json'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function decodedData(): array
    {
        return [
            'field_id' => $this->integer('field_id'),
            'collection_date' => $this->string('collection_date')->toString(),
            'data_type' => $this->string('data_type')->toString(),
            'latitude' => $this->input('latitude'),
            'longitude' => $this->input('longitude'),
            'data' => json_decode($this->string('data_json'), true),
            'metadata' => $this->filled('metadata_json')
                ? json_decode($this->string('metadata_json'), true)
                : null,
        ];
    }
}
