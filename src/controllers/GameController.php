<?php

namespace controllers;

use yii\web\Controller;
use yii\filters\AccessControl;

class GameController extends Controller
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
            'index' => \actions\game\Index::class,
            'entities' => \actions\game\Entities::class,
            'deposits' => \actions\game\Deposits::class,
            'config' => \actions\game\Config::class,
            'entity-resources' => \actions\game\EntityResources::class,
            'save-state' => \actions\game\SaveState::class,
            'finish-construction' => \actions\game\FinishConstruction::class,
            'add-user-resource' => \actions\game\AddUserResource::class,
        ];
    }
}
