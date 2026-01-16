<?php

namespace controllers;

use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;

class AdminController extends Controller
{
    public $layout = 'admin';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function($rule, $action) {
                    if (!\Yii::$app->request->isAjax) {
                        throw new ForbiddenHttpException('Admin access required');
                    }
                    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    \Yii::$app->response->statusCode = 403;
                    \Yii::$app->response->data = [
                        'result' => 'error',
                        'error' => 'Admin access required'
                    ];
                    \Yii::$app->end();
                },
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function($rule, $action) {
                            return \Yii::$app->user->identity->is_admin ?? false;
                        }
                    ],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        // Disable CSRF for AJAX actions
        if (in_array($action->id, ['update-landing', 'create-deposit', 'save-technology', 'delete-technology'])) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    public function actions()
    {
        return [
            'index' => \controllers\actions\admin\Index::class,
            'regions' => \controllers\actions\admin\Regions::class,
            'users' => \controllers\actions\admin\Users::class,
            'edit-map' => \controllers\actions\admin\EditMap::class,
            'config' => \controllers\actions\admin\Config::class,
            'tiles' => \controllers\actions\admin\Tiles::class,
            'update-landing' => \controllers\actions\admin\UpdateLanding::class,
            'create-deposit' => \controllers\actions\admin\CreateDeposit::class,
            'technologies' => \controllers\actions\admin\Technologies::class,
            'save-technology' => \controllers\actions\admin\SaveTechnology::class,
            'delete-technology' => \controllers\actions\admin\DeleteTechnology::class,
        ];
    }
}
