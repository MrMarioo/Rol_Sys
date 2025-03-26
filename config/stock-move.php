<?php

return [
    'action' => [
        'backflush' => 1,
        'stock_adjustment' => 2,
        'stock_movement' => 3,
        'reception_po' => 4,
        'shipment' => 5,
        'return_po' => 6,
        'stock_scrap' => 7,
    ],
    'supported_manual_actions' => [2, 3, 7],
    'production_location_id' => env('PRODUCTION_LOCATION_ID', 291),
];
