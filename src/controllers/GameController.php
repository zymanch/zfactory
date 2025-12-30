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
            'deposits' => \actions\game\Deposits::class,
            'config' => \actions\game\Config::class,
            'entity-resources' => \actions\game\EntityResources::class,
            'save-state' => \actions\game\SaveState::class,
            'finish-construction' => \actions\game\FinishConstruction::class,
            'add-user-resource' => \actions\game\AddUserResource::class,
        ];
    }
}
