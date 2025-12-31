<?php

namespace bl\entity\types;

use bl\entity\types\building;
use bl\entity\types\tree;
use bl\entity\types\transporter;
use bl\entity\types\manipulator;
use bl\entity\types\relief;
use bl\entity\types\eye;

/**
 * Factory for mapping entity_type_id to EntityType class
 * Used by EntityType::instantiate() to return correct class instances
 */
class EntityTypeFactory
{
    /** @var array Map of entity_type_id to EntityType class */
    private static $classMap = [
        // Trees (1-8)
        tree\PineTreeEntityType::ENTITY_TYPE_ID => tree\PineTreeEntityType::class,
        tree\OakTreeEntityType::ENTITY_TYPE_ID => tree\OakTreeEntityType::class,
        tree\DeadTreeEntityType::ENTITY_TYPE_ID => tree\DeadTreeEntityType::class,
        tree\BirchTreeEntityType::ENTITY_TYPE_ID => tree\BirchTreeEntityType::class,
        tree\SpruceTreeEntityType::ENTITY_TYPE_ID => tree\SpruceTreeEntityType::class,
        tree\MapleTreeEntityType::ENTITY_TYPE_ID => tree\MapleTreeEntityType::class,
        tree\WillowTreeEntityType::ENTITY_TYPE_ID => tree\WillowTreeEntityType::class,
        tree\AshTreeEntityType::ENTITY_TYPE_ID => tree\AshTreeEntityType::class,

        // Relief (10-12)
        relief\SmallRockEntityType::ENTITY_TYPE_ID => relief\SmallRockEntityType::class,
        relief\MediumRockEntityType::ENTITY_TYPE_ID => relief\MediumRockEntityType::class,
        relief\LargeRockEntityType::ENTITY_TYPE_ID => relief\LargeRockEntityType::class,

        // Transporters (100)
        transporter\ConveyorEntityType::ENTITY_TYPE_ID => transporter\ConveyorEntityType::class,

        // Buildings (101-122)
        building\FurnaceEntityType::ENTITY_TYPE_ID => building\FurnaceEntityType::class,
        building\DrillEntityType::ENTITY_TYPE_ID => building\DrillEntityType::class,
        building\AssemblerEntityType::ENTITY_TYPE_ID => building\AssemblerEntityType::class,
        building\ChestEntityType::ENTITY_TYPE_ID => building\ChestEntityType::class,
        building\PowerPoleEntityType::ENTITY_TYPE_ID => building\PowerPoleEntityType::class,
        building\SteamEngineEntityType::ENTITY_TYPE_ID => building\SteamEngineEntityType::class,
        building\BoilerEntityType::ENTITY_TYPE_ID => building\BoilerEntityType::class,
        building\DrillFastEntityType::ENTITY_TYPE_ID => building\DrillFastEntityType::class,
        building\HqEntityType::ENTITY_TYPE_ID => building\HqEntityType::class,
        building\DrillLargeEntityType::ENTITY_TYPE_ID => building\DrillLargeEntityType::class,
        building\SawmillSmallEntityType::ENTITY_TYPE_ID => building\SawmillSmallEntityType::class,
        building\SawmillMediumEntityType::ENTITY_TYPE_ID => building\SawmillMediumEntityType::class,
        building\SawmillLargeEntityType::ENTITY_TYPE_ID => building\SawmillLargeEntityType::class,
        building\StoneQuarrySmallEntityType::ENTITY_TYPE_ID => building\StoneQuarrySmallEntityType::class,
        building\StoneQuarryMediumEntityType::ENTITY_TYPE_ID => building\StoneQuarryMediumEntityType::class,
        building\StoneQuarryLargeEntityType::ENTITY_TYPE_ID => building\StoneQuarryLargeEntityType::class,
        building\MineSmallEntityType::ENTITY_TYPE_ID => building\MineSmallEntityType::class,
        building\MineMediumEntityType::ENTITY_TYPE_ID => building\MineMediumEntityType::class,
        building\MineLargeEntityType::ENTITY_TYPE_ID => building\MineLargeEntityType::class,
        building\QuarrySmallEntityType::ENTITY_TYPE_ID => building\QuarrySmallEntityType::class,
        building\QuarryMediumEntityType::ENTITY_TYPE_ID => building\QuarryMediumEntityType::class,
        building\QuarryLargeEntityType::ENTITY_TYPE_ID => building\QuarryLargeEntityType::class,

        // Manipulators (200-201)
        manipulator\ShortManipulatorEntityType::ENTITY_TYPE_ID => manipulator\ShortManipulatorEntityType::class,
        manipulator\LongManipulatorEntityType::ENTITY_TYPE_ID => manipulator\LongManipulatorEntityType::class,

        // Eye - Crystal Towers (400-402)
        eye\SmallCrystalTowerEntityType::ENTITY_TYPE_ID => eye\SmallCrystalTowerEntityType::class,
        eye\MediumCrystalTowerEntityType::ENTITY_TYPE_ID => eye\MediumCrystalTowerEntityType::class,
        eye\LargeCrystalTowerEntityType::ENTITY_TYPE_ID => eye\LargeCrystalTowerEntityType::class,
    ];

    /**
     * Get class name for given entity_type_id
     * @param int $entityTypeId
     * @return string|null
     */
    public static function getClass(int $entityTypeId): ?string
    {
        return self::$classMap[$entityTypeId] ?? null;
    }

    /**
     * Check if class exists for given entity_type_id
     * @param int $entityTypeId
     * @return bool
     */
    public static function hasClass(int $entityTypeId): bool
    {
        return isset(self::$classMap[$entityTypeId]);
    }

    /**
     * Get all registered entity_type_ids
     * @return array
     */
    public static function getRegisteredIds(): array
    {
        return array_keys(self::$classMap);
    }

    /**
     * Register a new class mapping
     * @param int $entityTypeId
     * @param string $className
     */
    public static function register(int $entityTypeId, string $className): void
    {
        self::$classMap[$entityTypeId] = $className;
    }
}
