<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreFieldDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
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