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
            'generate' => \actions\landing\Generate::class,
            'scale-original' => \actions\landing\ScaleOriginal::class,
            'generate-ai' => \actions\landing\GenerateAi::class,
            'generate-sids' => \actions\landing\GenerateSids::class,
        ];
    }


}
