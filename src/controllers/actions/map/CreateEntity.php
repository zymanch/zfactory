<?php

namespace controllers\actions\map;

use controllers\actions\JsonAction;
use models\Entity;
use models\ShipEntity;
use models\ShipLanding;
use models\EntityType;
use models\EntityResource;
use models\EntityTypeCost;
use models\Deposit;
use models\DepositType;
use models\Region;
use services\BuildingRules;
use Yii;

/**
 * AJAX: Create new entity (building placement)
 * POST params: entity_type_id, x, y (world coordinates), state, target_entity_id (optional)
 * Automatically detects ship vs island placement based on coordinates
 */
class CreateEntity extends JsonAction
{
    public function run()
    {
        if (!Yii::$app->request->isPost) {
            return $this->error('POST required');
        }

        $data = $this->getBodyParams();

        // Check for mass creation mode
        if (isset($data['entities']) && is_array($data['entities'])) {
            return $this->createMultipleEntities($data['entities']);
        }

        // Backward compatibility: single entity creation
        return $this->createSingleEntity($data);
    }

    /**
     * Create single entity (backward compatibility)
     */
    private function createSingleEntity($data)
    {
        $userId = Yii::$app->user->id;
        $currentRegionId = (int)$this->getUser()->current_region_id;
        $region = Region::findOne($currentRegionId);

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $result = $this->createEntityInternal($data, $userId, $currentRegionId, $region);

            if (!$result['success']) {
                throw new \Exception($result['error']);
            }

            $transaction->commit();

            return $this->success([
                'entity' => $result['entity'],
                'depositsRemoved' => $result['depositsRemoved'],
                'isShip' => $result['isShip'],
                'targetRemoved' => $result['targetRemoved'] ?? false,
                'oldHqRemoved' => $result['oldHqRemoved'] ?? null,
            ]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->error($e->getMessage());
        }
    }

    /**
     * Create multiple entities in one transaction
     */
    private function createMultipleEntities($entitiesData)
    {
        $userId = Yii::$app->user->id;
        $currentRegionId = (int)$this->getUser()->current_region_id;
        $region = Region::findOne($currentRegionId);

        $createdEntities = [];
        $totalDepositsRemoved = [];
        $transaction = Yii::$app->db->beginTransaction();

        try {
            foreach ($entitiesData as $entityData) {
                $result = $this->createEntityInternal(
                    $entityData,
                    $userId,
                    $currentRegionId,
                    $region
                );

                if ($result['success']) {
                    $createdEntities[] = $result['entity'];
                    if (!empty($result['depositsRemoved'])) {
                        $totalDepositsRemoved = array_merge(
                            $totalDepositsRemoved,
                            $result['depositsRemoved']
                        );
                    }
                }
                // Skip invalid entities (don't break transaction)
            }

            $transaction->commit();

            return $this->success([
                'entities' => $createdEntities,
                'depositsRemoved' => $totalDepositsRemoved,
                'count' => count($createdEntities)
            ]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->error($e->getMessage());
        }
    }

    /**
     * Internal method to create a single entity
     * Extracted from run() for reuse in both single and mass creation
     */
    private function createEntityInternal($data, $userId, $currentRegionId, $region)
    {
        // Validate required fields
        $entityTypeId = (int) ($data['entity_type_id'] ?? 0);
        $worldX = (int) ($data['x'] ?? 0);
        $worldY = (int) ($data['y'] ?? 0);
        $state = $data['state'] ?? 'blueprint';
        $targetEntityId = isset($data['target_entity_id']) ? (int) $data['target_entity_id'] : null;

        if (!$entityTypeId) {
            return ['success' => false, 'error' => 'entity_type_id required'];
        }

        // Check entity type exists
        $entityType = EntityType::findOne($entityTypeId);
        if (!$entityType) {
            return ['success' => false, 'error' => 'Invalid entity_type_id'];
        }

        // Special rules for HQ building
        $isHqBuilding = ($entityType->subtype === 'hq');

        // Use provided region and user data
        $shipAttachX = $region ? (int)$region->ship_attach_x : 0;
        $shipAttachY = $region ? (int)$region->ship_attach_y : 0;

        // Determine if placement is on ship or island
        $isShipPlacement = false;
        $shipRelativeX = $worldX - $shipAttachX;
        $shipRelativeY = $worldY - $shipAttachY;

        // Check if there's a ship landing at this position
        $shipLanding = ShipLanding::findOne([
            'user_id' => $userId,
            'x' => $shipRelativeX,
            'y' => $shipRelativeY,
        ]);

        if ($shipLanding) {
            $isShipPlacement = true;
        }

        // For ship entity types (type='ship'), placement is on ship if coordinates are within ship bounds
        // (Ship entities CREATE ship landings, not built ON existing landings)
        if ($entityType->type === 'ship' && $shipRelativeX >= 0 && $shipRelativeY >= 0) {
            $isShipPlacement = true;
        }

        // Check if user can afford building (BEFORE placement rules)
        if (!EntityTypeCost::canAfford($userId, $entityTypeId)) {
            return ['success' => false, 'error' => 'Not enough resources to build this'];
        }

        // HQ Rule 2: Can only build HQ on ship landing
        if ($isHqBuilding && !$isShipPlacement) {
            return ['success' => false, 'error' => 'HQ can only be built on ship floors'];
        }

        // Check if coordinate is visible (only for island placement)
        if (!$isShipPlacement) {
            $isVisible = \services\FogOfWarService::isCoordinateVisible($worldX, $worldY, $currentRegionId);
            if (!$isVisible) {
                return ['success' => false, 'error' => 'Cannot build in fog of war'];
            }
        }

        // Check building rules using behavior system (world coordinates)
        // This checks: landing buildability, entity collision, resource target
        $ruleCheck = BuildingRules::canPlace($entityTypeId, $worldX, $worldY, null, $currentRegionId);
        $targetEntity = $ruleCheck['targetEntity'];

        // If building placement is not allowed
        if (!$ruleCheck['allowed']) {
            return ['success' => false, 'error' => $ruleCheck['error'] ?? 'Cannot place here'];
        }

        // HQ Rule 1: Delete old HQ when building new one
        if ($isHqBuilding) {
            // Find existing HQ for this user
            $existingHq = null;
            if ($isShipPlacement) {
                $existingHq = ShipEntity::find()
                    ->joinWith('entityType')
                    ->where(['ship_entity.user_id' => $userId])
                    ->andWhere(['entity_type.subtype' => 'hq'])
                    ->one();
            } else {
                $existingHq = Entity::find()
                    ->joinWith('entityType')
                    ->where(['entity.region_id' => $currentRegionId])
                    ->andWhere(['entity_type.subtype' => 'hq'])
                    ->one();
            }

            // We will delete the existing HQ in the transaction below
        }

        // Validate target_entity_id matches the rule check (for mining entities)
        if ($targetEntityId && $targetEntity && $targetEntity->entity_id != $targetEntityId) {
            return ['success' => false, 'error' => 'Target entity mismatch'];
        }

        // No transaction here - managed by calling methods
        try {
            // HQ Rule 1: Delete old HQ if exists (before creating new one)
            $oldHqRemoved = null;
            if ($isHqBuilding && isset($existingHq) && $existingHq) {
                // Store info before deletion
                $oldHqRemoved = [
                    'entity_id' => $isShipPlacement ? 'ship_' . $existingHq->ship_entity_id : $existingHq->entity_id,
                    'x' => $existingHq->x,
                    'y' => $existingHq->y,
                ];

                if (!$existingHq->delete()) {
                    throw new \Exception('Failed to remove existing HQ');
                }
            }

            // Deduct building cost from user resources
            EntityTypeCost::deductCost($userId, $entityTypeId);

            // Create entity (ship or island)
            if ($isShipPlacement) {
                // Create ship entity
                $entity = new ShipEntity();
                $entity->user_id = $userId;
                $entity->entity_type_id = $entityTypeId;
                $entity->x = $shipRelativeX;
                $entity->y = $shipRelativeY;
                $entity->state = $state;
                $entity->durability = $state === 'built' ? $entityType->max_durability : 0;

                if (!$entity->save()) {
                    throw new \Exception('Failed to save ship entity: ' . json_encode($entity->errors));
                }

                // Note: ShipLanding is NOT created here
                // It will be created in FinishConstruction.php when ship entity converts to landing
                // (via converts_to_landing_id field in entity_type table)

                $entityIdResponse = 'ship_' . $entity->ship_entity_id;
            } else {
                // Create island entity
                $entity = new Entity();
                $entity->entity_type_id = $entityTypeId;
                $entity->x = $worldX;
                $entity->y = $worldY;
                $entity->state = $state;
                $entity->durability = $state === 'built' ? $entityType->max_durability : 0;
                $entity->construction_progress = $state === 'built' ? 100 : 0;
                $entity->region_id = $currentRegionId;

                if (!$entity->save()) {
                    throw new \Exception('Failed to save entity: ' . json_encode($entity->errors));
                }

                $entityIdResponse = $entity->entity_id;
            }

            $targetRemoved = false;
            $depositsRemoved = [];

            // Transfer resources from target entity to new entity (only for island entities)
            if ($targetEntity && !$isShipPlacement) {
                // Get resources from target entity
                $resources = EntityResource::findAll(['entity_id' => $targetEntity->entity_id]);

                // Transfer each resource to new entity
                foreach ($resources as $resource) {
                    $newResource = new EntityResource();
                    $newResource->entity_id = $entity->entity_id;
                    $newResource->resource_id = $resource->resource_id;
                    $newResource->amount = $resource->amount;
                    if (!$newResource->save()) {
                        throw new \Exception('Failed to transfer resources');
                    }
                }

                // Delete target entity (cascades to delete its resources)
                if (!$targetEntity->delete()) {
                    throw new \Exception('Failed to remove target entity');
                }

                $targetRemoved = true;
            }

            // Process deposits to remove (for extraction buildings: sawmill, quarry, drill, mine)
            // Only for island entities
            if (!$isShipPlacement && isset($ruleCheck['depositsToRemove']) && !empty($ruleCheck['depositsToRemove'])) {
                $depositIds = $ruleCheck['depositsToRemove'];
                $deposits = Deposit::findAll(['deposit_id' => $depositIds]);

                foreach ($deposits as $deposit) {
                    // Get deposit type to know which resource to add
                    $depositType = DepositType::findOne($deposit->deposit_type_id);
                    if (!$depositType) {
                        continue;
                    }

                    // Find existing entity_resource or create new one
                    $entityResource = EntityResource::findOne([
                        'entity_id' => $entity->entity_id,
                        'resource_id' => $depositType->resource_id,
                    ]);

                    if (!$entityResource) {
                        $entityResource = new EntityResource();
                        $entityResource->entity_id = $entity->entity_id;
                        $entityResource->resource_id = $depositType->resource_id;
                        $entityResource->amount = 0;
                    }

                    // Add deposit's resource amount to entity
                    $entityResource->amount += $deposit->resource_amount;

                    if (!$entityResource->save()) {
                        throw new \Exception('Failed to transfer deposit resources');
                    }

                    // Store deposit info for response
                    $depositsRemoved[] = [
                        'deposit_id' => $deposit->deposit_id,
                        'x' => $deposit->x,
                        'y' => $deposit->y,
                    ];

                    // Delete deposit
                    if (!$deposit->delete()) {
                        throw new \Exception('Failed to remove deposit');
                    }
                }
            }

            // Recalculate electricity systems if electricity entity was created (only for island, not ship)
            if (!$isShipPlacement && $entityType->type === 'electricity') {
                \bl\electricity\ElectricitySystemManager::recalculateSystems($currentRegionId);
            }

            // Invalidate fog cache if eye entity was created (only for built entities)
            if (!$isShipPlacement && $entityType->type === 'eye' && $state === 'built') {
                \services\FogOfWarService::invalidateCache($currentRegionId);
            }

            // Return success result (transaction managed by calling method)
            return [
                'success' => true,
                'entity' => [
                    'entity_id' => $entityIdResponse,
                    'entity_type_id' => $entityTypeId,
                    'x' => $worldX,
                    'y' => $worldY,
                    'state' => $state,
                    'durability' => $entity->durability,
                ],
                'targetRemoved' => $targetRemoved,
                'depositsRemoved' => $depositsRemoved,
                'oldHqRemoved' => $oldHqRemoved,
                'isShip' => $isShipPlacement,
            ];

        } catch (\Exception $e) {
            // Let exception bubble up to calling method for transaction rollback
            throw $e;
        }
    }
}
