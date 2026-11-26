<?php

namespace App\Http\Requests;

use App\Enums\FieldDataType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateFieldDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('fieldData'));
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
            'data' => ['required', 'array'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
