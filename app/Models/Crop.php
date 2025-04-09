<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Crop extends Model
{
    use HasFactory;

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
}
