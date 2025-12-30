<?php

namespace helpers;

use models\Region;
use Yii;

/**
 * RegionGenerator - generates a 5x5 grid of regions with randomized coordinates
 */
class RegionGenerator
{
    const GRID_SIZE = 5;
    const BASE_SPACING = 222; // Base distance between regions (scaled by 1/9)
    const RANDOM_OFFSET = 89; // Random offset range (-44 to +44, scaled by 1/9)
    const BASE_WIDTH = 100;
    const BASE_HEIGHT = 100;

    /**
     * Generate all 25 regions in a 5x5 grid
     * Region 1 (center, 0,0) is already created by migration
     * @return int Number of regions created
     */
    public static function generateAll()
    {
        $created = 0;
        $regionId = 2; // Start from 2, region 1 already exists

        for ($gridY = 0; $gridY < self::GRID_SIZE; $gridY++) {
            for ($gridX = 0; $gridX < self::GRID_SIZE; $gridX++) {
                // Skip center region (already created as region_id=1)
                if ($gridX === 2 && $gridY === 2) {
                    continue;
                }

                // Calculate difficulty (distance from center)
                $difficulty = self::calculateDifficulty($gridX, $gridY);

                // Calculate base coordinates (grid position relative to center)
                $baseX = ($gridX - 2) * self::BASE_SPACING;
                $baseY = ($gridY - 2) * self::BASE_SPACING;

                // Add significant random offset
                $randomX = self::getRandomOffset($regionId);
                $randomY = self::getRandomOffset($regionId + 1000);

                $x = $baseX + $randomX;
                $y = $baseY + $randomY;

                // Vary size based on difficulty
                $width = self::BASE_WIDTH + ($difficulty * 20);
                $height = self::BASE_HEIGHT + ($difficulty * 20);

                $region = new Region();
                $region->region_id = $regionId;
                $region->name = self::generateName($regionId, $difficulty);
                $region->description = self::generateDescription($difficulty);
                $region->difficulty = $difficulty;
                $region->x = $x;
                $region->y = $y;
                $region->width = $width;
                $region->height = $height;
                $region->image_url = "region_{$regionId}.png";

                if ($region->save()) {
                    $created++;
                    echo "Created region {$regionId}: {$region->name} at ({$x}, {$y}), difficulty {$difficulty}\n";
                } else {
                    echo "Failed to create region {$regionId}: " . json_encode($region->errors) . "\n";
                }

                $regionId++;
            }
        }

        return $created;
    }

    /**
     * Calculate difficulty based on distance from center (2,2)
     * @param int $gridX
     * @param int $gridY
     * @return int 1-5
     */
    private static function calculateDifficulty($gridX, $gridY)
    {
        $centerX = 2;
        $centerY = 2;

        $dx = abs($gridX - $centerX);
        $dy = abs($gridY - $centerY);

        // Manhattan distance, capped at 4, +1 to make range 1-5
        $distance = min($dx + $dy, 4);

        return $distance + 1;
    }

    /**
     * Get seeded random offset for coordinate
     * @param int $seed
     * @return int
     */
    private static function getRandomOffset($seed)
    {
        mt_srand($seed);
        return mt_rand(-self::RANDOM_OFFSET / 2, self::RANDOM_OFFSET / 2);
    }

    /**
     * Generate region name based on ID and difficulty
     * @param int $regionId
     * @param int $difficulty
     * @return string
     */
    private static function generateName($regionId, $difficulty)
    {
        $prefixes = [
            1 => ['Green', 'Peaceful', 'Calm', 'Safe', 'Tranquil'],
            2 => ['Golden', 'Bright', 'Sunny', 'Warm', 'Pleasant'],
            3 => ['Amber', 'Blazing', 'Fiery', 'Wild', 'Rugged'],
            4 => ['Crimson', 'Dangerous', 'Harsh', 'Hostile', 'Fierce'],
            5 => ['Violet', 'Deadly', 'Forsaken', 'Cursed', 'Dark'],
        ];

        $suffixes = ['Isle', 'Island', 'Atoll', 'Archipelago', 'Keys', 'Haven', 'Refuge'];

        $prefixList = $prefixes[$difficulty];
        $prefixIndex = ($regionId % count($prefixList));
        $suffixIndex = (floor($regionId / count($prefixList)) % count($suffixes));

        return $prefixList[$prefixIndex] . ' ' . $suffixes[$suffixIndex];
    }

    /**
     * Generate region description based on difficulty
     * @param int $difficulty
     * @return string
     */
    private static function generateDescription($difficulty)
    {
        $descriptions = [
            1 => 'A safe and welcoming region, perfect for beginners.',
            2 => 'A moderately challenging region with decent resources.',
            3 => 'A dangerous region requiring caution and preparation.',
            4 => 'A very hazardous region with valuable but hard-to-reach resources.',
            5 => 'An extremely perilous region where only the bravest dare to venture.',
        ];

        return $descriptions[$difficulty];
    }

    /**
     * Reset all regions (delete all except region_id=1)
     * Useful for regeneration
     * @return int Number of regions deleted
     */
    public static function reset()
    {
        return Region::deleteAll(['>', 'region_id', 1]);
    }
}
