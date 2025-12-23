<?php

namespace commands;

use helpers\LandingTransitionGenerator;
use models\Landing;
use models\LandingAdjacency;
use Yii;

/**
 * Landing management commands
 */
class LandingController extends \yii\console\Controller
{
    /**
     * Generate texture atlases for all landing types
     * Usage: php yii landing/generate
     */
    public function actionGenerate()
    {
        $this->stdout("Generating landing texture atlases...\n\n");

        $basePath = Yii::getAlias('@app/..');
        $generator = new LandingTransitionGenerator($basePath);
        $generator->generateAllAtlases();

        return 0;
    }

    /**
     * Generate transition sprites for all landing adjacencies (DEPRECATED - use actionGenerate instead)
     * Usage: php yii landing/generate-transitions
     */
    public function actionGenerateTransitions()
    {
        $this->stdout("Generating landing transition sprites...\n\n");

        // Get all landing types indexed by ID
        $landings = Landing::find()
            ->indexBy('landing_id')
            ->asArray()
            ->all();

        // Build name
        $landingNames = [];
        $fileNames = [];
        foreach ($landings as $id => $landing) {
            $landingNames[$id] = $landing['name'];
            $fileNames[$id] = $landing['image_url'];
        }

        // Get all adjacency pairs
        $adjacencies = LandingAdjacency::find()->asArray()->all();

        if (empty($adjacencies)) {
            $this->stdout("No adjacency pairs found in database.\n");
            $this->stdout("Run migration first: php yii migrate\n");
            return 1;
        }

        // Add fake adjacencies: sky (9) and island_edge (10) contact all real landings (1-8)
        $skyId = 9;
        $islandEdgeId = 10;

        foreach ($landings as $landing) {
            $landingId = $landing['landing_id'];
            if ($landingId == $skyId || $landingId == $islandEdgeId) {
                continue;
            }
            // Sky adjacencies (bidirectional)
            $adjacencies[] = ['landing_id_1' => $skyId, 'landing_id_2' => $landingId];


            // Island edge adjacencies (unidirectional - only island_edge below landing)
            $adjacencies[] = ['landing_id_1' => $islandEdgeId, 'landing_id_2' => $landingId];
        }

        // Create generator
        $basePath = Yii::getAlias('@app/..');
        $generator = new LandingTransitionGenerator($basePath);

        $totalGenerated = 0;

        foreach ($adjacencies as $adj) {
            $id1 = $adj['landing_id_1'];
            $id2 = $adj['landing_id_2'];

            $name1 = $landingNames[$id1] ?? null;
            $name2 = $landingNames[$id2] ?? null;

            if (!$name1 || !$name2) {
                $this->stdout("Warning: Unknown landing IDs: {$id1}, {$id2}\n");
                continue;
            }


            // A->B (A is base, B is adjacent)
            $this->stdout("Generating: {$name1} -> {$name2}... ");
            $generated1 = $generator->generatePair($fileNames[$id1], $fileNames[$id2]);
            $this->stdout(count($generated1) . " files\n");
            $totalGenerated += count($generated1);

            // B->A (B is base, A is adjacent)
            $this->stdout("Generating: {$name2} -> {$name1}... ");
            $generated2 = $generator->generatePair($fileNames[$id2], $fileNames[$id1]);
            $this->stdout(count($generated2) . " files\n");
            $totalGenerated += count($generated2);
        }

        foreach ($landings as $landing) {
            $landingId = $landing['landing_id'];
            if ($landingId == $skyId || $landingId == $islandEdgeId) {
                continue;
            }
            $name1 = $landingNames[$islandEdgeId] ?? null;
            $name2 = $landingNames[$landingId] ?? null;

            if (!$name1 || !$name2) {
                $this->stdout("Warning: Unknown landing IDs: {$islandEdgeId}, {$landingId}\n");
                continue;
            }

            // island_edge -> landing (island_edge below, landing above)
            $this->stdout("Generating: {$name1} -> {$name2} (top)... ");
            $generated = $generator->generateTopOnly($fileNames[$islandEdgeId], $fileNames[$landingId]);
            $this->stdout(count($generated) . " files\n");
            $totalGenerated += count($generated);

        }

        $this->stdout("\nDone! Generated {$totalGenerated} transition sprites.\n");
        $this->stdout("Output: public/assets/tiles/landing/transitions/\n");

        return 0;
    }

}
