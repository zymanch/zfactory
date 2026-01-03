<?php

namespace controllers;

use yii\web\Controller;
use yii\filters\AccessControl;

class MapController extends Controller
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
        if (in_array($action->id, ['tiles', 'create-entity', 'delete-entity'])) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    public function actions()
    {
        return [
            'tiles' => \actions\map\Tiles::class,
            'create-entity' => \actions\map\CreateEntity::class,
            'delete-entity' => \actions\map\DeleteEntity::class,
        ];
    }
}
