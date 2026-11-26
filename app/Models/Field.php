<?php

namespace App\Models;

use App\Enums\FieldDataType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $location
 * @property string $size
 * @property string|null $description
 * @property array<int, array{0: float, 1: float}> $boundaries
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, FieldData> $fieldData
 * @property-read FieldData|null $latestNdviReading
 * @property-read Collection<int, Analytics> $analytics
 * @property-read Collection<int, Crop> $crops
 */
final class Field extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'location',
        'size',
        'description',
        'boundaries',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'boundaries' => 'array',
            'size' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<FieldData, $this> */
    public function fieldData(): HasMany
    {
        return $this->hasMany(FieldData::class);
    }

    /** @return HasOne<FieldData, $this> */
    public function latestNdviReading(): HasOne
    {
        return $this->hasOne(FieldData::class)->ofMany(
            column: ['collection_date' => 'max', 'id' => 'max'],
            aggregate: fn (Builder $query) => $query->where('data_type', FieldDataType::Ndvi->value),
        );
    }

    /** @return HasMany<Analytics, $this> */
    public function analytics(): HasMany
    {
        return $this->hasMany(Analytics::class);
    }

    /** @return BelongsToMany<Crop, $this, FieldCrop> */
    public function crops(): BelongsToMany
    {
        return $this->belongsToMany(Crop::class, 'field_crops')
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
