<?php

namespace actions\site;

use actions\Base;
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
