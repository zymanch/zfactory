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
            'generate' => \commands\entity\Generate::class,
            'generate-states' => \commands\entity\GenerateStates::class,
            'generate-ai-flux' => \commands\entity\GenerateAiFlux::class,
        ];
    }
}
