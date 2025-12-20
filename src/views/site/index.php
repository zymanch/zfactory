<?php
use yii\helpers\Url;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZFactory</title>
</head>
<body style="margin:0;padding:0;width:100vw;height:100vh;display:flex;align-items:center;justify-content:center;background:#1a1a2e;font-family:Arial,sans-serif;">
    <a href="<?= Url::to(['site/login']) ?>" style="padding:20px 60px;font-size:24px;font-weight:bold;color:#fff;background:#4a86e8;border:none;border-radius:8px;cursor:pointer;text-decoration:none;transition:background 0.2s;">
        ВОЙТИ!
    </a>
</body>
</html>
