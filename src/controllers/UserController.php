<?php

namespace controllers;

use yii\web\Controller;

class UserController extends Controller
{
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
