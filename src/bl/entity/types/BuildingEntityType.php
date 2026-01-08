<?php

namespace bl\entity\types;

use bl\entity\generators\base\AbstractEntityGenerator;
use bl\entity\generators\building;

/**
 * Base class for building entity types (furnace, assembler, drill, etc.)
 */
abstract class BuildingEntityType extends AbstractEntityType
{
    /**
     * Get entity type category
     */
    public function getTypeCategory(): string
    {
        return 'building';
    }

    /**
     * Whether this building produces power
     */
    public function producesPower(): bool
    {
        return $this->power > 0;
    }

    /**
     * Whether this building consumes power
     */
    public function consumesPower(): bool
    {
        return $this->power < 0;
    }

    /**
     * Get power production/consumption (positive = production)
     */
    public function getPowerValue(): int
    {
        return $this->power ?? 0;
    }

    /**
     * Get generator for this building type
     * @return AbstractEntityGenerator|null
     */
    public function getGenerator(): ?AbstractEntityGenerator
    {
        $generatorClass = null;

        switch ($this->image_url) {
            case 'furnace':
                $generatorClass = building\FurnaceGenerator::class;
                break;
            case 'assembler':
                $generatorClass = building\AssemblerGenerator::class;
                break;
            case 'chest':
                $generatorClass = building\ChestGenerator::class;
                break;
            case 'power_pole':
                $generatorClass = building\PowerPoleGenerator::class;
                break;
            case 'steam_engine':
                $generatorClass = building\SteamEngineGenerator::class;
                break;
            case 'boiler':
                $generatorClass = building\BoilerGenerator::class;
                break;
            case 'press':
                $generatorClass = building\PressGenerator::class;
                break;
            case 'drill':
                $generatorClass = building\DrillGenerator::class;
                break;
            case 'drill_fast':
                $generatorClass = building\DrillFastGenerator::class;
                break;
            case 'drill_large':
                $generatorClass = building\DrillLargeGenerator::class;
                break;
            case 'hq':
                $generatorClass = building\HqGenerator::class;
                break;
            case 'sawmill_small':
                $generatorClass = building\SawmillSmallGenerator::class;
                break;
            case 'sawmill_medium':
                $generatorClass = building\SawmillMediumGenerator::class;
                break;
            case 'sawmill_large':
                $generatorClass = building\SawmillLargeGenerator::class;
                break;
            case 'stone_quarry_small':
                $generatorClass = building\StoneQuarrySmallGenerator::class;
                break;
            case 'stone_quarry_medium':
                $generatorClass = building\StoneQuarryMediumGenerator::class;
                break;
            case 'stone_quarry_large':
                $generatorClass = building\StoneQuarryLargeGenerator::class;
                break;
            case 'mine_small':
                $generatorClass = building\MineSmallGenerator::class;
                break;
            case 'mine_medium':
                $generatorClass = building\MineMediumGenerator::class;
                break;
            case 'mine_large':
                $generatorClass = building\MineLargeGenerator::class;
                break;
            case 'quarry_small':
                $generatorClass = building\QuarrySmallGenerator::class;
                break;
            case 'quarry_medium':
                $generatorClass = building\QuarryMediumGenerator::class;
                break;
            case 'quarry_large':
                $generatorClass = building\QuarryLargeGenerator::class;
                break;
        }

        return $generatorClass ? new $generatorClass($this) : null;
    }
}
