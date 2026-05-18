<?php

return [
    'app' => [
        'name' => 'KickOff Elite',
        'url' => 'http://localhost',
        'base_path' => '/kickoff',
        'min_age' => 18,
        'session_name' => 'kickoff_elite_session',
        'mapbox_token' => 'PASTE_YOUR_MAPBOX_TOKEN_HERE',
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'football_simple',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'from_address' => 'your-email@gmail.com',
        'from_name' => 'KickOff Elite',
        'smtp' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'your-email@gmail.com',
            'password' => 'YOUR_GMAIL_APP_PASSWORD',
            'encryption' => 'tls',
            'auth' => true,
        ],
    ],
];

