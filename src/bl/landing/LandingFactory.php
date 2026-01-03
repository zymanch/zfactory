<?php

namespace bl\landing;

/**
 * Factory for mapping landing_id to Landing class
 * Used by Landing::instantiate() to return correct class instances
 */
class LandingFactory
{
    /** @var array Map of landing_id to Landing class */
    private static $classMap = [
        // Island landings (1-8)
        GrassLanding::LANDING_ID => GrassLanding::class,
        DirtLanding::LANDING_ID => DirtLanding::class,
        SandLanding::LANDING_ID => SandLanding::class,
        WaterLanding::LANDING_ID => WaterLanding::class,
        StoneLanding::LANDING_ID => StoneLanding::class,
        LavaLanding::LANDING_ID => LavaLanding::class,
        SnowLanding::LANDING_ID => SnowLanding::class,
        SwampLanding::LANDING_ID => SwampLanding::class,

        // Sky landings (9-10)
        SkyLanding::LANDING_ID => SkyLanding::class,
        IslandEdgeLanding::LANDING_ID => IslandEdgeLanding::class,

        // Ship landings (11-16)
        ShipEdgeLanding::LANDING_ID => ShipEdgeLanding::class,
        ShipFloorWoodLanding::LANDING_ID => ShipFloorWoodLanding::class,
        ShipFloorIronLanding::LANDING_ID => ShipFloorIronLanding::class,
        ShipFloorSteelLanding::LANDING_ID => ShipFloorSteelLanding::class,
        ShipFloorTitaniumLanding::LANDING_ID => ShipFloorTitaniumLanding::class,
        ShipFloorCrystalLanding::LANDING_ID => ShipFloorCrystalLanding::class,
    ];

    /**
     * Get class name for given landing_id
     * @param int $landingId
     * @return string|null
     */
    public static function getClass(int $landingId): ?string
    {
        return self::$classMap[$landingId] ?? null;
    }

    /**
     * Check if class exists for given landing_id
     * @param int $landingId
     * @return bool
     */
    public static function hasClass(int $landingId): bool
    {
        return isset(self::$classMap[$landingId]);
    }

    /**
     * Get all registered landing_ids
     * @return array
     */
    public static function getRegisteredIds(): array
    {
        return array_keys(self::$classMap);
    }

    /**
     * Register a new class mapping
     * @param int $landingId
     * @param string $className
     */
    public static function register(int $landingId, string $className): void
    {
        self::$classMap[$landingId] = $className;
    }
}
