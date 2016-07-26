<?php
return [
    'rabbit'     => [
        'host'     => env('RABBITMQ_DOMAIN'),
        'username' => env('RABBITMQ_WORKER_USER'),
        'password' => env('RABBITMQ_WORKER_PASSWORD'),
        'port'     => env('RABBITMQ_NODE_PORT'),
        'charset'  => 'utf8',
    ]
];
