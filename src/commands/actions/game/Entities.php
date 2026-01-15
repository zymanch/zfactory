<?php

namespace commands\actions\game;

use commands\actions\JsonAction;
use models\Entity;
use models\ShipEntity;
use models\Region;
use models\EntityResource;
use models\EntityCrafting;

/**
 * AJAX: Load all entities (called once on init)
 * Merges island (Entity) and ship (ShipEntity) entities into single array
 */
class Entities extends JsonAction
{
    /**
     * Get entity resources grouped by entity_id
     * @param array $entityIds
     * @return array ['entity_id' => [['resource_id' => ..., 'amount' => ...], ...]]
     */
    protected function getEntityResourcesGrouped($entityIds)
    {
        if (empty($entityIds)) {
            return [];
        }

        $rows = EntityResource::find()
            ->where(['entity_id' => $entityIds])
            ->andWhere(['position_px' => null])  // Building storage only
            ->select(['entity_id', 'resource_id', 'amount'])
            ->asArray()
            ->all();

        $grouped = [];
        foreach ($rows as $row) {
            $entityId = (int)$row['entity_id'];
            if (!isset($grouped[$entityId])) {
                $grouped[$entityId] = [];
            }
            $grouped[$entityId][] = [
                'resource_id' => (int)$row['resource_id'],
                'amount' => (int)$row['amount']
            ];
        }
        return $grouped;
    }

    /**
     * Get crafting states indexed by entity_id
     * @param array $entityIds
     * @return array ['entity_id' => ['recipe_id' => ..., 'ticks_remaining' => ...]]
     */
    protected function getCraftingStatesIndexed($entityIds)
    {
        if (empty($entityIds)) {
            return [];
        }

        $rows = EntityCrafting::find()
            ->where(['entity_id' => $entityIds])
            ->select(['entity_id', 'recipe_id', 'ticks_remaining'])
            ->asArray()
            ->all();

        $indexed = [];
        foreach ($rows as $row) {
            $entityId = (int)$row['entity_id'];
            $indexed[$entityId] = [
                'recipe_id' => (int)$row['recipe_id'],
                'ticks_remaining' => (int)$row['ticks_remaining']
            ];
        }
        return $indexed;
    }

    /**
     * Get transport states indexed by entity_id
     * @param array $entityIds
     * @return array ['entity_id' => ['resource_id' => ..., 'amount' => ..., ...]]
     */
    protected function getTransportStatesIndexed($entityIds)
    {
        if (empty($entityIds)) {
            return [];
        }

        $rows = EntityResource::find()
            ->where(['entity_id' => $entityIds])
            ->andWhere(['not', ['position_px' => null]])  // Transport only
            ->select(['entity_id', 'resource_id', 'amount', 'position_px', 'from_direction', 'status'])
            ->asArray()
            ->all();

        $indexed = [];
        foreach ($rows as $row) {
            $entityId = (int)$row['entity_id'];
            $indexed[$entityId] = [
                'resource_id' => (int)$row['resource_id'],
                'amount' => (int)$row['amount'],
                'position_px' => (int)$row['position_px'],
                'from_direction' => $row['from_direction'],
                'status' => $row['status']
            ];
        }
        return $indexed;
    }

    public function run()
    {
        // Get current region ID
        $currentRegionId = 1; // Default
        $userId = null;
        if (!$this->isGuest()) {
            $currentRegionId = (int)$this->getUser()->current_region_id;
            $userId = (int)$this->getUser()->user_id;
        }

        // Get island entities for current region
        $islandEntities = $this->castNumericFieldsArray(
            Entity::find()
                ->select(['entity_id', 'entity_type_id', 'state', 'durability', 'x', 'y'])
                ->where(['region_id' => $currentRegionId])
                ->asArray()
                ->all(),
            ['entity_id', 'entity_type_id', 'durability', 'x', 'y']
        );

        // Load all entity states in 3 queries
        $entityIds = array_column($islandEntities, 'entity_id');

        $entityResources = $this->getEntityResourcesGrouped($entityIds);
        $craftingStates = $this->getCraftingStatesIndexed($entityIds);
        $transportStates = $this->getTransportStatesIndexed($entityIds);

        // Attach states to island entities
        foreach ($islandEntities as &$entity) {
            $entityId = $entity['entity_id'];

            // Add resources if exists
            if (isset($entityResources[$entityId])) {
                $entity['resources'] = $entityResources[$entityId];
            }

            // Add crafting state if exists
            if (isset($craftingStates[$entityId])) {
                $entity['craftingState'] = $craftingStates[$entityId];
            }

            // Add transport state if exists
            if (isset($transportStates[$entityId])) {
                $entity['transportState'] = $transportStates[$entityId];
            }
        }
        unset($entity);  // Break reference

        $entities = $islandEntities;

        // Get ship entities for current user (if logged in)
        if ($userId) {
            // Get region's ship attachment point
            $region = Region::findOne($currentRegionId);
            $shipAttachX = $region ? (int)$region->ship_attach_x : 0;
            $shipAttachY = $region ? (int)$region->ship_attach_y : 0;

            // Get ship entities
            $shipEntities = ShipEntity::find()
                ->select(['ship_entity_id', 'entity_type_id', 'state', 'durability', 'x', 'y'])
                ->where(['user_id' => $userId])
                ->asArray()
                ->all();

            // Convert ship coordinates to world coordinates and add to entities
            foreach ($shipEntities as $shipEntity) {
                $entities[] = [
                    'entity_id' => 'ship_' . $shipEntity['ship_entity_id'], // Prefix to distinguish from island entities
                    'entity_type_id' => (int)$shipEntity['entity_type_id'],
                    'state' => $shipEntity['state'],
                    'durability' => (int)$shipEntity['durability'],
                    'x' => (int)$shipEntity['x'] + $shipAttachX, // Convert to world coordinates
                    'y' => (int)$shipEntity['y'] + $shipAttachY,
                ];
            }
        }

        return $this->success([
            'entities' => $entities,
        ]);
    }
}
