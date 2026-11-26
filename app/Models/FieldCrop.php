<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $field_id
 * @property int $crop_id
 * @property Carbon|null $planting_date
 * @property Carbon|null $expected_harvest_date
 * @property Carbon|null $actual_harvest_date
 * @property string|null $yield
 * @property string $status
 */
final class FieldCrop extends Pivot
{
    protected $table = 'field_crops';

    protected function casts(): array
    {
        return [
            'planting_date' => 'date',
            'expected_harvest_date' => 'date',
            'actual_harvest_date' => 'date',
        ];
    }
}
