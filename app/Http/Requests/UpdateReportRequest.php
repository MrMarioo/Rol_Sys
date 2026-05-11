<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('report'));
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
        ];
    }
}