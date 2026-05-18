<?php

return [
    'app' => [
        'name' => 'KickOff Elite',
        'url' => 'http://localhost',
        'base_path' => 'auto',
        'min_age' => 18,
        'session_name' => 'kickoff_elite_session',
        'mapbox_token' => '',
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
        'from_address' => 'no-reply@kickoff.local',
        'from_name' => 'KickOff Elite',
        'smtp' => [
            'host' => '',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
            'auth' => true,
        ],
    ],
];

