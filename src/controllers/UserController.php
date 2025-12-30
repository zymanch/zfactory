<?php

namespace controllers;

use yii\web\Controller;
use yii\filters\AccessControl;

class UserController extends Controller
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
                        'roles' => ['@'], // Only authenticated
                    ],
                ],
            ],
        ];
    }

    /**
     * Disable CSRF for AJAX endpoints
     */
    public function beforeAction($action)
    {
        if (in_array($action->id, ['save-build-panel', 'save-position'])) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    public function actions()
    {
        return [
            'save-build-panel' => \actions\user\SaveBuildPanel::class,
            'save-position' => \actions\user\SavePosition::class,
        ];
    }
}
