<?php

namespace actions\game;

use actions\JsonAction;
use models\ManipulatorConfig;
use models\Entity;
use Yii;

/**
 * Save manipulator configuration (filters and counter)
 *
 * POST params:
 * - entity_id: int (required)
 * - filter_resource_ids: array of int (optional)
 * - max_transfer_count: int (optional, for counting manipulator only)
 */
class SaveManipulatorConfig extends JsonAction
{
    public function run()
    {
        $entityId = Yii::$app->request->post('entity_id');
        $filterResourceIds = Yii::$app->request->post('filter_resource_ids', []);
        $maxTransferCount = Yii::$app->request->post('max_transfer_count');

        // Validate entity_id
        if (!$entityId) {
            return $this->error('entity_id is required');
        }

        // Check entity exists
        $entity = Entity::findOne($entityId);
        if (!$entity) {
            return $this->error('Entity not found');
        }

        // Check entity is a filtered manipulator (types 216-221)
        $entityTypeId = $entity->entity_type_id;
        $baseTypeId = $entityTypeId % 100; // Remove orientation offset
        $isFilteredManipulator = in_array($baseTypeId, [216, 220, 221]);

        if (!$isFilteredManipulator) {
            return $this->error('Entity is not a filtered manipulator');
        }

        // Validate filter_resource_ids is array
        if (!is_array($filterResourceIds)) {
            $filterResourceIds = [];
        }

        // Filter out invalid IDs
        $filterResourceIds = array_filter($filterResourceIds, function($id) {
            return is_numeric($id) && $id > 0;
        });
        $filterResourceIds = array_map('intval', $filterResourceIds);

        // Determine max filter count based on type
        $maxFilterCount = 1;
        if ($baseTypeId === 220) {
            $maxFilterCount = 5;
        }

        // Limit filter count
        if (count($filterResourceIds) > $maxFilterCount) {
            $filterResourceIds = array_slice($filterResourceIds, 0, $maxFilterCount);
        }

        // Find or create config
        $config = ManipulatorConfig::findOrCreate($entityId);

        // Update filter
        $config->setFilterResourceIdsArray($filterResourceIds);

        // Update counter (only for counting manipulator - type 221)
        if ($baseTypeId === 221) {
            if ($maxTransferCount !== null && $maxTransferCount !== '') {
                $config->max_transfer_count = max(1, intval($maxTransferCount));
            } else {
                $config->max_transfer_count = null;
            }
        } else {
            $config->max_transfer_count = null;
        }

        // Save
        if (!$config->save()) {
            return $this->error('Failed to save configuration', [
                'errors' => $config->errors,
            ]);
        }

        return $this->success([
            'config' => [
                'manipulator_config_id' => $config->manipulator_config_id,
                'entity_id' => $config->entity_id,
                'filter_resource_ids' => $config->getFilterResourceIdsArray(),
                'max_transfer_count' => $config->max_transfer_count,
                'current_transfer_count' => $config->current_transfer_count,
            ],
        ]);
    }
}
