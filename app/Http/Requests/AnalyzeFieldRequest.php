<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnalyzeFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('analyze', $this->route('field'));
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'sensitivity' => ['nullable', 'string', 'in:low,medium,high'],
            'include_growth_prediction' => ['nullable', 'boolean'],
            'prediction_days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }
}