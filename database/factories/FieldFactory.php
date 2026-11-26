<?php

namespace Database\Factories;

use App\Models\Field;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Field>
 */
final class FieldFactory extends Factory
{
    protected $model = Field::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Pole '.fake()->unique()->numberBetween(1, 9999),
            'size' => fake()->randomFloat(2, 1, 100),
            'status' => 'active',
        ];
    }
}
