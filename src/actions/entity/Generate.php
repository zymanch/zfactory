<?php

namespace actions\entity;

use actions\ConsoleAction;
use helpers\EntityAtlasGenerator;
use Yii;

/**
 * Generate texture atlases for all entity types
 * Usage: php yii entity/generate
 */
class Generate extends ConsoleAction
{
    public function run()
    {
        $this->stdout("Generating entity texture atlases...\n\n");

        $basePath = Yii::getAlias('@app/..');
        $generator = new EntityAtlasGenerator($basePath);
        $generator->generateAllAtlases();

        $this->stdout("\nDone! Entity atlases generated.\n");
        $this->stdout("Run 'npm run assets' to rebuild game assets.\n");

        return 0;
    }
}
