<?php

namespace commands;

use yii\console\Controller;
use yii\console\ExitCode;
use models\UserResource;

/**
 * Resource management commands
 */
class ResourceController extends Controller
{
    /**
     * Add resources to storage entity
     *
     * Usage:
     *   php yii resource/add-to-storage 10 1000
     *   php yii resource/add-to-storage 10 1000 raw,crafted
     *
     * @param int $entityId Storage entity ID
     * @param int $amount Amount to add (default: 1000)
     * @param string|null $types Resource types filter (comma-separated: raw,crafted,liquid)
     * @return int Exit code
     */
    public function actionAddToStorage($entityId, $amount = 1000, $types = null)
    {
        try {
            $db = \Yii::$app->db;

            // Validate entity exists and is storage
            $entity = $db->createCommand("
                SELECT e.entity_id, et.name, et.storage_type
                FROM entity e
                JOIN entity_type et ON e.entity_type_id = et.entity_type_id
                WHERE e.entity_id = :entity_id
                AND e.state = 'built'
            ", [':entity_id' => $entityId])->queryOne();

            if (!$entity) {
                $this->stderr("Error: Entity #{$entityId} not found or not built\n");
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $this->stdout("Storage: {$entity['name']} (ID: {$entityId}, Type: {$entity['storage_type']})\n\n");

            // Build resource query
            $typeFilter = '';
            if ($types) {
                $typeArray = array_map('trim', explode(',', $types));
                $typeFilter = " AND type IN ('" . implode("','", $typeArray) . "')";
            } else {
                $typeFilter = " AND type IN ('raw', 'crafted', 'liquid')";
            }

            // Get resources
            $resources = $db->createCommand("
                SELECT resource_id, name, type
                FROM resource
                WHERE 1=1 {$typeFilter}
                ORDER BY resource_id
            ")->queryAll();

            if (empty($resources)) {
                $this->stderr("No resources found\n");
                return ExitCode::OK;
            }

            $this->stdout("Adding {$amount} of each resource...\n\n");

            $added = 0;
            $updated = 0;

            foreach ($resources as $resource) {
                // Check if resource already exists
                $existing = $db->createCommand("
                    SELECT entity_resource_id, amount
                    FROM entity_resource
                    WHERE entity_id = :entity_id AND resource_id = :resource_id
                ", [
                    ':entity_id' => $entityId,
                    ':resource_id' => $resource['resource_id']
                ])->queryOne();

                if ($existing) {
                    // Update existing
                    $newAmount = $existing['amount'] + $amount;
                    $db->createCommand()->update('entity_resource', [
                        'amount' => $newAmount
                    ], [
                        'entity_resource_id' => $existing['entity_resource_id']
                    ])->execute();

                    $this->stdout("  [{$resource['resource_id']}] {$resource['name']}: {$existing['amount']} + {$amount} = {$newAmount}\n");
                    $updated++;
                } else {
                    // Insert new
                    $db->createCommand()->insert('entity_resource', [
                        'entity_id' => $entityId,
                        'resource_id' => $resource['resource_id'],
                        'amount' => $amount,
                        'position_px' => null,
                        'from_direction' => null,
                        'status' => null
                    ])->execute();

                    $this->stdout("  [{$resource['resource_id']}] {$resource['name']}: NEW → {$amount}\n");
                    $added++;
                }
            }

            $this->stdout("\nDone!\n");
            $this->stdout("Added: {$added} new resources\n");
            $this->stdout("Updated: {$updated} existing resources\n");
            $this->stdout("Total: " . ($added + $updated) . " resources\n");

            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr("Error: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Clear all resources from storage entity
     *
     * Usage:
     *   php yii resource/clear-storage 10
     *
     * @param int $entityId Storage entity ID
     * @return int Exit code
     */
    public function actionClearStorage($entityId)
    {
        try {
            $db = \Yii::$app->db;

            $deleted = $db->createCommand()->delete('entity_resource', [
                'entity_id' => $entityId
            ])->execute();

            $this->stdout("Cleared {$deleted} resources from entity #{$entityId}\n");

            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr("Error: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Add resources to user inventory
     *
     * Usage:
     *   php yii resource/add-to-user 1 1000
     *   php yii resource/add-to-user 1 1000 raw,crafted
     *
     * @param int $userId User ID
     * @param int $amount Amount to add (default: 1000)
     * @param string|null $types Resource types filter (comma-separated: raw,crafted,liquid)
     * @return int Exit code
     */
    public function actionAddToUser($userId, $amount = 1000, $types = null)
    {
        try {
            $db = \Yii::$app->db;

            // Validate user exists
            $user = $db->createCommand("
                SELECT user_id, username
                FROM user
                WHERE user_id = :user_id
            ", [':user_id' => $userId])->queryOne();

            if (!$user) {
                $this->stderr("Error: User #{$userId} not found\n");
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $this->stdout("User: {$user['username']} (ID: {$userId})\n\n");

            // Build resource query
            $typeFilter = '';
            if ($types) {
                $typeArray = array_map('trim', explode(',', $types));
                $typeFilter = " AND type IN ('" . implode("','", $typeArray) . "')";
            } else {
                $typeFilter = " AND type IN ('raw', 'crafted', 'liquid')";
            }

            // Get resources
            $resources = $db->createCommand("
                SELECT resource_id, name, type
                FROM resource
                WHERE 1=1 {$typeFilter}
                ORDER BY resource_id
            ")->queryAll();

            if (empty($resources)) {
                $this->stderr("No resources found\n");
                return ExitCode::OK;
            }

            $this->stdout("Adding {$amount} of each resource...\n\n");

            $added = 0;
            $updated = 0;

            foreach ($resources as $resource) {
                // Check if resource already exists
                $existing = UserResource::findOne([
                    'user_id' => $userId,
                    'resource_id' => $resource['resource_id']
                ]);

                $oldQuantity = $existing ? $existing->quantity : 0;

                // Add resource (creates or updates)
                if (UserResource::addResource($userId, $resource['resource_id'], $amount)) {
                    $newQuantity = $oldQuantity + $amount;

                    if ($existing) {
                        $this->stdout("  [{$resource['resource_id']}] {$resource['name']}: {$oldQuantity} + {$amount} = {$newQuantity}\n");
                        $updated++;
                    } else {
                        $this->stdout("  [{$resource['resource_id']}] {$resource['name']}: NEW → {$amount}\n");
                        $added++;
                    }
                } else {
                    $this->stderr("  ERROR: Failed to add {$resource['name']}\n");
                }
            }

            $this->stdout("\nDone!\n");
            $this->stdout("Added: {$added} new resources\n");
            $this->stdout("Updated: {$updated} existing resources\n");
            $this->stdout("Total: " . ($added + $updated) . " resources\n");

            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr("Error: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
