<?php

namespace services;

use models\Entity;
use models\EntityType;
use models\Landing;
use models\Map;

class FogOfWarService
{
    private static $regionMapCache = [];
    private static $landingTypesCache = null;
    private static $visibleTilesCache = [];

    /**
     * Check if specific coordinate is visible
     * Uses cached visible tiles to avoid recalculation
     */
    public static function isCoordinateVisible(int $x, int $y, int $regionId): bool
    {
        $visibleTiles = self::getVisibleTiles($regionId);

        // No eyes = all visible
        if ($visibleTiles === null) return true;

        $key = self::tileKey($x, $y);
        return in_array($key, $visibleTiles, true);
    }

    /**
     * Get all visible tiles for region (cached)
     * Returns null if no eye entities exist
     * @return array|null Array of "x_y" strings or null
     */
    private static function getVisibleTiles(int $regionId): ?array
    {
        if (isset(self::$visibleTilesCache[$regionId])) {
            return self::$visibleTilesCache[$regionId];
        }

        $visibleTiles = self::calculateVisibleTiles($regionId);
        self::$visibleTilesCache[$regionId] = $visibleTiles;

        return $visibleTiles;
    }

    /**
     * Calculate all visible tiles from all eye entities
     * @return array|null Array of "x_y" strings or null if no eyes
     */
    private static function calculateVisibleTiles(int $regionId): ?array
    {
        // Query all built eye entities
        $eyeEntities = Entity::find()
            ->alias('e')
            ->innerJoin(EntityType::tableName() . ' et', 'e.entity_type_id = et.entity_type_id')
            ->where(['et.type' => 'eye', 'e.state' => 'built', 'e.region_id' => $regionId])
            ->select(['e.x', 'e.y', 'et.power'])
            ->asArray()
            ->all();

        if (empty($eyeEntities)) return null; // No eyes = all visible

        $visibleTiles = [];

        foreach ($eyeEntities as $eye) {
            $tiles = self::calculateLineOfSight(
                (int)$eye['x'],
                (int)$eye['y'],
                (int)($eye['power'] ?? 1),
                $regionId
            );
            $visibleTiles = array_merge($visibleTiles, $tiles);
        }

        return array_unique($visibleTiles);
    }

    /**
     * Calculate line-of-sight from single eye (identical to client-side)
     */
    private static function calculateLineOfSight(int $eyeX, int $eyeY, int $radius, int $regionId): array
    {
        $visibleTiles = [self::tileKey($eyeX, $eyeY)];
        $radiusSq = $radius * $radius;

        // Cast rays to all tiles in radius
        for ($targetY = $eyeY - $radius; $targetY <= $eyeY + $radius; $targetY++) {
            for ($targetX = $eyeX - $radius; $targetX <= $eyeX + $radius; $targetX++) {
                $dx = $targetX - $eyeX;
                $dy = $targetY - $eyeY;
                if ($dx * $dx + $dy * $dy > $radiusSq) continue;
                if ($targetX === $eyeX && $targetY === $eyeY) continue;

                $rayTiles = self::castRay($eyeX, $eyeY, $targetX, $targetY, $regionId);
                $visibleTiles = array_merge($visibleTiles, $rayTiles);
            }
        }

        return $visibleTiles;
    }

    /**
     * Invalidate visibility cache for region
     */
    public static function invalidateCache(int $regionId): void
    {
        unset(self::$visibleTilesCache[$regionId]);
    }

    /**
     * Cast ray and return all visible tiles along the way
     * Stops at first blocking tile (but includes it)
     */
    private static function castRay(int $x0, int $y0, int $x1, int $y1, int $regionId): array
    {
        $visibleTiles = [];

        $dx = abs($x1 - $x0);
        $dy = abs($y1 - $y0);
        $sx = $x0 < $x1 ? 1 : -1;
        $sy = $y0 < $y1 ? 1 : -1;
        $err = $dx - $dy;
        $x = $x0;
        $y = $y0;

        while (true) {
            $isBlocking = self::isBlockingTile($x, $y, $regionId);

            // Add current tile to visible (even if blocking)
            $visibleTiles[] = self::tileKey($x, $y);

            // Stop if blocked (except starting position)
            if ($isBlocking && ($x !== $x0 || $y !== $y0)) {
                break;
            }

            // Reached target
            if ($x === $x1 && $y === $y1) {
                break;
            }

            // Bresenham step
            $e2 = 2 * $err;
            if ($e2 > -$dy) { $err -= $dy; $x += $sx; }
            if ($e2 < $dx) { $err += $dx; $y += $sy; }
        }

        return $visibleTiles;
    }

    /**
     * Check if tile blocks vision (with caching)
     */
    private static function isBlockingTile(int $x, int $y, int $regionId): bool
    {
        $tiles = self::getMapTiles($regionId);
        $key = self::tileKey($x, $y);

        if (!isset($tiles[$key])) return true; // Off-map

        $landingId = $tiles[$key]['landing_id'];
        $landingTypes = self::getLandingTypes();

        if (!isset($landingTypes[$landingId])) return true;

        return $landingTypes[$landingId]['blocks_vision'] === 'yes';
    }

    /**
     * Get all landing types (cached)
     */
    private static function getLandingTypes(): array
    {
        if (self::$landingTypesCache === null) {
            self::$landingTypesCache = Landing::find()
                ->indexBy('landing_id')
                ->asArray()
                ->all();
        }

        return self::$landingTypesCache;
    }

    /**
     * Get all map tiles for region (cached)
     */
    private static function getMapTiles(int $regionId): array
    {
        if (!isset(self::$regionMapCache[$regionId])) {
            $tiles = Map::find()
                ->where(['region_id' => $regionId])
                ->indexBy(function($row) { return "{$row['x']}_{$row['y']}"; })
                ->asArray()
                ->all();

            self::$regionMapCache[$regionId] = $tiles;
        }

        return self::$regionMapCache[$regionId];
    }

    private static function tileKey(int $x, int $y): string
    {
        return "{$x}_{$y}";
    }
}
