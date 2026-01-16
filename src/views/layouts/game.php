<?php

use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $content string */

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <link rel="icon" type="image/png" sizes="64x64" href="/favicon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #1a1a2e;
        }
        #game-container {
            width: 100%;
            height: 100%;
        }
        #game-container canvas {
            display: block;
        }
        #loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #1a1a2e;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        #loading-content {
            text-align: center;
        }
        #loading-text {
            color: #fff;
            font-family: Arial, sans-serif;
            font-size: 18px;
            margin-bottom: 20px;
        }
        #loading-bar-container {
            width: 400px;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        #loading-bar {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #4a90e2, #67b8ff);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        #debug-info {
            position: fixed;
            top: 10px;
            left: 10px;
            color: #0f0;
            font-family: monospace;
            font-size: 12px;
            background: rgba(0,0,0,0.7);
            padding: 10px;
            border-radius: 4px;
            z-index: 1000;
        }
        #controls-hint {
            position: fixed;
            bottom: 10px;
            left: 10px;
            color: #fff;
            font-family: monospace;
            font-size: 14px;
            background: rgba(0,0,0,0.7);
            padding: 10px;
            border-radius: 4px;
            z-index: 1000;
        }
    </style>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<?= $content ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
