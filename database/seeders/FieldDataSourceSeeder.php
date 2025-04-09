<?php

namespace Database\Seeders;

use App\Models\DataSource;
use App\Models\Field;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FieldDataSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = Field::all();
        $dataSources = DataSource::all();

        foreach ($fields as $field) {
            // Każde pole ma 2-3 losowe źródła danych
            $dataSourceCount = rand(2, 3);
            $selectedDataSources = $dataSources->random($dataSourceCount);

            foreach ($selectedDataSources as $dataSource) {
                DB::table('field_data_sources')->insert([
                    'field_id' => $field->id,
                    'data_source_id' => $dataSource->id,
                    'settings' => json_encode([
                        'active' => true,
                        'update_frequency' => rand(1, 24),
                        'last_update' => now()
                            ->subHours(rand(1, 48))
                            ->toDateTimeString(),
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
