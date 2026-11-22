<?php

namespace App\Notifications;

use App\Models\Analytics;
use Illuminate\Notifications\Notification;

class AnalysisCompleted extends Notification
{
    public function __construct(
        public readonly Analytics $analytics,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Analiza AI zakończona',
            'message' => sprintf('Analiza pola %s jest gotowa.', $this->analytics->field->name),
            'level' => 'success',
            'url' => route('analytics.show', $this->analytics->id, false),
        ];
    }
}
