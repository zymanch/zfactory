import * as PIXI from 'pixi.js';
import { tileKey, parseTileKey } from './utils.js';
import { Z_INDEX, FOG_COLOR, FOG_FULL_ALPHA, FOG_EDGE_ALPHA } from './constants.js';

/**
 * FogOfWar - manages visibility based on 'eye' type entities
 */
export class FogOfWar {
    constructor(game) {
        this.game = game;
        this.enabled = true;
        this.visibleTiles = new Set();
        this.eyeEntities = new Map();
        this.fogSprites = new Map();
        this.fogLayer = null;
        this.initialized = false;
        this.fogNeedsUpdate = false;
    }

    /**
     * Initialize fog layer
     */
    init() {
        this.fogLayer = new PIXI.Container();
        this.fogLayer.sortableChildren = false;
        this.fogLayer.zIndex = Z_INDEX.FOG;
        this.game.worldContainer.addChild(this.fogLayer);

        this.loadInitialEyeEntities();
        this.initialized = true;
    }

    /**
     * Load eye entities from initial config
     * Entity coordinates are stored as tiles
     */
    loadInitialEyeEntities() {
        const eyeEntities = this.game.initialEyeEntities || [];

        for (const entity of eyeEntities) {
            const entityType = this.game.entityTypes[entity.entity_type_id];
            if (!entityType) continue;

            const power = parseInt(entityType.power) || 1;

            this.eyeEntities.set(parseInt(entity.entity_id), {
                x: parseInt(entity.x),
                y: parseInt(entity.y),
                power: power
            });
        }

        this.recalculateVisibility();
    }

    /**
     * Add a new eye entity
     * Coordinates are tile-based
     */
    addEyeEntity(entityId, entityTypeId, tileX, tileY) {
        const entityType = this.game.entityTypes[entityTypeId];
        if (!entityType || entityType.type !== 'eye') return;

        const power = parseInt(entityType.power) || 1;

        this.eyeEntities.set(parseInt(entityId), {
            x: tileX,
            y: tileY,
            power: power
        });

        this.recalculateVisibility();
    }

    /**
     * Remove an eye entity
     */
    removeEyeEntity(entityId) {
        if (this.eyeEntities.delete(parseInt(entityId))) {
            this.recalculateVisibility();
        }
    }

    /**
     * Recalculate visibility mask
     */
    recalculateVisibility() {
        this.visibleTiles.clear();

        for (const [id, eye] of this.eyeEntities) {
            this.addVisibilityCircle(eye.x, eye.y, eye.power);
        }

        this.fogNeedsUpdate = true;
    }

    /**
     * Add circular visibility area
     */
    addVisibilityCircle(centerX, centerY, radius) {
        const radiusSq = radius * radius;

        for (let dy = -radius; dy <= radius; dy++) {
            for (let dx = -radius; dx <= radius; dx++) {
                if (dx * dx + dy * dy <= radiusSq) {
                    this.visibleTiles.add(tileKey(centerX + dx, centerY + dy));
                }
            }
        }
    }

    /**
     * Check if tile is at fog edge
     */
    isEdgeTile(x, y) {
        const neighbors = [
            [x - 1, y], [x + 1, y], [x, y - 1], [x, y + 1],
            [x - 1, y - 1], [x + 1, y - 1], [x - 1, y + 1], [x + 1, y + 1]
        ];
        return neighbors.some(([nx, ny]) => this.visibleTiles.has(tileKey(nx, ny)));
    }

    /**
     * Render fog for viewport
     */
    renderFog(startTileX, startTileY, viewWidth, viewHeight) {
        if (!this.enabled || this.eyeEntities.size === 0) {
            this.clearFog();
            return;
        }

        if (this.fogNeedsUpdate) {
            this.updateFogVisibility();
            this.fogNeedsUpdate = false;
        }

        const newFogKeys = new Set();

        for (let x = startTileX; x < startTileX + viewWidth; x++) {
            for (let y = startTileY; y < startTileY + viewHeight; y++) {
                const key = tileKey(x, y);

                if (this.visibleTiles.has(key)) continue;

                newFogKeys.add(key);

                if (!this.fogSprites.has(key)) {
                    const isEdge = this.isEdgeTile(x, y);
                    const sprite = this.createFogSprite(x, y, isEdge);
                    this.fogLayer.addChild(sprite);
                    this.fogSprites.set(key, { sprite, isEdge });
                }
            }
        }

        this.cleanupFogSprites(newFogKeys);
    }

    /**
     * Create fog sprite at position
     */
    createFogSprite(tileX, tileY, isEdge) {
        const { tileWidth, tileHeight } = this.game.config;
        const alpha = isEdge ? FOG_EDGE_ALPHA : FOG_FULL_ALPHA;

        const fog = new PIXI.Graphics();
        fog.rect(0, 0, tileWidth, tileHeight);
        fog.fill({ color: FOG_COLOR, alpha });
        fog.x = tileX * tileWidth;
        fog.y = tileY * tileHeight;

        return fog;
    }

    /**
     * Update fog visibility without clearing
     */
    updateFogVisibility() {
        const { tileWidth, tileHeight } = this.game.config;

        for (const [key, data] of this.fogSprites) {
            if (this.visibleTiles.has(key)) {
                this.fogLayer.removeChild(data.sprite);
                data.sprite.destroy();
                this.fogSprites.delete(key);
            } else {
                const { x, y } = parseTileKey(key);
                const isEdge = this.isEdgeTile(x, y);

                if (isEdge !== data.isEdge) {
                    this.fogLayer.removeChild(data.sprite);
                    data.sprite.destroy();

                    const sprite = this.createFogSprite(x, y, isEdge);
                    this.fogLayer.addChild(sprite);
                    this.fogSprites.set(key, { sprite, isEdge });
                }
            }
        }
    }

    /**
     * Remove fog sprites not in viewport
     */
    cleanupFogSprites(validKeys) {
        for (const [key, data] of this.fogSprites) {
            if (!validKeys.has(key)) {
                this.fogLayer.removeChild(data.sprite);
                data.sprite.destroy();
                this.fogSprites.delete(key);
            }
        }
    }

    /**
     * Clear all fog sprites
     */
    clearFog() {
        for (const [key, data] of this.fogSprites) {
            this.fogLayer.removeChild(data.sprite);
            data.sprite.destroy();
        }
        this.fogSprites.clear();
    }

    /**
     * Check if entity is visible
     * Entity coordinates are tile-based
     */
    isEntityVisible(entityData) {
        if (!this.enabled || this.eyeEntities.size === 0) return true;

        const tileX = parseInt(entityData.x);
        const tileY = parseInt(entityData.y);

        return this.visibleTiles.has(tileKey(tileX, tileY));
    }

    /**
     * Toggle fog on/off
     */
    toggle() {
        this.enabled = !this.enabled;
        if (!this.enabled) {
            this.clearFog();
        }
        this.game.needsReload = true;
    }
}

export default FogOfWar;
