<?php

namespace bl\entity\generators;

use app\client\ComfyUIClient;
use bl\entity\generators\base\AbstractEntityGenerator;
use bl\entity\generators\building;
use bl\entity\generators\tree;
use bl\entity\generators\transporter;
use bl\entity\generators\manipulator;
use bl\entity\generators\deposit;
use bl\entity\generators\relief;
use bl\entity\generators\eye;
use models\EntityType;

/**
 * Factory for creating entity generators
 * Returns appropriate generator based on entity image_url
 */
class EntityGeneratorFactory
{
    /** @var ComfyUIClient */
    private $fluxClient;

    /** @var string */
    private $basePath;

    /** @var array Cached generator instances */
    private $generators = [];

    /** @var array Map of image_url to generator class */
    private static $generatorMap = [
        // Buildings
        'furnace' => building\FurnaceGenerator::class,
        'assembler' => building\AssemblerGenerator::class,
        'chest' => building\ChestGenerator::class,
        'power_pole' => building\PowerPoleGenerator::class,
        'steam_engine' => building\SteamEngineGenerator::class,
        'boiler' => building\BoilerGenerator::class,
        'drill' => building\DrillGenerator::class,
        'drill_fast' => building\DrillFastGenerator::class,
        'drill_large' => building\DrillLargeGenerator::class,
        'hq' => building\HqGenerator::class,

        // Sawmills
        'sawmill_small' => building\SawmillSmallGenerator::class,
        'sawmill_medium' => building\SawmillMediumGenerator::class,
        'sawmill_large' => building\SawmillLargeGenerator::class,

        // Stone Quarries
        'stone_quarry_small' => building\StoneQuarrySmallGenerator::class,
        'stone_quarry_medium' => building\StoneQuarryMediumGenerator::class,
        'stone_quarry_large' => building\StoneQuarryLargeGenerator::class,

        // Mines
        'mine_small' => building\MineSmallGenerator::class,
        'mine_medium' => building\MineMediumGenerator::class,
        'mine_large' => building\MineLargeGenerator::class,

        // Quarries
        'quarry_small' => building\QuarrySmallGenerator::class,
        'quarry_medium' => building\QuarryMediumGenerator::class,
        'quarry_large' => building\QuarryLargeGenerator::class,

        // Trees
        'tree_pine' => tree\PineTreeGenerator::class,
        'tree_oak' => tree\OakTreeGenerator::class,
        'tree_dead' => tree\DeadTreeGenerator::class,
        'tree_birch' => tree\BirchTreeGenerator::class,
        'tree_willow' => tree\WillowTreeGenerator::class,
        'tree_maple' => tree\MapleTreeGenerator::class,
        'tree_spruce' => tree\SpruceTreeGenerator::class,
        'tree_ash' => tree\AshTreeGenerator::class,

        // Transporters
        'conveyor' => transporter\ConveyorGenerator::class,

        // Manipulators
        'manipulator_short' => manipulator\ShortManipulatorGenerator::class,
        'manipulator_long' => manipulator\LongManipulatorGenerator::class,

        // Deposits
        'ore_iron' => deposit\IronOreGenerator::class,
        'ore_copper' => deposit\CopperOreGenerator::class,
        'ore_aluminum' => deposit\AluminumOreGenerator::class,
        'ore_titanium' => deposit\TitaniumOreGenerator::class,
        'ore_silver' => deposit\SilverOreGenerator::class,
        'ore_gold' => deposit\GoldOreGenerator::class,

        // Relief
        'rock_small' => relief\SmallRockGenerator::class,
        'rock_medium' => relief\MediumRockGenerator::class,
        'rock_large' => relief\LargeRockGenerator::class,

        // Eye (Crystal Towers)
        'crystal_tower_small' => eye\SmallCrystalTowerGenerator::class,
        'crystal_tower_medium' => eye\MediumCrystalTowerGenerator::class,
        'crystal_tower_large' => eye\LargeCrystalTowerGenerator::class,
    ];

    public function __construct(?ComfyUIClient $fluxClient = null, ?string $basePath = null)
    {
        $this->fluxClient = $fluxClient;
        $this->basePath = $basePath;
    }

    /**
     * Get generator for entity type
     * @param EntityType|string $entityOrImageUrl
     * @return AbstractEntityGenerator|null
     */
    public function getGenerator($entityOrImageUrl): ?AbstractEntityGenerator
    {
        $imageUrl = $entityOrImageUrl instanceof EntityType
            ? $entityOrImageUrl->image_url
            : $entityOrImageUrl;

        // Check cache
        if (isset($this->generators[$imageUrl])) {
            return $this->generators[$imageUrl];
        }

        // Find generator class
        $generatorClass = self::$generatorMap[$imageUrl] ?? null;

        if ($generatorClass === null) {
            return null;
        }

        // Create and cache generator
        $generator = new $generatorClass($this->fluxClient, $this->basePath);
        $this->generators[$imageUrl] = $generator;

        return $generator;
    }

    /**
     * Check if generator exists for image_url
     * @param string $imageUrl
     * @return bool
     */
    public function hasGenerator(string $imageUrl): bool
    {
        return isset(self::$generatorMap[$imageUrl]);
    }

    /**
     * Get all registered image_urls
     * @return array
     */
    public function getRegisteredImageUrls(): array
    {
        return array_keys(self::$generatorMap);
    }

    /**
     * Register a new generator
     * @param string $imageUrl
     * @param string $generatorClass
     */
    public static function register(string $imageUrl, string $generatorClass): void
    {
        self::$generatorMap[$imageUrl] = $generatorClass;
    }
}
