<?php

namespace controllers;

use yii\web\Controller;
use yii\filters\AccessControl;

class SiteController extends Controller
{
    public $layout = false;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'login'],
                        'roles' => ['?', '@'], // Guest and authenticated
                    ],
                    [
                        'allow' => true,
                        'actions' => ['logout'],
                        'roles' => ['@'], // Only authenticated
                    ],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'index' => \commands\actions\site\Index::class,
            'login' => \commands\actions\site\Login::class,
            'logout' => \commands\actions\site\Logout::class,
        ];
    }
}
