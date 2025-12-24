<?php

namespace services\behaviors;

use models\Entity;
use models\EntityType;
use models\Landing;
use models\Map;
use Yii;

/**
 * Base class for entity type behaviors
 * Each entity type can have different placement rules and behaviors
 */
abstract class EntityBehavior
{
    /** @var EntityType */
    protected $entityType;

    /** @var int */
    protected $tileWidth;

    /** @var int */
    protected $tileHeight;

    /**
     * @param EntityType $entityType
     */
    public function __construct(EntityType $entityType)
    {
        $this->entityType = $entityType;
        $this->tileWidth = Yii::$app->params['tile_width'] ?? 64;
        $this->tileHeight = Yii::$app->params['tile_height'] ?? 64;
    }

    /**
     * Check if entity can be built at specified coordinates
     *
     * @param int $tileX Tile X coordinate
     * @param int $tileY Tile Y coordinate
     * @param array $visibleTiles Array of visible tile keys (for fog check), null if no fog
     * @return array ['allowed' => bool, 'error' => string|null, 'targetEntity' => Entity|null]
     */
    abstract public function canBuildAt(int $tileX, int $tileY, ?array $visibleTiles = null): array;

    /**
     * Check if entity should show hover tooltip information
     * Default: true for all entities
     */
    public function shouldShowHoverInfo(): bool
    {
        return true;
    }

    /**
     * Check if entity is indestructible (cannot be destroyed/damaged)
     * Default: false
     */
    public function isIndestructible(): bool
    {
        return false;
    }

    /**
     * Get tile key for visibility checks
     */
    protected function getTileKey(int $tileX, int $tileY): string
    {
        return "{$tileX}_{$tileY}";
    }

    /**
     * Check if tile is visible (not in fog of war)
     */
    protected function isTileVisible(int $tileX, int $tileY, ?array $visibleTiles): bool
    {
        // If no fog data provided, assume visible
        if ($visibleTiles === null) {
            return true;
        }
        return in_array($this->getTileKey($tileX, $tileY), $visibleTiles);
    }

    /**
     * Check if all tiles for entity are visible (using tile coordinates)
     */
    protected function areAllTilesVisible(int $tileX, int $tileY, ?array $visibleTiles): bool
    {
        if ($visibleTiles === null) {
            return true;
        }

        $width = $this->entityType->width ?? 1;
        $height = $this->entityType->height ?? 1;

        for ($dx = 0; $dx < $width; $dx++) {
            for ($dy = 0; $dy < $height; $dy++) {
                if (!$this->isTileVisible($tileX + $dx, $tileY + $dy, $visibleTiles)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Check if landing at tile allows building
     */
    protected function isLandingBuildable(int $tileX, int $tileY): bool
    {
        $map = Map::find()
            ->where(['x' => $tileX, 'y' => $tileY])
            ->one();

        if (!$map) {
            return false;
        }

        $landing = Landing::findOne($map->landing_id);
        if (!$landing) {
            return false;
        }

        return $landing->is_buildable === 'yes';
    }

    /**
     * Check if all tiles for entity allow building (using tile coordinates)
     */
    protected function areAllTilesBuildable(int $tileX, int $tileY): bool
    {
        $width = $this->entityType->width ?? 1;
        $height = $this->entityType->height ?? 1;

        for ($dx = 0; $dx < $width; $dx++) {
            for ($dy = 0; $dy < $height; $dy++) {
                if (!$this->isLandingBuildable($tileX + $dx, $tileY + $dy)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Check if there's an entity collision at specified area (using tile coordinates)
     *
     * @param int $tileX Tile X coordinate
     * @param int $tileY Tile Y coordinate
     * @param int|null $excludeEntityId Entity ID to exclude from collision check
     * @return bool True if collision exists
     */
    protected function hasEntityCollision(int $tileX, int $tileY, ?int $excludeEntityId = null): bool
    {
        $width = $this->entityType->width ?? 1;
        $height = $this->entityType->height ?? 1;

        // Check for AABB collision with tile coordinates
        $query = Entity::find()
            ->alias('e')
            ->innerJoin(EntityType::tableName() . ' et', 'e.entity_type_id = et.entity_type_id')
            ->where(['and',
                ['<', 'e.x', $tileX + $width],
                ['>', new \yii\db\Expression('e.x + COALESCE(et.width, 1)'), $tileX],
                ['<', 'e.y', $tileY + $height],
                ['>', new \yii\db\Expression('e.y + COALESCE(et.height, 1)'), $tileY],
            ]);

        if ($excludeEntityId !== null) {
            $query->andWhere(['!=', 'e.entity_id', $excludeEntityId]);
        }

        return $query->exists();
    }

    /**
     * Find entity at exact tile position
     */
    protected function findEntityAt(int $tileX, int $tileY): ?Entity
    {
        return Entity::find()
            ->where(['x' => $tileX, 'y' => $tileY])
            ->andWhere(['state' => 'built'])
            ->one();
    }

    /**
     * Find resource entity at tile position
     */
    protected function findResourceEntityAt(int $tileX, int $tileY): ?Entity
    {
        return Entity::find()
            ->alias('e')
            ->innerJoin(EntityType::tableName() . ' et', 'e.entity_type_id = et.entity_type_id')
            ->where(['e.x' => $tileX, 'e.y' => $tileY])
            ->andWhere(['e.state' => 'built'])
            ->andWhere(['et.type' => 'resource'])
            ->one();
    }

    /**
     * Get behavior info for client-side
     */
    public function getClientInfo(): array
    {
        return [
            'showHoverInfo' => $this->shouldShowHoverInfo(),
            'indestructible' => $this->isIndestructible(),
        ];
    }
}
