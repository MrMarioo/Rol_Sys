<?php

namespace Tests\Feature;

use App\Models\Field;
use App\Models\User;
use Database\Seeders\FieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FieldBoundariesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_boundaries_are_lon_lat_pairs(): void
    {
        User::factory()->create();

        $this->seed(FieldSeeder::class);

        $boundaries = Field::firstOrFail()->boundaries;
        $this->assertIsArray($boundaries);
        $this->assertGreaterThanOrEqual(3, count($boundaries));
        foreach ($boundaries as [$lon, $lat]) {
            $this->assertTrue($lon >= 14 && $lon <= 25, 'longitude within Poland');
            $this->assertTrue($lat >= 49 && $lat <= 55, 'latitude within Poland');
        }
    }

    public function test_migration_normalizes_legacy_boundaries(): void
    {
        $user = User::factory()->create();
        $doubleEncoded = Field::factory()->for($user)->create();
        $latLngObjects = Field::factory()->for($user)->create();
        $valid = Field::factory()->for($user)->create(['boundaries' => [[20.6, 50.8], [20.7, 50.8], [20.7, 50.9]]]);

        DB::table('fields')->where('id', $doubleEncoded->id)
            ->update(['boundaries' => json_encode(json_encode([[20.6, 50.8], [20.7, 50.8], [20.7, 50.9]]))]);
        DB::table('fields')->where('id', $latLngObjects->id)
            ->update(['boundaries' => json_encode([['lat' => 50.8, 'lng' => 20.6], ['lat' => 50.9, 'lng' => 20.7]])]);

        $migration = require database_path('migrations/2026_11_23_000000_normalize_field_boundaries.php');
        $migration->up();

        $this->assertSame([[20.6, 50.8], [20.7, 50.8], [20.7, 50.9]], $doubleEncoded->fresh()->boundaries);
        $this->assertSame([[20.6, 50.8], [20.7, 50.9]], $latLngObjects->fresh()->boundaries);
        $this->assertSame([[20.6, 50.8], [20.7, 50.8], [20.7, 50.9]], $valid->fresh()->boundaries);
    }
}
