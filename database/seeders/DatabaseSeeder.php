<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CropSeeder::class,
            FieldSeeder::class,
            FieldCropSeeder::class,
            FieldDataSeeder::class,
            AnalyticsSeeder::class,
            ReportSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
