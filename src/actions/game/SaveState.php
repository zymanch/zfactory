<?php

namespace actions\game;

use actions\JsonAction;
use models\EntityResource;
use models\EntityCrafting;
use Yii;
use yii\db\Expression;

/**
 * AJAX: Save transport system state
 * Saves entity resources, crafting states, and transport states
 */
class SaveState extends JsonAction
{
    public function run()
    {
        $data = $this->getBodyParams();

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Save entity resources
            if (!empty($data['entityResources'])) {
                $this->saveEntityResources($data['entityResources']);
            }

            // Save crafting states
            if (!empty($data['craftingStates'])) {
                $this->saveCraftingStates($data['craftingStates']);
            }

            // Save transporter states
            if (!empty($data['transporterStates'])) {
                $this->saveTransportStates($data['transporterStates']);
            }

            // Save manipulator states
            if (!empty($data['manipulatorStates'])) {
                $this->saveTransportStates($data['manipulatorStates']);
            }

            $transaction->commit();

            return $this->success();

        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->error('Failed to save state', $e->getMessage());
        }
    }

    /**
     * Save entity resources (buildings, storage)
     * Only for entities without transport state (position IS NULL)
     */
    private function saveEntityResources(array $resources)
    {
        if (empty($resources)) return;

        // Group by entity_id for efficient processing
        $entityIds = array_unique(array_column($resources, 'entity_id'));

        // Delete existing non-transport resources for these entities
        EntityResource::deleteAll([
            'and',
            ['entity_id' => $entityIds],
            ['position' => null]
        ]);

        // Insert new resources
        $rows = [];
        foreach ($resources as $r) {
            if (($r['amount'] ?? 0) > 0) {
                $rows[] = [
                    $r['entity_id'],
                    $r['resource_id'],
                    $r['amount'],
                    null,  // position
                    null,  // lateral_offset
                    null,  // arm_position
                    null   // status
                ];
            }
        }

        if (!empty($rows)) {
            Yii::$app->db->createCommand()->batchInsert(
                EntityResource::tableName(),
                ['entity_id', 'resource_id', 'amount', 'position', 'lateral_offset', 'arm_position', 'status'],
                $rows
            )->execute();
        }
    }

    /**
     * Save crafting states
     */
    private function saveCraftingStates(array $states)
    {
        if (empty($states)) return;

        $entityIds = array_column($states, 'entity_id');

        // Delete existing states
        EntityCrafting::deleteAll(['entity_id' => $entityIds]);

        // Insert new states
        $rows = [];
        foreach ($states as $s) {
            if (!empty($s['recipe_id']) && ($s['ticks_remaining'] ?? 0) > 0) {
                $rows[] = [
                    $s['entity_id'],
                    $s['recipe_id'],
                    $s['ticks_remaining']
                ];
            }
        }

        if (!empty($rows)) {
            Yii::$app->db->createCommand()->batchInsert(
                EntityCrafting::tableName(),
                ['entity_id', 'recipe_id', 'ticks_remaining'],
                $rows
            )->execute();
        }
    }

    /**
     * Save transport states (conveyors and manipulators)
     * Stored in entity_resource with transport fields populated
     */
    private function saveTransportStates(array $states)
    {
        if (empty($states)) return;

        foreach ($states as $s) {
            $entityId = $s['entity_id'];

            // Find existing transport state (position IS NOT NULL)
            $existing = EntityResource::find()
                ->where(['entity_id' => $entityId])
                ->andWhere(['not', ['position' => null]])
                ->one();

            if ($existing) {
                $existing->resource_id = $s['resource_id'] ?? null;
                $existing->amount = $s['amount'] ?? 0;
                $existing->position = $s['position'] ?? 0;
                $existing->lateral_offset = $s['lateral_offset'] ?? 0;
                $existing->arm_position = $s['arm_position'] ?? 0.5;
                $existing->status = $s['status'] ?? 'empty';
                $existing->save(false);
            } else {
                $model = new EntityResource();
                $model->entity_id = $entityId;
                $model->resource_id = $s['resource_id'] ?? null;
                $model->amount = $s['amount'] ?? 0;
                $model->position = $s['position'] ?? 0;
                $model->lateral_offset = $s['lateral_offset'] ?? 0;
                $model->arm_position = $s['arm_position'] ?? 0.5;
                $model->status = $s['status'] ?? 'empty';
                $model->save(false);
            }
        }
    }
}
