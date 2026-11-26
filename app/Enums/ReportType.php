<?php

namespace App\Enums;

enum ReportType: string
{
    case WeeklySummary = 'weekly_summary';
    case MonthlySummary = 'monthly_summary';
    case ProblemReport = 'problem_report';
    case CropStatus = 'crop_status';
    case Comparative = 'comparative';

    public function label(): string
    {
        return match ($this) {
            self::WeeklySummary => 'Weekly Summary',
            self::MonthlySummary => 'Monthly Summary',
            self::ProblemReport => 'Problem Report',
            self::CropStatus => 'Crop Status Report',
            self::Comparative => 'Comparative Analysis',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::WeeklySummary => 'Weekly overview of field conditions and activities',
            self::MonthlySummary => 'Comprehensive monthly report with trends and analytics',
            self::ProblemReport => 'Issues and anomalies detected in your fields',
            self::CropStatus => 'Current status of crops and growth stages',
            self::Comparative => 'Compare fields or time periods performance',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::WeeklySummary => 'ni-calendar-alt',
            self::MonthlySummary => 'ni-calendar',
            self::ProblemReport => 'ni-alert-circle',
            self::CropStatus => 'ni-growth',
            self::Comparative => 'ni-bar-chart',
        };
    }

    public function parameters(): array
    {
        return match ($this) {
            self::WeeklySummary => ['date_range', 'fields'],
            self::MonthlySummary => ['month', 'year', 'fields'],
            self::ProblemReport => ['severity', 'date_range', 'fields'],
            self::CropStatus => ['crops', 'fields', 'growth_stage'],
            self::Comparative => ['comparison_type', 'fields', 'date_ranges'],
        };
    }

    public static function templates(): array
    {
        $templates = [];

        foreach (self::cases() as $type) {
            $templates[$type->value] = [
                'name' => $type->label(),
                'description' => $type->description(),
                'icon' => $type->icon(),
                'parameters' => $type->parameters(),
            ];
        }

        return $templates;
    }
}
