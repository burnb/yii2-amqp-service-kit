<?php

$params = require(__DIR__ . '/params.php');
$db = require(__DIR__ . '/db.php');

$config = [
    'timeZone'            => 'Europe/Moscow',
    'bootstrap'           => ['log'],
    'defaultRoute'        => 'main',
    'controllerNamespace' => 'app\commands',
    'components'          => [
        'log' => [
            'flushInterval' => 1,
            'traceLevel'    => YII_DEBUG ? 3 : 0,
            'targets'       => [
                [
                    'class'          => 'yii\log\FileTarget',
                    'levels'         => ['error'],
                    'logVars'        => [],
                    'exportInterval' => 1
                ],
                [
                    'class'          => 'yii\log\DbTarget',
                    'levels'         => ['error', 'warning'],
                    'logTable'       => 'log_error',
                    'logVars'        => [],
                    'exportInterval' => 1
                ],
                [
                    'class'          => 'app\components\SlackTarget',
                    'enabled'        => !env('SLACK_MONITORING_DISABLED_OPTIONAL'),
                    'levels'         => ['error', 'warning'],
                    'exportInterval' => 1,
                    'username'       => 'SERVICE | PROMO',
                    'viewErrorUrl'   => '@backendUrl/log/error/view',
                    'channel'        => env('SLACK_MONITORING_CHANNEL_OPTIONAL'),
                    'icon'           => env('SLACK_MONITORING_ICON_OPTIONAL'),
                    'endpoint'       => env('SLACK_MONITORING_ENDPOINT', (boolean)env('SLACK_MONITORING_DISABLED_OPTIONAL'))
                ]
            ],
        ],
        'db'  => $db,
    ],
    'params'              => $params
];

return $config;
