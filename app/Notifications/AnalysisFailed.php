<?php

namespace App\Notifications;

use App\Models\Field;
use Illuminate\Notifications\Notification;

final class AnalysisFailed extends Notification
{
    public function __construct(
        public readonly Field $field,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'AI analysis failed',
            'message' => sprintf(
                'Field %s could not be analysed. Please try again later.',
                $this->field->name,
            ),
            'level' => 'danger',
            'url' => route(name: 'fields.analytics', parameters: $this->field->id, absolute: false),
        ];
    }
}
