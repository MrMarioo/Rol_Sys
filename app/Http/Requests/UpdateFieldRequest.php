<?php

namespace App\Http\Requests;

use App\Enums\FieldStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateFieldRequest extends FormRequest
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
            'boundaries' => ['sometimes', 'required', 'array', 'min:4', 'max:1000'],
            'boundaries.*' => ['required', 'array', 'size:2'],
            'boundaries.*.0' => ['required', 'numeric', 'between:-180,180'],
            'boundaries.*.1' => ['required', 'numeric', 'between:-90,90'],
            'status' => ['sometimes', 'required', Rule::enum(type: FieldStatus::class)],
        ];
    }
}
