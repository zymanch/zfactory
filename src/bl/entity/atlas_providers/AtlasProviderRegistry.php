<?php

namespace bl\entity\atlas_providers;

use bl\entity\atlas_providers\base\AtlasProviderInterface;
use bl\entity\atlas_providers\building\BuildingAtlasProvider;
use bl\entity\atlas_providers\conveyor\ConveyorAtlasProvider;
use bl\entity\atlas_providers\conveyor\UndergroundBeltAtlasProvider;
use bl\entity\atlas_providers\conveyor\SplitterAtlasProvider;
use bl\entity\atlas_providers\pipe\PipeAtlasProvider;
use bl\entity\atlas_providers\manipulator\ManipulatorAtlasProvider;
use models\EntityType;

/**
 * Registry for atlas providers
 * Maps entity type+subtype to appropriate atlas provider
 *
 * Usage:
 *   AtlasProviderRegistry::init();
 *   $provider = AtlasProviderRegistry::getProvider($entityType);
 *   $atlasGenerators = $provider->getAtlasGenerators($entityType);
 */
class AtlasProviderRegistry
{
    /** @var array<string, string> Map of "type:subtype" => ProviderClassName */
    private static $providers = [];

    /** @var bool */
    private static $initialized = false;

    /**
     * Initialize registry with default providers
     * Call this once at application start or before first use
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        // Building types (most common)
        self::register('building', 'none', BuildingAtlasProvider::class);

        // Conveyor types
        self::register('conveyor', 'conveyor', ConveyorAtlasProvider::class);
        self::register('conveyor', 'underground_in', UndergroundBeltAtlasProvider::class);
        self::register('conveyor', 'underground_out', UndergroundBeltAtlasProvider::class);
        self::register('conveyor', 'underground_belt', UndergroundBeltAtlasProvider::class);
        self::register('conveyor', 'splitter', SplitterAtlasProvider::class);

        // Pipe types
        self::register('pipe', 'pipe', PipeAtlasProvider::class);
        self::register('pipe', 'underground_pipe', PipeAtlasProvider::class);

        // Storage types
        self::register('storage', 'none', BuildingAtlasProvider::class);
        self::register('storage', 'solid', BuildingAtlasProvider::class);
        self::register('storage', 'liquid', BuildingAtlasProvider::class);

        // Mining, eye, tree, relief, resource types (use building provider)
        self::register('mining', 'none', BuildingAtlasProvider::class);
        self::register('eye', 'none', BuildingAtlasProvider::class);

        // Manipulator types
        self::register('manipulator', 'short', ManipulatorAtlasProvider::class);
        self::register('manipulator', 'long', ManipulatorAtlasProvider::class);
        self::register('manipulator', 'filtered1', ManipulatorAtlasProvider::class);
        self::register('manipulator', 'filtered5', ManipulatorAtlasProvider::class);
        self::register('manipulator', 'counting', ManipulatorAtlasProvider::class);

        // Electricity types (when implemented, can use custom provider)
        // For now, use building provider
        self::register('electricity', 'pylon', BuildingAtlasProvider::class);
        self::register('electricity', 'battery', BuildingAtlasProvider::class);
        self::register('electricity', 'generator', BuildingAtlasProvider::class);

        // Ship type
        self::register('ship', 'none', BuildingAtlasProvider::class);

        // Legacy: transporter (before migration to conveyor)
        // This allows graceful fallback if migration hasn't run yet
        self::register('transporter', 'none', ConveyorAtlasProvider::class);
        self::register('transporter', 'conveyor', ConveyorAtlasProvider::class);

        self::$initialized = true;
    }

    /**
     * Register atlas provider for entity type+subtype
     * @param string $type Entity type (building, conveyor, pipe, etc.)
     * @param string|null $subtype Entity subtype (none, conveyor, pylon, etc.)
     * @param string $providerClass Provider class name (must implement AtlasProviderInterface)
     */
    public static function register(string $type, ?string $subtype, string $providerClass): void
    {
        $key = self::makeKey($type, $subtype);
        self::$providers[$key] = $providerClass;
    }

    /**
     * Get atlas provider for entity type
     * @param EntityType $entityType
     * @return AtlasProviderInterface
     * @throws \Exception If no provider registered for this type
     */
    public static function getProvider(EntityType $entityType): AtlasProviderInterface
    {
        if (!self::$initialized) {
            self::init();
        }

        $key = self::makeKey($entityType->type, $entityType->subtype);

        if (!isset(self::$providers[$key])) {
            // Try fallback to type only (ignore subtype)
            $fallbackKey = self::makeKey($entityType->type, 'none');
            if (isset(self::$providers[$fallbackKey])) {
                $key = $fallbackKey;
            } else {
                throw new \Exception("No atlas provider registered for type={$entityType->type}, subtype={$entityType->subtype}");
            }
        }

        $class = self::$providers[$key];
        return new $class();
    }

    /**
     * Check if provider exists for entity type
     * @param EntityType $entityType
     * @return bool
     */
    public static function hasProvider(EntityType $entityType): bool
    {
        if (!self::$initialized) {
            self::init();
        }

        $key = self::makeKey($entityType->type, $entityType->subtype);
        if (isset(self::$providers[$key])) {
            return true;
        }

        // Check fallback
        $fallbackKey = self::makeKey($entityType->type, 'none');
        return isset(self::$providers[$fallbackKey]);
    }

    /**
     * Create registry key from type and subtype
     * @param string $type
     * @param string|null $subtype
     * @return string
     */
    private static function makeKey(string $type, ?string $subtype): string
    {
        return $type . ':' . ($subtype ?? 'none');
    }

    /**
     * Clear registry (for testing)
     */
    public static function clear(): void
    {
        self::$providers = [];
        self::$initialized = false;
    }
}
