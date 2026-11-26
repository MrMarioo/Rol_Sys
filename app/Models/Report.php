<?php

namespace App\Models;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $type
 * @property string|null $description
 * @property array<int, int> $fields_included
 * @property array<string, mixed> $parameters
 * @property array<string, mixed>|null $content
 * @property ReportStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
final class Report extends Model
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

    protected function casts(): array
    {
        return [
            'fields_included' => 'array',
            'parameters' => 'array',
            'content' => 'array',
            'status' => ReportStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return ReportType::tryFrom($this->type)?->label() ?? $this->type;
    }
}
