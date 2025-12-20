<?php

namespace controllers;

use yii\web\Controller;

class SiteController extends Controller
{
    public $layout = false;

    public function actions()
    {
        return [
            'index' => \actions\site\Index::class,
            'login' => \actions\site\Login::class,
            'logout' => \actions\site\Logout::class,
        ];
    }
}
