<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class UserSeeder extends Seeder
{
    public const DEMO_EMAIL = 'demo@rolsys.test';

    public const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        $users = [
            ['name' => 'Demo Farmer', 'email' => self::DEMO_EMAIL],
            ['name' => 'Anna Nowak', 'email' => 'anna@rolsys.test'],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                attributes: ['email' => $user['email']],
                values: [
                    'name' => $user['name'],
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
