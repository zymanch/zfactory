<?php


return [
    'id' => 'sheer',
    'basePath' => __DIR__ . '/src',
    'vendorPath' => __DIR__ . '/vendor',
    'runtimePath' => __DIR__ . '/src/runtime',
    'controllerNamespace' => 'controllers',
    'defaultRoute' => 'site/index',
    'bootstrap' => array_filter([
        'log',
    ]),
    'modules' => [
        'debug' => [
            'class' => 'yii\debug\Module',
            'disableIpRestrictionWarning' => true,
            'allowedIPs' => [
                '127.0.0.1',
                '172.27.55.1',
                '185.120.71.24',
                '185.120.71.25',
                '185.120.71.2',
                '185.120.71.3',
                '185.120.71.37', // new lbweb
                '185.120.71.38', // new lbweb
                '185.120.71.39', // new lbweb
                '185.120.71.68', // new vpn
                '185.120.71.87',
                '185.120.71.88',
                '45.83.94.95',
                '45.83.94.4',
            ],
        ]
    ],
    'timeZone' => 'Europe/Moscow',
    'params' => [
        'replicate_ai_api_key' => $secure['replicate']['token'],
        'tile_width' => 64,
        'tile_height' => 64,
        'asset_version' => 27,
        'auto_save_interval' => 60, // seconds between auto-saves
    ],

    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
        '@commands' => '@app/commands',
    ],
    'controllerMap' => [
        'migrate' => [
            'class' => 'commands\MigrateController',
            'migrationTable' => 'zfactory._migration',
            'migrationPath' => [
                '@app/migrations',
            ]
        ],
    ],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-backend',
            'cookieValidationKey' => 'jadFKIsfc0zq_4d5YMIgUmxFpCVF2AkR',
            'csrfCookie' => [
                'httpOnly' => true,
                'secure' => true,
            ],
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'user' => [
            'identityClass' => 'models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
            'loginUrl' => ['site/index'], // Redirect to homepage for unauthenticated users
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'AP_SESSID',
            'cookieParams' => [
                'httponly' => true,
                'secure' => true,
            ],
        ],
        'assetManager' => [
            'appendTimestamp' => true,
            'hashCallback' => function ($path) {
                $path = (is_file($path) ? dirname($path) : $path);

                return sprintf('%x', crc32($path . Yii::getVersion()));
            },
        ],
        'errorHandler' => [
            'errorAction' => 'error/index',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'db' => [
            'class' => '\yii\db\Connection',
            'dsn' => 'mysql:host=' . $secure['mysql']['mysqlcluster']['hostname'] . ';dbname=zfactory',
            'username' => $secure['mysql']['mysqlcluster']['username'],
            'password' => $secure['mysql']['mysqlcluster']['password'],
            'charset' => 'utf8mb4',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 3600,
            'schemaCache' => 'cache',
        ],
        'log' => [
            'flushInterval' => 1,
            'traceLevel' => 3,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'exportInterval' => 1,
                    'logVars' => [],
                ],
            ],
        ],
    ]
];