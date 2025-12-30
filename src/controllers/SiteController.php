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
            'index' => \actions\site\Index::class,
            'login' => \actions\site\Login::class,
            'logout' => \actions\site\Logout::class,
        ];
    }
}
