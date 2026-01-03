<?php

namespace controllers;

use yii\web\Controller;
use yii\filters\AccessControl;

class ResearchController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function ($rule, $action) {
                    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    \Yii::$app->response->statusCode = 401;
                    \Yii::$app->response->data = [
                        'result' => 'error',
                        'error' => 'Authentication required'
                    ];
                    \Yii::$app->end();
                },
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'tree' => \actions\research\Tree::class,
            'unlock' => \actions\research\Unlock::class,
        ];
    }
}
