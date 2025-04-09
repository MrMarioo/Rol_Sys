<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Field extends Model
{
    use HasFactory;
    protected $table = 'fields';
    protected $fillable = [
        'user_id',
        'name',
        'location',
        'size',
        'description',
        'boundaries',
        'status',
    ];
    protected $casts = [
        'boundaries' => 'array',
        'size' => 'decimal:2',
    ];
    public function user(): belongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function fieldData(): HasMany
    {
        return $this->hasMany(FieldData::class);
    }
    public function analytics(): HasMany
    {
        return $this->hasMany(Analytics::class);
    }
    public function dataSource(): BelongsToMany
    {
        return $this->belongsToMany(DataSource::class, 'field_data_sources')
            ->withPivot('settings')
            ->withTimestamps();
    }
    public function crops(): BelongsToMany
    {
        return $this->belongsToMany(Crop::class, 'field_crops')
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
