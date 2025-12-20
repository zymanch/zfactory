<?php

namespace controllers;

use yii\web\Controller;

class GameController extends Controller
{
    public $layout = 'game';

    public function actions()
    {
        return [
            'index' => \actions\game\Index::class,
            'entities' => \actions\game\Entities::class,
            'config' => \actions\game\Config::class,
            'entity-resources' => \actions\game\EntityResources::class,
        ];
    }
}
