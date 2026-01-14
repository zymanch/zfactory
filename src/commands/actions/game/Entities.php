<?php

namespace commands\actions\game;

use commands\actions\JsonAction;
use models\Entity;
use models\ShipEntity;
use models\Region;
use models\PipeSystem;
use models\PipeSystemMember;

/**
 * AJAX: Load all entities (called once on init)
 * Merges island (Entity) and ship (ShipEntity) entities into single array
 */
class Entities extends JsonAction
{
    protected function getPipeSystems($regionId)
    {
        $systems = PipeSystem::find()
            ->where(['region_id' => $regionId])
            ->asArray()
            ->all();

        $result = [];
        foreach ($systems as $system) {
            $systemId = (int)$system['pipe_system_id'];

            // Get all entity_ids in this system
            $members = PipeSystemMember::find()
                ->where(['pipe_system_id' => $systemId])
                ->select(['entity_id'])
                ->asArray()
                ->all();

            $entityIds = array_map(function($m) { return (int)$m['entity_id']; }, $members);

            $result[$systemId] = [
                'pipe_system_id' => $systemId,
                'resource_id' => $system['resource_id'] ? (int)$system['resource_id'] : null,
                'current_amount' => (int)$system['current_amount'],
                'max_capacity' => (int)$system['max_capacity'],
                'entity_ids' => $entityIds,
            ];
        }

        return $result;
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
            'pipeSystems' => $this->getPipeSystems($currentRegionId),
        ]);
    }
}
