<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $field_id
 * @property Carbon $collection_date
 * @property string $data_type
 * @property mixed $data
 * @property string|null $latitude
 * @property string|null $longitude
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Field $field
 */
final class FieldData extends Model
{
    use HasFactory;

    protected $table = 'field_data';

    protected $fillable = [
        'field_id',
        'collection_date',
        'data_type',
        'data',
        'latitude',
        'longitude',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'data' => 'array',
            'metadata' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /** @return BelongsTo<Field, $this> */
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->whereHas('field', fn (Builder $fieldQuery) => $fieldQuery->where('user_id', $user->id));
    }
}
