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
 * @property string $analysis_type
 * @property Carbon $analysis_date
 * @property array<string, mixed>|null $results
 * @property array<int, mixed>|null $recommendations
 * @property array<string, mixed>|null $parameters
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Field $field
 */
final class Analytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'field_id',
        'analysis_type',
        'analysis_date',
        'results',
        'recommendations',
        'parameters',
    ];

    protected function casts(): array
    {
        return [
            'analysis_date' => 'date',
            'results' => 'array',
            'parameters' => 'array',
            'recommendations' => 'array',
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
