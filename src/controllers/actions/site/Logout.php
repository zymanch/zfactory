<?php

namespace controllers\actions\site;

use controllers\actions\Base;
use Yii;

/**
 * Logout
 */
class Logout extends Base
{
    public function run()
    {
        Yii::$app->user->logout();
        return $this->redirect(['site/index']);
    }
}
