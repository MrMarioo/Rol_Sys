<?php

namespace App\Models;

use App\Traits\OrderableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Crop extends Model
{
    use HasFactory, OrderableTrait;

    protected $fillable = ['name', 'description', 'optimal_conditions'];

    protected $casts = [
        'optimal_conditions' => 'array',
    ];

    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(Field::class, 'field_crops')
            ->withPivot(
                'planting_date',
                'expected_harvest_date',
                'actual_harvest_date',
                'yield',
                'status',
            )
            ->withTimestamps();
    }
    public function getSelect2labelAttribute(): string
    {
        return $this->name;
    }
    public function scopeQuerySearch(Builder $query): void
    {
        $query
            ->when(request()->input('term'), function ($query, $search) {
                $query->where(
                    fn($q) => $q->where('name', 'like', '%' . $search . '%'),
                );
            })
            ->queryOrder();
    }
}
