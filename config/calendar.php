<?php

return [
    'resources' => [
        'mapping' => [
            'user' => [
                'title' => 'full_name',
            ],
            'project' => [
                'title' => 'code',
                'color' => 'colour',
            ],

            'project-child' => [
                'type' => 'project',
                'resource_type' => 'timesheet',
                'id' => 'timesheet.calendar_resource_id',
                'title' => 'code',
                'color' => 'colour',
            ],

            'user-child' => [
                'type' => 'user',
                'resource_type' => 'timesheet',
                'id' => 'timesheet.calendar_resource_id',
                'title' => 'full_name',
            ],
        ],
    ],

    'events' => [
        'mapping' => [
            'timesheet' => [
                'resource_type' => 'timesheet-',
                'resource_id' => 'calendar_resource_id',
                'title' => 'duration',
                'start' => 'clock_in',
                'end' => 'clock_out',
                'event_color' => 'project.colour',
            ],
        ],
    ],
];
