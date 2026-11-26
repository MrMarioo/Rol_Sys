<?php

namespace Database\Seeders;

use App\Enums\FieldStatus;
use App\Models\Field;
use App\Models\User;
use Illuminate\Database\Seeder;

final class FieldSeeder extends Seeder
{
    private const NAMES = [
        'North Meadow',
        'River Plot',
        'Hilltop Field',
        'Oak Lane Field',
        'South Pasture',
    ];

    private const LOCATIONS = [
        'Kielce, Poland',
        'Chęciny, Poland',
        'Morawica, Poland',
        'Daleszyce, Poland',
        'Piekoszów, Poland',
    ];

    public function run(): void
    {
        foreach (User::all() as $user) {
            $fieldCount = rand(2, 5);

            for ($i = 0; $i < $fieldCount; $i++) {
                Field::query()->create(attributes: [
                    'user_id' => $user->id,
                    'name' => self::NAMES[$i],
                    'location' => self::LOCATIONS[$i],
                    'size' => rand(50, 500) / 10,
                    'description' => sprintf('Arable field managed by %s.', $user->name),
                    'boundaries' => $this->randomRectangle(),
                    'status' => FieldStatus::Active->value,
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
