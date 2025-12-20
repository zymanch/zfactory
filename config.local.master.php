<?php
return [
    'bootstrap'  => ['debug', 'log'],
    'modules'    => [
        'debug' => [
            'class' => 'yii\debug\Module',
        ],
        'gii'   => [
            'class'      => 'yii\gii\Module',
            'allowedIPs' => ['127.0.0.1', '::1']  //allowing ip's
        ],
    ],
    'params'     => [
        'env'              => 'dev',
        'debug'            => true,
    ],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-backend',
            'cookieValidationKey' => 'jadFKIsfc0zq_4d5YMIgUmxFpCVF2AkR',
            'csrfCookie' => [
                'httpOnly' => true,
                'secure' => false,
            ],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'AP_SESSID',
            'cookieParams' => [
                'httponly' => true,
                'secure' => false,
            ],
        ],
    ],
];