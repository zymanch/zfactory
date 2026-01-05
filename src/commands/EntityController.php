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
            'generate' => \commands\actions\entity\Generate::class,
            'generate-states' => \commands\actions\entity\GenerateStates::class,
            'generate-ai-flux' => \commands\actions\entity\GenerateAiFlux::class,
        ];
    }
}
