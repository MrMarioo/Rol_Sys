<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Notifications\Notification;

class ReportGenerated extends Notification
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
            'title' => 'Raport wygenerowany',
            'message' => sprintf('Raport „%s” jest gotowy do pobrania.', $this->report->title),
            'level' => 'success',
            'url' => route('reports.show', $this->report->id, false),
        ];
    }
}
