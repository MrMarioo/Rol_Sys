<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('fields')
            ->whereNotNull('boundaries')
            ->orderBy('id')
            ->each(function (object $field) {
                $normalized = $this->normalize(boundaries: $field->boundaries);

                if ($normalized !== null) {
                    DB::table('fields')
                        ->where('id', $field->id)
                        ->update(['boundaries' => json_encode($normalized)]);
                }
            });
    }

    public function down(): void {}

    private function normalize(string $boundaries): ?array
    {
        $decoded = json_decode($boundaries, true);

        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        if (! is_array($decoded)) {
            return null;
        }

        return array_map(
            fn (mixed $point) => is_array($point) && isset($point['lat'], $point['lng'])
                ? [(float) $point['lng'], (float) $point['lat']]
                : $point,
            $decoded,
        );
    }
};
