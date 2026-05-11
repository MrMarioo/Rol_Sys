<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'size' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'boundaries' => ['required', 'array'],
            'status' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['user_id' => Auth::id()]);
    }
}