<?php

return [
    'network_1' => [
        'name'    => 'Network 1',
        'api_url' => env('API1_URL', 'http://localhost:8001'),
        'api_key' => env('API1_KEY', ''),
    ],
    'network_2' => [
        'name'    => 'Network 2',
        'api_url' => env('API2_URL', 'http://localhost:8002'),
        'api_key' => env('API2_KEY', ''),
    ],
];
