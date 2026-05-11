<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateFieldDataRequest extends FormRequest
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
                Rule::exists('fields', 'id')->where('user_id', Auth::id()),
            ],
            'collection_date' => ['required', 'date'],
            'data_type' => ['required', 'string', 'max:255'],
            'data' => ['required', 'array'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}