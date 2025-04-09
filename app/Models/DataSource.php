<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DataSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'configuration',
        'is_active',
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
    ];

    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(Field::class, 'field_data_sources')
            ->withPivot('settings')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
