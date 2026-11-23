<?php

namespace Database\Seeders;

use App\Models\Field;
use App\Models\User;
use Illuminate\Database\Seeder;

class FieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'rolnik')->get();

        foreach ($users as $user) {
            $fieldCount = rand(2, 5);

            for ($i = 0; $i < $fieldCount; $i++) {
                Field::create([
                    'user_id' => $user->id,
                    'name' => 'Pole ' . ($i + 1) . ' - ' . $user->name,
                    'location' => 'Lokalizacja przykładowa ' . ($i + 1),
                    'size' => rand(50, 500) / 10, // 5.0 - 50.0 ha
                    'description' =>
                        'Przykładowy opis pola ' .
                        ($i + 1) .
                        ' należącego do ' .
                        $user->name,
                    'boundaries' => $this->randomRectangle(),
                    'status' => 'active',
                ]);
            }
        }
    }

    private function randomRectangle(): array
    {
        $lon = 20.4 + mt_rand(0, 6000) / 10000;
        $lat = 50.7 + mt_rand(0, 3000) / 10000;
        $width = 0.003 + mt_rand(0, 40) / 10000;
        $height = 0.002 + mt_rand(0, 30) / 10000;

        return [
            [round($lon, 6), round($lat, 6)],
            [round($lon + $width, 6), round($lat, 6)],
            [round($lon + $width, 6), round($lat + $height, 6)],
            [round($lon, 6), round($lat + $height, 6)],
        ];
    }
}
