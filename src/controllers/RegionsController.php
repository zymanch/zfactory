<?php

namespace controllers;

use yii\web\Controller;
use yii\filters\AccessControl;

class RegionsController extends Controller
{
    public $layout = 'game';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function ($rule, $action) {
                    // For HTML pages (index), redirect to homepage
                    if (!\Yii::$app->request->isAjax) {
                        return \Yii::$app->response->redirect(['site/index'])->send();
                    }
                    // For AJAX, return JSON error
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
                        'roles' => ['@'], // Only authenticated
                    ],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'index' => '\controllers\actions\regions\Index',
            'list' => '\controllers\actions\regions\ListRegions',
            'travel' => '\controllers\actions\regions\Travel',
            'resources' => '\controllers\actions\regions\Resources',
        ];
    }
}
