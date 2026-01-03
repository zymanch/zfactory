<?php
use yii\helpers\Url;
use yii\helpers\Json;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Map - <?= htmlspecialchars($region->name) ?></title>
    <link href="/css/admin-map-editor.css?v=<?= Yii::$app->params['asset_version'] ?? 1 ?>" rel="stylesheet">
</head>
<body>
    <div id="sprite-coords" class="sprite-coords">X: 0, Y: 0</div>
    <a href="<?= Url::to(['/admin/index']) ?>" id="back-button" class="back-button">← Back to Admin</a>
    <div id="admin-controls-hint" class="controls-hint">
        <strong>Горячие клавиши:</strong><br>
        <kbd>L</kbd> Ландшафты | <kbd>D</kbd> Депозиты | <kbd>ESC</kbd> Отмена
    </div>
    <div id="game-container"></div>

    <script>
        window.REGION_ID = <?= $region->region_id ?>;
        window.REGION_NAME = <?= Json::encode($region->name) ?>;
    </script>
    <script src="/js/admin-map-editor.js?v=<?= Yii::$app->params['asset_version'] ?? 1 ?>"></script>
</body>
</html>
