import { tileKey, tileToWorld } from './utils.js';

/**
 * BuildingRules - client-side building placement rules
 */
export class BuildingRules {
    constructor(game) {
        this.game = game;
        this.requiresTarget = {};
        this.resourceEntityTypes = [];
    }

    /**
     * Initialize rules from server config
     */
    init(rules) {
        if (!rules) return;
        this.requiresTarget = rules.requiresTarget || {};
        this.resourceEntityTypes = rules.resourceEntityTypes || [];
    }

    /**
     * Check if building type requires a target entity
     */
    requiresTargetEntity(entityTypeId) {
        return this.requiresTarget.hasOwnProperty(entityTypeId);
    }

    /**
     * Get allowed target entity types for a building
     */
    getAllowedTargets(entityTypeId) {
        return this.requiresTarget[entityTypeId] || [];
    }

    /**
     * Check if entity type is a resource node
     */
    isResourceEntity(entityTypeId) {
        return this.resourceEntityTypes.includes(parseInt(entityTypeId));
    }

    /**
     * Check if building can be placed at tile position
     * @param {number} entityTypeId Building type to place
     * @param {number} tileX Tile X coordinate
     * @param {number} tileY Tile Y coordinate
     * @returns {object} { allowed: bool, targetEntity: object|null, error: string|null }
     */
    canPlace(entityTypeId, tileX, tileY) {
        // If building doesn't require a target, allow placement
        if (!this.requiresTargetEntity(entityTypeId)) {
            return { allowed: true, targetEntity: null, error: null };
        }

        const allowedTargets = this.getAllowedTargets(entityTypeId);
        const { tileWidth, tileHeight } = this.game.config;
        const pos = tileToWorld(tileX, tileY, tileWidth, tileHeight);

        // Find entity at this position
        const targetEntity = this.findEntityAt(pos.x, pos.y);

        if (!targetEntity) {
            return {
                allowed: false,
                targetEntity: null,
                error: 'Requires a resource node'
            };
        }

        // Check if target entity type is allowed
        const targetTypeId = parseInt(targetEntity.entity_type_id);
        if (!allowedTargets.includes(targetTypeId)) {
            const entityType = this.game.entityTypes[targetTypeId];
            return {
                allowed: false,
                targetEntity: null,
                error: `Cannot place on ${entityType?.name || 'this entity'}`
            };
        }

        return {
            allowed: true,
            targetEntity: targetEntity,
            error: null
        };
    }

    /**
     * Find entity at pixel position
     */
    findEntityAt(x, y) {
        for (const [key, entityData] of this.game.entityData) {
            if (parseInt(entityData.x) === x && parseInt(entityData.y) === y) {
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
        const { tileWidth, tileHeight } = this.game.config;
        const pos = tileToWorld(tileX, tileY, tileWidth, tileHeight);
        const entity = this.findEntityAt(pos.x, pos.y);

        if (entity && this.isResourceEntity(entity.entity_type_id)) {
            return entity;
        }
        return null;
    }
}

export default BuildingRules;
