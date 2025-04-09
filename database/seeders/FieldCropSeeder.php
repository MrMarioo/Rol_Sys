<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\Field;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FieldCropSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = Field::all();
        $crops = Crop::all();

        $statuses = ['active', 'harvested', 'failed'];

        foreach ($fields as $field) {
            // Każde pole ma 1-2 uprawy
            $cropCount = rand(1, 2);
            $selectedCrops = $crops->random($cropCount);

            foreach ($selectedCrops as $crop) {
                $status = $statuses[rand(0, 10) > 7 ? rand(1, 2) : 0];

                $plantingDate = now()->subDays(rand(30, 180));
                $expectedHarvestDate = $plantingDate
                    ->copy()
                    ->addDays(rand(90, 180));
                $actualHarvestDate = null;
                $yield = null;

                if ($status === 'harvested') {
                    $actualHarvestDate = $expectedHarvestDate
                        ->copy()
                        ->subDays(rand(-10, 10));
                    $yield = rand(300, 800) / 10; // 30.0 - 80.0 dt/ha
                }

                DB::table('field_crops')->insert([
                    'field_id' => $field->id,
                    'crop_id' => $crop->id,
                    'planting_date' => $plantingDate,
                    'expected_harvest_date' => $expectedHarvestDate,
                    'actual_harvest_date' => $actualHarvestDate,
                    'yield' => $yield,
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
