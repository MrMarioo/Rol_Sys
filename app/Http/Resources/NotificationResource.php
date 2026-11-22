<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * @mixin DatabaseNotification
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = (array) $this->data;

        return [
            'id' => $this->id,
            'title' => $data['title'] ?? '',
            'message' => $data['message'] ?? '',
            'level' => $data['level'] ?? 'info',
            'url' => $data['url'] ?? null,
            'read' => $this->read_at !== null,
            'created_at' => $this->created_at?->diffForHumans(),
        ];
    }
}
