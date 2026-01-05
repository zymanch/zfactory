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
            'index' => \commands\actions\game\Index::class,
            'entities' => \commands\actions\game\Entities::class,
            'deposits' => \commands\actions\game\Deposits::class,
            'config' => \commands\actions\game\Config::class,
            'entity-resources' => \commands\actions\game\EntityResources::class,
            'save-state' => \commands\actions\game\SaveState::class,
            'finish-construction' => \commands\actions\game\FinishConstruction::class,
            'add-user-resource' => \commands\actions\game\AddUserResource::class,
            'save-manipulator-config' => \commands\actions\game\SaveManipulatorConfig::class,
        ];
    }
}
