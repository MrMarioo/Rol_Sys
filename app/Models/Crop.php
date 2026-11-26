<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property array<string, mixed>|null $optimal_conditions
 * @property-read FieldCrop $pivot
 * @property-read Collection<int, Field> $fields
 */
final class Crop extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'optimal_conditions'];

    protected function casts(): array
    {
        return [
            'optimal_conditions' => 'array',
        ];
    }

    /** @return BelongsToMany<Field, $this, FieldCrop> */
    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(Field::class, 'field_crops')
            ->using(FieldCrop::class)
            ->withPivot(
                'planting_date',
                'expected_harvest_date',
                'actual_harvest_date',
                'yield',
                'status',
            )
            ->withTimestamps();
    }
}
