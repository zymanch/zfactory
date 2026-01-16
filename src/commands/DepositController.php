<?php

namespace commands;

use yii\console\Controller;

/**
 * Deposit management commands
 */
class DepositController extends Controller
{
    public function actions()
    {
        return [
            'generate-ai-flux' => \commands\deposit\GenerateAiFlux::class,
        ];
    }
}
