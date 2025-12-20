<?php

$secure = __DIR__ . '/../config.secure.json';
$secure = json_decode(file_get_contents($secure), 1);
if (!$secure) {
    print 'Secure config has wrong format';
    die();
}

if (!file_exists(__DIR__ . '/../config.local.php')) {
    print 'Local config not exist';
    die();
}

// Load app configs from config.local (depends from environment)
include(__DIR__ . '/../vendor/yiisoft/yii2/helpers/BaseArrayHelper.php');
include(__DIR__ . '/../vendor/yiisoft/yii2/helpers/ArrayHelper.php');
$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../static_config.php'),
    require(__DIR__ . '/../config.local.php')
);

// Initialize application
defined('YII_DEBUG') or define('YII_DEBUG', $config['params']['debug']);
defined('YII_ENV') or define('YII_ENV', $config['params']['env']);

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

Yii::setAlias('@backend', dirname(__DIR__) . '/src');
Yii::setAlias('@tests', dirname(__DIR__) . '/tests');

$yii = new yii\web\Application($config);
$yii->run();
