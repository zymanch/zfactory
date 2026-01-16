<?php

namespace controllers\actions\game;

use controllers\actions\JsonAction;
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
     * NEW (2026-01): resources is now a dictionary {entity_id => [...]}
     */
    private function saveEntityResources(array $resources)
    {
        if (empty($resources)) return;

        // Get all entity IDs from dictionary keys
        $entityIds = array_keys($resources);

        // Delete existing non-transport resources for these entities
        EntityResource::deleteAll([
            'and',
            ['entity_id' => $entityIds],
            ['position_px' => null]
        ]);

        // Insert new resources
        $rows = [];
        foreach ($resources as $entityId => $resourceList) {
            foreach ($resourceList as $r) {
                if (($r['amount'] ?? 0) > 0) {
                    $rows[] = [
                        $entityId,  // From dict key
                        $r['resource_id'],
                        $r['amount'],
                        null,  // position_px
                        null,  // from_direction
                        null   // status
                    ];
                }
            }
        }

        if (!empty($rows)) {
            Yii::$app->db->createCommand()->batchInsert(
                EntityResource::tableName(),
                ['entity_id', 'resource_id', 'amount', 'position_px', 'from_direction', 'status'],
                $rows
            )->execute();
        }
    }

    /**
     * Save crafting states
     * NEW (2026-01): states is now a dictionary {entity_id => {...}}
     */
    private function saveCraftingStates(array $states)
    {
        if (empty($states)) return;

        // Get all entity IDs from dictionary keys
        $entityIds = array_keys($states);

        // Delete existing states
        EntityCrafting::deleteAll(['entity_id' => $entityIds]);

        // Insert new states
        $rows = [];
        foreach ($states as $entityId => $s) {
            if (!empty($s['recipe_id']) && ($s['ticks_remaining'] ?? 0) > 0) {
                $rows[] = [
                    $entityId,  // From dict key
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
     * NEW (2026-01): states is now a dictionary {entity_id => {...}}
     */
    private function saveTransportStates(array $states)
    {
        if (empty($states)) return;

        foreach ($states as $entityId => $s) {
            // NEW: entity_id is now the dict key

            // Find existing transport state (position_px IS NOT NULL)
            $existing = EntityResource::find()
                ->where(['entity_id' => $entityId])
                ->andWhere(['not', ['position_px' => null]])
                ->one();

            if ($existing) {
                $existing->resource_id = $s['resource_id'] ?? null;
                $existing->amount = $s['amount'] ?? 0;
                $existing->position_px = $s['position_px'] ?? 0;
                $existing->from_direction = $s['from_direction'] ?? null;
                $existing->status = $s['status'] ?? 'empty';
                $existing->save(false);
            } else {
                $model = new EntityResource();
                $model->entity_id = $entityId;  // From dict key
                $model->resource_id = $s['resource_id'] ?? null;
                $model->amount = $s['amount'] ?? 0;
                $model->position_px = $s['position_px'] ?? 0;
                $model->from_direction = $s['from_direction'] ?? null;
                $model->status = $s['status'] ?? 'empty';
                $model->save(false);
            }
        }
    }

}
