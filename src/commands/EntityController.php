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
            'generate' => \actions\entity\Generate::class,
            'generate-states' => \actions\entity\GenerateStates::class,
            'generate-ai' => \actions\entity\GenerateAi::class,
            'generate-ai-flux' => \actions\entity\GenerateAiFlux::class,
        ];
    }
}
