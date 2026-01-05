<?php

namespace commands;

use helpers\LandingTransitionGenerator;
use models\Landing;
use models\LandingAdjacency;
use Yii;
use yii\helpers\Console;

/**
 * Landing management commands
 */
class LandingController extends \yii\console\Controller
{
    /**
     * Register standalone action classes
     */
    public function actions()
    {
        return [
            'generate' => \commands\actions\landing\Generate::class,
            'scale-original' => \commands\actions\landing\ScaleOriginal::class,
            'generate-ai' => \commands\actions\landing\GenerateAi::class,
            'generate-ai-flux' => \commands\actions\landing\GenerateAiFlux::class,
            'generate-sids' => \commands\actions\landing\GenerateSids::class,
        ];
    }


}
