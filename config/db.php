<?php
$host = env('MYSQL_HOST');
$database = env('MYSQL_DATABASE');

return [
    'class'    => 'yii\db\Connection',
    'dsn'      => "mysql:host=$host;dbname=$database",
    'username' => env('MYSQL_WORKER_USER'),
    'password' => env('MYSQL_WORKER_PASSWORD'),
    'charset'  => 'utf8',
];
