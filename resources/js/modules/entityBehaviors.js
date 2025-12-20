import { tileKey, rectsOverlap } from './utils.js';

/**
 * Base class for entity behaviors
 * Provides placement validation and behavior info
 */
class EntityBehavior {
    constructor(game, entityType) {
        this.game = game;
        this.entityType = entityType;
        this.tileWidth = game.config.tileWidth;
        this.tileHeight = game.config.tileHeight;
    }

    /**
     * Check if entity can be built at tile position
     * @param {number} tileX Tile X coordinate
     * @param {number} tileY Tile Y coordinate
     * @returns {object} { allowed: bool, error: string|null, targetEntity: object|null }
     */
    canBuildAt(tileX, tileY) {
        throw new Error('canBuildAt must be implemented by subclass');
    }

    /**
     * Check if entity should show hover tooltip
     */
    shouldShowHoverInfo() {
        return true;
    }

    /**
     * Check if entity is indestructible
     */
    isIndestructible() {
        return false;
    }

    /**
     * Check if tile is visible (not in fog)
     */
    isTileVisible(tileX, tileY) {
        const fog = this.game.fogOfWar;
        if (!fog || !fog.enabled || fog.eyeEntities.size === 0) {
            return true;
        }
        return fog.visibleTiles.has(tileKey(tileX, tileY));
    }

    /**
     * Check if all tiles for entity are visible
     */
    areAllTilesVisible(tileX, tileY) {
        const width = parseInt(this.entityType.width) || 1;
        const height = parseInt(this.entityType.height) || 1;

        for (let dx = 0; dx < width; dx++) {
            for (let dy = 0; dy < height; dy++) {
                if (!this.isTileVisible(tileX + dx, tileY + dy)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Check if landing at tile is buildable
     */
    isLandingBuildable(tileX, tileY) {
        const key = tileKey(tileX, tileY);
        const landingId = this.game.tileDataMap.get(key);

        if (!landingId) {
            return false;
        }

        const landing = this.game.landingTypes[landingId];
        if (!landing) {
            return false;
        }

        return landing.is_buildable === 'yes';
    }

    /**
     * Check if all tiles for entity are buildable
     */
    areAllTilesBuildable(tileX, tileY) {
        const width = parseInt(this.entityType.width) || 1;
        const height = parseInt(this.entityType.height) || 1;

        for (let dx = 0; dx < width; dx++) {
            for (let dy = 0; dy < height; dy++) {
                if (!this.isLandingBuildable(tileX + dx, tileY + dy)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Check if there's an entity collision at tile
     * Entity coordinates are tile-based
     * @param {number} tileX Tile X coordinate
     * @param {number} tileY Tile Y coordinate
     * @param {number|null} excludeEntityId Entity ID to exclude from collision check
     */
    hasEntityCollision(tileX, tileY, excludeEntityId = null) {
        const width = parseInt(this.entityType.width) || 1;
        const height = parseInt(this.entityType.height) || 1;

        for (const [key, entityData] of this.game.entityData) {
            if (excludeEntityId !== null && parseInt(entityData.entity_id) === excludeEntityId) {
                continue;
            }

            const eType = this.game.entityTypes[entityData.entity_type_id];
            const eWidth = parseInt(eType?.width) || 1;
            const eHeight = parseInt(eType?.height) || 1;
            const eX = parseInt(entityData.x);
            const eY = parseInt(entityData.y);

            // AABB collision check with tile coordinates
            if (rectsOverlap(
                tileX, tileY, width, height,
                eX, eY, eWidth, eHeight
            )) {
                return true;
            }
        }
        return false;
    }

    /**
     * Find entity at tile position
     * Entity coordinates are tile-based
     */
    findEntityAt(tileX, tileY) {
        for (const [key, entityData] of this.game.entityData) {
            if (parseInt(entityData.x) === tileX && parseInt(entityData.y) === tileY) {
                if (entityData.state === 'built') {
                    return entityData;
                }
            }
        }
        return null;
    }

    /**
     * Find resource entity at tile position
     */
    findResourceEntityAt(tileX, tileY) {
        const entity = this.findEntityAt(tileX, tileY);
        if (!entity) return null;

        const entityType = this.game.entityTypes[entity.entity_type_id];
        if (entityType && entityType.type === 'resource') {
            return entity;
        }
        return null;
    }

    /**
     * Helper: create error response
     */
    error(message) {
        return { allowed: false, error: message, targetEntity: null };
    }

    /**
     * Helper: create success response
     */
    success(targetEntity = null) {
        return { allowed: true, error: null, targetEntity };
    }
}

/**
 * Default behavior for buildings, transporters, manipulators
 */
class DefaultEntityBehavior extends EntityBehavior {
    canBuildAt(tileX, tileY) {
        // 1. Check fog of war
        if (!this.areAllTilesVisible(tileX, tileY)) {
            return this.error('Cannot build in fog of war');
        }

        // 2. Check landing is buildable
        if (!this.areAllTilesBuildable(tileX, tileY)) {
            return this.error('Cannot build on this terrain');
        }

        // 3. Check entity collision
        if (this.hasEntityCollision(tileX, tileY)) {
            return this.error('Position is occupied');
        }

        return this.success();
    }
}

/**
 * Behavior for mining entities (Mining Drill, Fast Mining Drill)
 */
class MiningEntityBehavior extends EntityBehavior {
    constructor(game, entityType) {
        super(game, entityType);
        this.allowedResourceTypes = this.loadAllowedResourceTypes();
    }

    loadAllowedResourceTypes() {
        const types = [];
        for (const id in this.game.entityTypes) {
            if (this.game.entityTypes[id].type === 'resource') {
                types.push(parseInt(id));
            }
        }
        return types;
    }

    canBuildAt(tileX, tileY) {
        // 1. Check fog of war
        if (!this.areAllTilesVisible(tileX, tileY)) {
            return this.error('Cannot build in fog of war');
        }

        // 2. Check for resource entity
        const resourceEntity = this.findResourceEntityAt(tileX, tileY);
        if (!resourceEntity) {
            return this.error('Requires a resource node');
        }

        // 3. Check if resource type is allowed
        const resourceTypeId = parseInt(resourceEntity.entity_type_id);
        if (!this.allowedResourceTypes.includes(resourceTypeId)) {
            const entityType = this.game.entityTypes[resourceTypeId];
            return this.error(`Cannot place on ${entityType?.name || 'this resource'}`);
        }

        return this.success(resourceEntity);
    }
}

/**
 * Behavior for resource entities (ores)
 */
class ResourceEntityBehavior extends EntityBehavior {
    canBuildAt(tileX, tileY) {
        return this.error('Resources cannot be placed by player');
    }

    shouldShowHoverInfo() {
        return true;
    }

    isIndestructible() {
        return true;
    }
}

/**
 * Behavior for relief entities (rocks)
 */
class ReliefEntityBehavior extends EntityBehavior {
    canBuildAt(tileX, tileY) {
        return this.error('Relief cannot be placed by player');
    }

    shouldShowHoverInfo() {
        return true;
    }

    isIndestructible() {
        return true;
    }
}

/**
 * Behavior for tree entities
 */
class TreeEntityBehavior extends EntityBehavior {
    canBuildAt(tileX, tileY) {
        return this.error('Trees cannot be placed by player');
    }

    shouldShowHoverInfo() {
        return false;
    }

    isIndestructible() {
        return false;
    }
}

/**
 * Behavior for eye entities (Crystal Towers)
 */
class EyeEntityBehavior extends DefaultEntityBehavior {
    shouldShowHoverInfo() {
        return true;
    }

    isIndestructible() {
        return false;
    }
}

/**
 * Factory for creating entity behaviors
 */
export class EntityBehaviorFactory {
    static TYPE_BEHAVIORS = {
        'mining': MiningEntityBehavior,
        'building': DefaultEntityBehavior,
        'transporter': DefaultEntityBehavior,
        'manipulator': DefaultEntityBehavior,
        'tree': TreeEntityBehavior,
        'relief': ReliefEntityBehavior,
        'resource': ResourceEntityBehavior,
        'eye': EyeEntityBehavior,
    };

    static cache = new Map();

    /**
     * Create or get cached behavior for entity type
     */
    static create(game, entityTypeId) {
        if (this.cache.has(entityTypeId)) {
            return this.cache.get(entityTypeId);
        }

        const entityType = game.entityTypes[entityTypeId];
        if (!entityType) {
            return null;
        }

        const BehaviorClass = this.TYPE_BEHAVIORS[entityType.type] || DefaultEntityBehavior;
        const behavior = new BehaviorClass(game, entityType);

        this.cache.set(entityTypeId, behavior);
        return behavior;
    }

    /**
     * Clear cache
     */
    static clearCache() {
        this.cache.clear();
    }

    /**
     * Check if entity type requires target entity
     */
    static requiresTargetEntity(game, entityTypeId) {
        const behavior = this.create(game, entityTypeId);
        return behavior instanceof MiningEntityBehavior;
    }

    /**
     * Get allowed target types for mining entities
     */
    static getAllowedTargetTypes(game, entityTypeId) {
        const behavior = this.create(game, entityTypeId);
        if (behavior instanceof MiningEntityBehavior) {
            return behavior.allowedResourceTypes;
        }
        return [];
    }
}

export {
    EntityBehavior,
    DefaultEntityBehavior,
    MiningEntityBehavior,
    ResourceEntityBehavior,
    ReliefEntityBehavior,
    TreeEntityBehavior,
    EyeEntityBehavior
};

export default EntityBehaviorFactory;
