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

    public function scopeQuerySearch($query)
    {
        return $query
            ->when(request('search'), function ($q, $search) {
                if (is_string($search)) {
                    $q->where(function ($subQuery) use ($search) {
                        $subQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('location', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                }
            })
            ->when(request('name'), function ($q, $name) {
                $q->where('name', 'like', "%{$name}%");
            })
            ->when(request('location'), function ($q, $location) {
                $q->where('location', 'like', "%{$location}%");
            })
            ->when(request('description'), function ($q, $description) {
                $q->where('description', 'like', "%{$description}%");
            })
            ->when(request('size_min'), function ($q, $sizeMin) {
                $q->where('size', '>=', $sizeMin);
            })
            ->when(request('size_max'), function ($q, $sizeMax) {
                $q->where('size', '<=', $sizeMax);
            })
            ->when(request('status'), function ($q, $status) {
                $q->where('status', $status);
            })
            ->when(request('user_id', auth()->id()), function ($q, $userId) {
                $q->where('user_id', $userId);
            })
            ->when(request('crop_id'), function ($q, $cropId) {
                $q->whereHas('crops', function ($subQuery) use ($cropId) {
                    $subQuery->where('crop_id', $cropId);
                });
            })
            ->when(request('data_from'), function ($q, $dataFrom) {
                $q->whereHas('fieldData', function ($subQuery) use ($dataFrom) {
                    $subQuery->where('collection_date', '>=', $dataFrom);
                });
            })
            ->when(request('data_to'), function ($q, $dataTo) {
                $q->whereHas('fieldData', function ($subQuery) use ($dataTo) {
                    $subQuery->where('collection_date', '<=', $dataTo);
                });
            });
    }
}
