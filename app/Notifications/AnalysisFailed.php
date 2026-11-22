<?php

namespace App\Notifications;

use App\Models\Field;
use Illuminate\Notifications\Notification;

class AnalysisFailed extends Notification
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
            'title' => 'Analiza AI nie powiodła się',
            'message' => sprintf(
                'Nie udało się przeanalizować pola %s. Spróbuj ponownie później.',
                $this->field->name,
            ),
            'level' => 'danger',
            'url' => route('fields.analytics', $this->field->id, false),
        ];
    }
}
