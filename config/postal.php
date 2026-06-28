<?php

return [
    'main_db' => [
        'host' => env('POSTAL_DB_HOST'),
        'port' => env('POSTAL_DB_PORT', '3306'),
        'database' => env('POSTAL_DB_NAME'),
        'username' => env('POSTAL_DB_USER'),
        'password' => env('POSTAL_DB_PASS'),
    ],

    'message_db' => [
        'host' => env('POSTAL_MESSAGE_DB_HOST'),
        'port' => env('POSTAL_MESSAGE_DB_PORT', '3306'),
        'prefix' => env('POSTAL_MESSAGE_DB_PREFIX'),
        'username' => env('POSTAL_MESSAGE_DB_USER'),
        'password' => env('POSTAL_MESSAGE_DB_PASS'),
    ],
];
