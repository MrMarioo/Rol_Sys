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

    public function scopeQuerySearch($query)
    {
        return $query
            ->when(request('search'), function ($q, $search) {
                if (is_string($search)) {
                    $q->where(function ($subQuery) use ($search) {
                        $subQuery
                            ->where('data_type', 'like', "%{$search}%")
                            ->orWhereHas('field', function ($fieldQuery) use (
                                $search,
                            ) {
                                $fieldQuery->where(
                                    'name',
                                    'like',
                                    "%{$search}%",
                                );
                            });
                    });
                }
            })
            ->when(request('field_id'), function ($q, $fieldId) {
                $q->where('field_id', $fieldId);
            })
            ->when(request('data_type'), function ($q, $dataType) {
                $q->where('data_type', $dataType);
            })
            ->when(request('date_from'), function ($q, $dateFrom) {
                $q->whereDate('collection_date', '>=', $dateFrom);
            })
            ->when(request('date_to'), function ($q, $dateTo) {
                $q->whereDate('collection_date', '<=', $dateTo);
            })
            ->when(request('latitude_min'), function ($q, $latMin) {
                $q->where('latitude', '>=', $latMin);
            })
            ->when(request('latitude_max'), function ($q, $latMax) {
                $q->where('latitude', '<=', $latMax);
            })
            ->when(request('longitude_min'), function ($q, $lngMin) {
                $q->where('longitude', '>=', $lngMin);
            })
            ->when(request('longitude_max'), function ($q, $lngMax) {
                $q->where('longitude', '<=', $lngMax);
            })
            ->when(request('user_id', auth()->id()), function ($q, $userId) {
                $q->whereHas('field', function ($fieldQuery) use ($userId) {
                    $fieldQuery->where('user_id', $userId);
                });
            });
    }
}
