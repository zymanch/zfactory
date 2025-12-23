<?php

namespace actions\landing;

use actions\ConsoleAction;
use helpers\LandingTransitionGenerator;
use Yii;

/**
 * Generate texture atlases for all landing types
 * Usage: php yii landing/generate
 */
class Generate extends ConsoleAction
{
    public function run()
    {
        $this->stdout("Generating landing texture atlases...\n\n");

        $basePath = Yii::getAlias('@app/..');
        $generator = new LandingTransitionGenerator($basePath);
        $generator->generateAllAtlases();

        return 0;
    }
}
