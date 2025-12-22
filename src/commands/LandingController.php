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
     * Generate transition sprites for all landing adjacencies
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

        // Build name lookup from image_url (remove .jpg extension)
        $landingNames = [];
        foreach ($landings as $id => $landing) {
            $name = pathinfo($landing['image_url'], PATHINFO_FILENAME);
            $landingNames[$id] = $name;
        }

        // Get all adjacency pairs
        $adjacencies = LandingAdjacency::find()->asArray()->all();

        if (empty($adjacencies)) {
            $this->stdout("No adjacency pairs found in database.\n");
            $this->stdout("Run migration first: php yii migrate\n");
            return 1;
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

            // Generate transitions in both directions
            // A->B (A is base, B is adjacent)
            $this->stdout("Generating: {$name1} -> {$name2}... ");
            $generated1 = $generator->generatePair($name1, $name2);
            $this->stdout(count($generated1) . " files\n");
            $totalGenerated += count($generated1);

            // B->A (B is base, A is adjacent)
            $this->stdout("Generating: {$name2} -> {$name1}... ");
            $generated2 = $generator->generatePair($name2, $name1);
            $this->stdout(count($generated2) . " files\n");
            $totalGenerated += count($generated2);
        }

        $this->stdout("\nDone! Generated {$totalGenerated} transition sprites.\n");
        $this->stdout("Output: public/assets/tiles/landing/transitions/\n");

        return 0;
    }

    /**
     * List all landing adjacencies
     * Usage: php yii landing/list-adjacencies
     */
    public function actionListAdjacencies()
    {
        $landings = Landing::find()
            ->indexBy('landing_id')
            ->asArray()
            ->all();

        $adjacencies = LandingAdjacency::find()->asArray()->all();

        if (empty($adjacencies)) {
            $this->stdout("No adjacencies found.\n");
            return 0;
        }

        $this->stdout("Landing Adjacencies:\n");
        $this->stdout("--------------------\n");

        foreach ($adjacencies as $adj) {
            $name1 = $landings[$adj['landing_id_1']]['name'] ?? 'Unknown';
            $name2 = $landings[$adj['landing_id_2']]['name'] ?? 'Unknown';
            $this->stdout("{$name1} <-> {$name2}\n");
        }

        $this->stdout("\nTotal: " . count($adjacencies) . " pairs\n");

        return 0;
    }
}
