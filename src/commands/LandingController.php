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
            'generate' => \commands\landing\Generate::class,
            'scale-original' => \commands\landing\ScaleOriginal::class,
            'generate-ai' => \commands\landing\GenerateAi::class,
            'generate-ai-flux' => \commands\landing\GenerateAiFlux::class,
            'generate-sids' => \commands\landing\GenerateSids::class,
        ];
    }


}
