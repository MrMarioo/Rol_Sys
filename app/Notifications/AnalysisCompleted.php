<?php

namespace App\Notifications;

use App\Models\Analytics;
use Illuminate\Notifications\Notification;

final class AnalysisCompleted extends Notification
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
            'title' => 'AI analysis completed',
            'message' => sprintf('Analysis of field %s is ready.', $this->analytics->field->name),
            'level' => 'success',
            'url' => route(name: 'analytics.show', parameters: $this->analytics->id, absolute: false),
        ];
    }
}
