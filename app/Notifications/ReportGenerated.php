<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Notifications\Notification;

final class ReportGenerated extends Notification
{
    public function __construct(
        public readonly Report $report,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Report generated',
            'message' => sprintf('Report "%s" is ready to download.', $this->report->title),
            'level' => 'success',
            'url' => route(name: 'reports.show', parameters: $this->report->id, absolute: false),
        ];
    }
}
