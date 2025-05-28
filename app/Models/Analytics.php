<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Analytics extends Model
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

    protected $casts = [
        'analysis_date' => 'date',
        'results' => 'array',
        'parameters' => 'array',
        'recommendations' => 'array',
    ];
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }
}
