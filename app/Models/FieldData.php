<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldData extends Model
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
    protected $casts = [
        'collection_date' => 'date',
        'data' => 'array',
        'metadata' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }
}
