<?php

namespace App\Http\Requests;

use App\Enums\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('report'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(type: ReportType::class)],
            'description' => ['nullable', 'string'],
            'fields_included' => ['required', 'array', 'min:1'],
            'fields_included.*' => [
                'integer',
                Rule::exists(table: 'fields', column: 'id')->where('user_id', $this->user()->id),
            ],
            'parameters' => ['required', 'array'],
        ];
    }
}
