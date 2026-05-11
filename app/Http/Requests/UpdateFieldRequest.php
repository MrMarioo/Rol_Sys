<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('field'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'size' => ['sometimes', 'required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'boundaries' => ['sometimes', 'required', 'array'],
            'status' => ['sometimes', 'required', 'string'],
        ];
    }
}