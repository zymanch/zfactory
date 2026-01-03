<?php

namespace bl\landing\generators;

use app\client\ComfyUIClient;
use app\client\StableDiffusionClient;
use bl\landing\generators\base\AbstractLandingGenerator;
use bl\landing\generators\island;
use bl\landing\generators\sky;
use bl\landing\generators\ship;
use models\Landing;

/**
 * Factory for creating landing generators
 * Returns appropriate generator based on landing folder name
 */
class LandingGeneratorFactory
{
    /** @var ComfyUIClient|null */
    private $fluxClient;

    /** @var StableDiffusionClient|null */
    private $sdClient;

    /** @var string|null */
    private $basePath;

    /** @var array Cached generator instances */
    private $generators = [];

    /** @var array Map of folder name to generator class */
    private static $generatorMap = [
        // Island landings
        'grass' => island\GrassLandingGenerator::class,
        'dirt' => island\DirtLandingGenerator::class,
        'sand' => island\SandLandingGenerator::class,
        'water' => island\WaterLandingGenerator::class,
        'stone' => island\StoneLandingGenerator::class,
        'lava' => island\LavaLandingGenerator::class,
        'snow' => island\SnowLandingGenerator::class,
        'swamp' => island\SwampLandingGenerator::class,

        // Sky landings
        'sky' => sky\SkyLandingGenerator::class,
        'island_edge' => sky\IslandEdgeLandingGenerator::class,

        // Ship landings
        'ship_edge' => ship\ShipEdgeLandingGenerator::class,
        'ship_floor_wood' => ship\ShipFloorWoodLandingGenerator::class,
        'ship_floor_iron' => ship\ShipFloorIronLandingGenerator::class,
        'ship_floor_steel' => ship\ShipFloorSteelLandingGenerator::class,
        'ship_floor_titanium' => ship\ShipFloorTitaniumLandingGenerator::class,
        'ship_floor_crystal' => ship\ShipFloorCrystalLandingGenerator::class,
    ];

    public function __construct(
        ?ComfyUIClient $fluxClient = null,
        ?StableDiffusionClient $sdClient = null,
        ?string $basePath = null
    ) {
        $this->fluxClient = $fluxClient;
        $this->sdClient = $sdClient;
        $this->basePath = $basePath;
    }

    /**
     * Get generator for landing
     * @param Landing|string $landingOrFolder Landing model or folder name
     * @return AbstractLandingGenerator|null
     */
    public function getGenerator($landingOrFolder): ?AbstractLandingGenerator
    {
        $folder = $landingOrFolder instanceof Landing
            ? $landingOrFolder->folder
            : $landingOrFolder;

        // Check cache
        if (isset($this->generators[$folder])) {
            return $this->generators[$folder];
        }

        // Find generator class
        $generatorClass = self::$generatorMap[$folder] ?? null;

        if ($generatorClass === null) {
            return null;
        }

        // Create and cache generator
        $generator = new $generatorClass($this->fluxClient, $this->sdClient, $this->basePath);
        $this->generators[$folder] = $generator;

        return $generator;
    }

    /**
     * Check if generator exists for folder
     * @param string $folder
     * @return bool
     */
    public function hasGenerator(string $folder): bool
    {
        return isset(self::$generatorMap[$folder]);
    }

    /**
     * Get all registered folder names
     * @return array
     */
    public function getRegisteredFolders(): array
    {
        return array_keys(self::$generatorMap);
    }

    /**
     * Get all island landing folders
     * @return array
     */
    public function getIslandFolders(): array
    {
        return ['grass', 'dirt', 'sand', 'water', 'stone', 'lava', 'snow', 'swamp'];
    }

    /**
     * Get all sky landing folders
     * @return array
     */
    public function getSkyFolders(): array
    {
        return ['sky', 'island_edge'];
    }

    /**
     * Get all ship landing folders
     * @return array
     */
    public function getShipFolders(): array
    {
        return [
            'ship_edge',
            'ship_floor_wood',
            'ship_floor_iron',
            'ship_floor_steel',
            'ship_floor_titanium',
            'ship_floor_crystal',
        ];
    }

    /**
     * Register a new generator
     * @param string $folder
     * @param string $generatorClass
     */
    public static function register(string $folder, string $generatorClass): void
    {
        self::$generatorMap[$folder] = $generatorClass;
    }
}
