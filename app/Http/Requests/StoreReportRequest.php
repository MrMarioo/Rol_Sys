<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:weekly_summary,monthly_summary,problem_report,crop_status,comparative'],
            'description' => ['nullable', 'string'],
            'fields_included' => ['required', 'array', 'min:1'],
            'fields_included.*' => ['integer', 'exists:fields,id'],
            'parameters' => ['required', 'array'],
            'content' => ['nullable', 'array'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'content' => $this->input('content', []),
        ]);
    }
}