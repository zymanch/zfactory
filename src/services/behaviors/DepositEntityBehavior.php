<?php

namespace services\behaviors;

use models\Deposit;
use models\DepositType;
use models\Entity;
use models\EntityType;

/**
 * Behavior for extraction buildings that require specific deposit types
 * (Sawmill, Stone Quarry, Ore Drill, Mine, Quarry)
 *
 * Placement rules:
 * - Must be on buildable terrain
 * - Must not be in fog of war
 * - Must have at least one deposit of the required type in the building area
 * - No other entity collisions
 *
 * Returns depositsToRemove array for CreateEntity to process
 */
class DepositEntityBehavior extends EntityBehavior
{
    /** @var string Required deposit type: 'tree', 'rock', or 'ore' */
    private $requiredDepositType;

    /** @var array Allowed deposit_type_ids */
    private $allowedDepositTypeIds = [];

    /**
     * @param EntityType $entityType
     */
    public function __construct(EntityType $entityType)
    {
        parent::__construct($entityType);
        $this->determineRequiredDepositType();
    }

    /**
     * Determine required deposit type based on entity_type_id
     */
    private function determineRequiredDepositType(): void
    {
        $typeId = $this->entityType->entity_type_id;

        // Sawmills (500-502) require trees
        if ($typeId >= 500 && $typeId <= 502) {
            $this->requiredDepositType = 'tree';
        }
        // Stone Quarries (503-505) require rocks
        elseif ($typeId >= 503 && $typeId <= 505) {
            $this->requiredDepositType = 'rock';
        }
        // Ore Drills (102, 108, 506) require iron/copper ores
        elseif (in_array($typeId, [102, 108, 506])) {
            $this->requiredDepositType = 'ore';
            // Only iron and copper for drills
            $this->allowedDepositTypeIds = [300, 301]; // Iron, Copper
        }
        // Mines (507-509) require silver/gold ores
        elseif ($typeId >= 507 && $typeId <= 509) {
            $this->requiredDepositType = 'ore';
            // Only silver and gold for mines
            $this->allowedDepositTypeIds = [304, 305]; // Silver, Gold
        }
        // Quarries (510-512) require aluminum/titanium ores
        elseif ($typeId >= 510 && $typeId <= 512) {
            $this->requiredDepositType = 'ore';
            // Only aluminum and titanium for quarries
            $this->allowedDepositTypeIds = [302, 303]; // Aluminum, Titanium
        }

        // Load allowed deposit type IDs if not specific ore types
        if (empty($this->allowedDepositTypeIds)) {
            $this->loadAllowedDepositTypes();
        }
    }

    /**
     * Load allowed deposit types from database based on required type
     */
    private function loadAllowedDepositTypes(): void
    {
        $depositTypeIds = DepositType::find()
            ->select('deposit_type_id')
            ->where(['type' => $this->requiredDepositType])
            ->column();

        $this->allowedDepositTypeIds = array_map('intval', $depositTypeIds);
    }

    /**
     * Check if extraction building can be built at specified coordinates
     *
     * @param int $tileX Tile X coordinate
     * @param int $tileY Tile Y coordinate
     * @param array|null $visibleTiles Array of visible tile keys
     * @return array ['allowed' => bool, 'error' => string|null, 'depositsToRemove' => array|null]
     */
    public function canBuildAt(int $tileX, int $tileY, ?array $visibleTiles = null): array
    {
        // Check fog of war
        if (!$this->areAllTilesVisible($tileX, $tileY, $visibleTiles)) {
            return $this->error('Cannot build in fog of war');
        }

        // Check landing buildability
        if (!$this->areAllTilesBuildable($tileX, $tileY)) {
            return $this->error('Cannot build on this terrain');
        }

        // Find deposits in building area
        $depositsCheck = $this->checkDepositsInArea($tileX, $tileY);
        if (!$depositsCheck['allowed']) {
            return $depositsCheck;
        }

        // Check for entity collisions
        if ($this->hasEntityCollision($tileX, $tileY)) {
            return $this->error('Position is occupied by another entity');
        }

        return $this->success($depositsCheck['deposits']);
    }

    /**
     * Check deposits in building area
     *
     * @return array ['allowed' => bool, 'error' => string|null, 'deposits' => array]
     */
    private function checkDepositsInArea(int $tileX, int $tileY): array
    {
        $width = $this->entityType->width ?? 1;
        $height = $this->entityType->height ?? 1;

        // Find all deposits in area
        $deposits = Deposit::find()
            ->where(['>=', 'x', $tileX])
            ->andWhere(['<', 'x', $tileX + $width])
            ->andWhere(['>=', 'y', $tileY])
            ->andWhere(['<', 'y', $tileY + $height])
            ->all();

        // Must have at least one deposit
        if (empty($deposits)) {
            return [
                'allowed' => false,
                'error' => $this->getNoDepositErrorMessage(),
                'deposits' => [],
            ];
        }

        // Check that all deposits are of allowed types
        foreach ($deposits as $deposit) {
            if (!in_array($deposit->deposit_type_id, $this->allowedDepositTypeIds)) {
                $depositType = DepositType::findOne($deposit->deposit_type_id);
                $name = $depositType ? $depositType->name : 'this deposit';
                return [
                    'allowed' => false,
                    'error' => "Cannot build on {$name}. " . $this->getRequiredDepositMessage(),
                    'deposits' => [],
                ];
            }
        }

        return [
            'allowed' => true,
            'error' => null,
            'deposits' => $deposits,
        ];
    }

    /**
     * Get error message for no deposits found
     */
    private function getNoDepositErrorMessage(): string
    {
        $messages = [
            'tree' => 'Requires trees to place sawmill',
            'rock' => 'Requires rocks to place stone quarry',
            'ore' => 'Requires ore deposits to place mining building',
        ];

        return $messages[$this->requiredDepositType] ?? 'Requires deposits to build';
    }

    /**
     * Get message about required deposit type
     */
    private function getRequiredDepositMessage(): string
    {
        $typeId = $this->entityType->entity_type_id;

        if ($typeId >= 500 && $typeId <= 502) {
            return 'Sawmills require trees.';
        } elseif ($typeId >= 503 && $typeId <= 505) {
            return 'Stone Quarries require rocks.';
        } elseif (in_array($typeId, [102, 108, 506])) {
            return 'Ore Drills require iron or copper ore.';
        } elseif ($typeId >= 507 && $typeId <= 509) {
            return 'Mines require silver or gold ore.';
        } elseif ($typeId >= 510 && $typeId <= 512) {
            return 'Quarries require aluminum or titanium ore.';
        }

        return 'Wrong deposit type.';
    }

    /**
     * Get client info including required deposit type
     */
    public function getClientInfo(): array
    {
        return array_merge(parent::getClientInfo(), [
            'requiresDeposit' => true,
            'requiredDepositType' => $this->requiredDepositType,
            'allowedDepositTypeIds' => $this->allowedDepositTypeIds,
        ]);
    }

    /**
     * Helper: create error response
     */
    private function error(string $message): array
    {
        return [
            'allowed' => false,
            'error' => $message,
            'depositsToRemove' => null,
        ];
    }

    /**
     * Helper: create success response
     */
    private function success(array $deposits): array
    {
        return [
            'allowed' => true,
            'error' => null,
            'depositsToRemove' => array_map(function($deposit) {
                return $deposit->deposit_id;
            }, $deposits),
        ];
    }
}
