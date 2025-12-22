<?php

namespace controllers;

use yii\web\Controller;

class MapController extends Controller
{
    /**
     * Disable CSRF for AJAX endpoints
     */
    public function beforeAction($action)
    {
        if (in_array($action->id, ['tiles', 'create-entity', 'update-landing'])) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    public function actions()
    {
        return [
            'tiles' => \actions\map\Tiles::class,
            'create-entity' => \actions\map\CreateEntity::class,
            'update-landing' => \actions\map\UpdateLanding::class,
        ];
    }
}
