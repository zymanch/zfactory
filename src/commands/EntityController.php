<?php

namespace commands;

/**
 * Entity management commands
 */
class EntityController extends \yii\console\Controller
{
    /**
     * Register standalone action classes
     */
    public function actions()
    {
        return [
            'generate-ai' => \actions\entity\GenerateAi::class,
        ];
    }
}
