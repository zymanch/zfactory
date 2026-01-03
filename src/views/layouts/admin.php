<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZFactory - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/admin.css?v=<?= Yii::$app->params['asset_version'] ?? 1 ?>" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand">ZFactory Admin</span>
            <div class="d-flex">
                <span class="text-white me-3"><?= Yii::$app->user->identity->username ?></span>
                <a href="<?= \yii\helpers\Url::to(['/game/index']) ?>" class="btn btn-sm btn-outline-light me-2">Game</a>
                <a href="<?= \yii\helpers\Url::to(['/site/logout']) ?>" class="btn btn-sm btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?= $content ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
