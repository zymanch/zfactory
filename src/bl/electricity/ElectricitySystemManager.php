<?php

namespace bl\electricity;

use models\Entity;
use models\EntityType;
use Yii;

/**
 * ElectricitySystemManager - manages electricity networks in regions
 * Similar architecture to PipeSystemManager
 */
class ElectricitySystemManager
{
    /**
     * Recalculate all electricity systems for a region
     * Deletes old systems and creates new ones based on current entities
     */
    public static function recalculateSystems(int $regionId): void
    {
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            // 1. Delete old systems for this region
            $db->createCommand()
                ->delete('electricity_system', ['region_id' => $regionId])
                ->execute();

            // 2. Find all electricity entities in region (only built state)
            $electricityEntities = Entity::find()
                ->alias('e')
                ->innerJoin('entity_type et', 'e.entity_type_id = et.entity_type_id')
                ->where([
                    'et.type' => 'electricity',
                    'e.state' => 'built',
                    'e.region_id' => $regionId
                ])
                ->select(['e.entity_id', 'e.x', 'e.y', 'et.entity_type_id', 'et.power'])
                ->asArray()
                ->all();

            if (empty($electricityEntities)) {
                $transaction->commit();
                return;
            }

            // 3. Group entities into networks using BFS
            $visited = [];
            $systemNumber = 0;

            foreach ($electricityEntities as $entity) {
                $entityId = (int)$entity['entity_id'];

                if (isset($visited[$entityId])) {
                    continue; // Already in a system
                }

                // Find all connected entities using BFS
                $connectedEntities = self::findConnectedEntities($entityId, $electricityEntities, $visited);

                if (empty($connectedEntities)) {
                    continue;
                }

                // Create new system
                $db->createCommand()->insert('electricity_system', [
                    'region_id' => $regionId,
                    'total_capacity' => 0,
                    'total_electricity' => 0,
                ])->execute();

                $systemId = $db->getLastInsertID();

                // Calculate total capacity (sum of battery capacities)
                $totalCapacity = 0;
                $totalElectricity = 0;

                // Add members to system
                foreach ($connectedEntities as $connectedEntity) {
                    $connectedEntityId = (int)$connectedEntity['entity_id'];
                    $connectedEntityTypeId = (int)$connectedEntity['entity_type_id'];

                    // Determine role based on entity_type_id
                    $role = self::determineRole($connectedEntityTypeId);

                    $db->createCommand()->insert('electricity_system_member', [
                        'system_id' => $systemId,
                        'entity_id' => $connectedEntityId,
                        'role' => $role,
                    ])->execute();

                    // If it's a battery, add its capacity
                    if ($role === 'battery') {
                        $capacity = (int)$connectedEntity['power']; // power = capacity for batteries
                        $totalCapacity += $capacity;

                        // Get current electricity stored in this battery
                        $entityResource = $db->createCommand()
                            ->select('amount')
                            ->from('entity_resource')
                            ->where([
                                'entity_id' => $connectedEntityId,
                                'resource_id' => 400 // Electricity resource ID
                            ])
                            ->queryScalar();

                        if ($entityResource !== false) {
                            $totalElectricity += (int)$entityResource;
                        }
                    }
                }

                // Update system with totals
                $db->createCommand()->update('electricity_system', [
                    'total_capacity' => $totalCapacity,
                    'total_electricity' => $totalElectricity,
                ], ['system_id' => $systemId])->execute();

                $systemNumber++;
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * BFS algorithm to find all entities connected by power radius
     */
    private static function findConnectedEntities(int $startEntityId, array $allEntities, array &$visited): array
    {
        $connectedEntities = [];
        $queue = [$startEntityId];
        $visited[$startEntityId] = true;

        // Create a map for faster lookup
        $entitiesById = [];
        foreach ($allEntities as $entity) {
            $entitiesById[(int)$entity['entity_id']] = $entity;
        }

        while (!empty($queue)) {
            $currentId = array_shift($queue);

            if (!isset($entitiesById[$currentId])) {
                continue;
            }

            $currentEntity = $entitiesById[$currentId];
            $connectedEntities[] = $currentEntity;

            // Find all entities within power radius
            $neighbors = self::getEntitiesInRadius($currentEntity, $allEntities);

            foreach ($neighbors as $neighborId) {
                if (!isset($visited[$neighborId])) {
                    $visited[$neighborId] = true;
                    $queue[] = $neighborId;
                }
            }
        }

        return $connectedEntities;
    }

    /**
     * Get all entities within power radius of given entity
     */
    private static function getEntitiesInRadius(array $entity, array $allEntities): array
    {
        $entityX = (int)$entity['x'];
        $entityY = (int)$entity['y'];
        $radius = (int)$entity['power'];

        $entitiesInRadius = [];

        foreach ($allEntities as $otherEntity) {
            $otherId = (int)$otherEntity['entity_id'];
            $otherX = (int)$otherEntity['x'];
            $otherY = (int)$otherEntity['y'];

            if ($otherId === (int)$entity['entity_id']) {
                continue; // Skip self
            }

            // Calculate distance
            $dx = $otherX - $entityX;
            $dy = $otherY - $entityY;
            $distanceSq = $dx * $dx + $dy * $dy;
            $radiusSq = $radius * $radius;

            if ($distanceSq <= $radiusSq) {
                $entitiesInRadius[] = $otherId;
            }
        }

        return $entitiesInRadius;
    }

    /**
     * Determine entity role based on entity_type_id
     */
    private static function determineRole(int $entityTypeId): string
    {
        // Pylons: 900-902
        if ($entityTypeId >= 900 && $entityTypeId <= 902) {
            return 'pylon';
        }

        // Batteries: 910-912
        if ($entityTypeId >= 910 && $entityTypeId <= 912) {
            return 'battery';
        }

        // Generators: 920-922
        if ($entityTypeId >= 920 && $entityTypeId <= 922) {
            return 'generator';
        }

        return 'consumer';
    }

    /**
     * Add electricity to a system
     * @param int $systemId
     * @param int $amount
     * @return bool Success
     */
    public static function addElectricity(int $systemId, int $amount): bool
    {
        $db = Yii::$app->db;

        $system = $db->createCommand()
            ->select('*')
            ->from('electricity_system')
            ->where(['system_id' => $systemId])
            ->queryOne();

        if (!$system) {
            return false;
        }

        $newTotal = (int)$system['total_electricity'] + $amount;
        $capacity = (int)$system['total_capacity'];

        if ($newTotal > $capacity) {
            return false; // Exceeds capacity
        }

        // Update system total
        $db->createCommand()->update('electricity_system', [
            'total_electricity' => $newTotal,
        ], ['system_id' => $systemId])->execute();

        // TODO: Distribute electricity to batteries (for now just update total)

        return true;
    }

    /**
     * Take electricity from a system
     * @param int $systemId
     * @param int $amount
     * @return bool Success
     */
    public static function takeElectricity(int $systemId, int $amount): bool
    {
        $db = Yii::$app->db;

        $system = $db->createCommand()
            ->select('*')
            ->from('electricity_system')
            ->where(['system_id' => $systemId])
            ->queryOne();

        if (!$system) {
            return false;
        }

        $currentElectricity = (int)$system['total_electricity'];

        if ($currentElectricity < $amount) {
            return false; // Not enough electricity
        }

        // Update system total
        $db->createCommand()->update('electricity_system', [
            'total_electricity' => $currentElectricity - $amount,
        ], ['system_id' => $systemId])->execute();

        // TODO: Deduct electricity from batteries (for now just update total)

        return true;
    }

    /**
     * Get system info for an entity
     * @param int $entityId
     * @return array|null [system_id, total_capacity, total_electricity, is_powered]
     */
    public static function getSystemInfo(int $entityId): ?array
    {
        $db = Yii::$app->db;

        $member = $db->createCommand()
            ->select('system_id')
            ->from('electricity_system_member')
            ->where(['entity_id' => $entityId])
            ->queryOne();

        if (!$member) {
            return null;
        }

        $systemId = (int)$member['system_id'];

        $system = $db->createCommand()
            ->select('*')
            ->from('electricity_system')
            ->where(['system_id' => $systemId])
            ->queryOne();

        if (!$system) {
            return null;
        }

        return [
            'system_id' => $systemId,
            'total_capacity' => (int)$system['total_capacity'],
            'total_electricity' => (int)$system['total_electricity'],
            'is_powered' => (int)$system['total_electricity'] > 0,
        ];
    }
}
