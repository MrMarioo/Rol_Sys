<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'description',
        'fields_included',
        'parameters',
        'content',
        'status',
    ];

    protected $casts = [
        'fields_included' => 'array',
        'parameters' => 'array',
        'content' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
    public function isGenerated(): bool
    {
        return $this->status === 'generated';
    }
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }
}
