<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CropSeeder::class,
            FieldSeeder::class,
            DataSourceSeeder::class,
            FieldDataSourceSeeder::class,
            FieldCropSeeder::class,
            FieldDataSeeder::class,
            AnalyticsSeeder::class,
            ReportSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
