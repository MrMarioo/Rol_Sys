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
                    'boundaries' => json_encode([
                        [rand(51.0, 52.0), rand(20.0, 21.0)],
                        [rand(51.0, 52.0), rand(20.0, 21.0)],
                        [rand(51.0, 52.0), rand(20.0, 21.0)],
                        [rand(51.0, 52.0), rand(20.0, 21.0)],
                    ]),
                    'status' => 'active',
                ]);
            }
        }
    }
}
