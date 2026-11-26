<?php

namespace App\Http\Requests;

use App\Enums\FieldStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'size' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'boundaries' => ['required', 'array', 'min:4', 'max:1000'],
            'boundaries.*' => ['required', 'array', 'size:2'],
            'boundaries.*.0' => ['required', 'numeric', 'between:-180,180'],
            'boundaries.*.1' => ['required', 'numeric', 'between:-90,90'],
            'status' => ['required', Rule::enum(type: FieldStatus::class)],
        ];
    }
}
