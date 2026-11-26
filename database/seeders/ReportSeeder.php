<?php

namespace Database\Seeders;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportGenerationService;
use Illuminate\Database\Seeder;

final class ReportSeeder extends Seeder
{
    public function __construct(
        private readonly ReportGenerationService $generator,
    ) {}

    public function run(): void
    {
        foreach (User::query()->with(relations: ['fields'])->get() as $user) {
            $fieldIds = $user->fields->pluck('id')->all();

            if ($fieldIds === []) {
                continue;
            }

            foreach (ReportType::cases() as $type) {
                $this->seedReport(user: $user, type: $type, fieldIds: $fieldIds);
            }
        }
    }

    private function seedReport(User $user, ReportType $type, array $fieldIds): void
    {
        $report = Report::query()->updateOrCreate(
            attributes: ['user_id' => $user->id, 'type' => $type->value],
            values: [
                'title' => sprintf('%s - %s', $type->label(), now()->format('Y-m-d')),
                'description' => $type->description(),
                'fields_included' => $fieldIds,
                'parameters' => $this->parametersFor(type: $type),
                'content' => [],
                'status' => ReportStatus::Draft,
            ],
        );

        $report->update(attributes: [
            'content' => $this->generator->generate(report: $report),
            'status' => ReportStatus::Generated,
        ]);
    }

    private function parametersFor(ReportType $type): array
    {
        return match ($type) {
            ReportType::WeeklySummary, ReportType::ProblemReport => [
                'start_date' => now()->subWeek()->toDateString(),
                'end_date' => now()->toDateString(),
                'severity' => 'all',
            ],
            ReportType::MonthlySummary => ['month' => now()->month, 'year' => now()->year],
            ReportType::CropStatus => [],
            ReportType::Comparative => ['comparison_type' => 'fields'],
        };
    }
}
