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
     * NOTE: Does not recalculate visibility - must be called separately after map is loaded
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

        // DO NOT call recalculateVisibility() here - tileDataMap is not loaded yet!
        // It will be called after map tiles are loaded in game.js
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
     * Add visibility with line-of-sight raycasting
     * Check DIRECT line of sight to each tile (identical to server-side)
     */
    addVisibilityCircle(centerX, centerY, radius) {
        const radiusSq = radius * radius;

        // Eye position always visible
        this.visibleTiles.add(tileKey(centerX, centerY));

        // Check direct line of sight to each tile in radius
        for (let targetY = centerY - radius; targetY <= centerY + radius; targetY++) {
            for (let targetX = centerX - radius; targetX <= centerX + radius; targetX++) {
                const dx = targetX - centerX;
                const dy = targetY - centerY;
                if (dx * dx + dy * dy > radiusSq) continue;
                if (targetX === centerX && targetY === centerY) continue;

                // Check DIRECT ray to this specific tile
                if (this.hasDirectLineOfSight(centerX, centerY, targetX, targetY)) {
                    this.visibleTiles.add(tileKey(targetX, targetY));
                }
            }
        }
    }

    /**
     * Check if there's direct unobstructed line of sight to specific target
     * Identical to server-side hasLineOfSight()
     */
    hasDirectLineOfSight(x0, y0, x1, y1) {
        const dx = Math.abs(x1 - x0);
        const dy = Math.abs(y1 - y0);
        const sx = x0 < x1 ? 1 : -1;
        const sy = y0 < y1 ? 1 : -1;
        let err = dx - dy;
        let x = x0, y = y0;

        while (true) {
            const isBlocking = this.isBlockingTile(x, y);

            // Stop if blocked (except starting position)
            if (isBlocking && (x !== x0 || y !== y0)) {
                return false;
            }

            // Reached target - line of sight is clear!
            if (x === x1 && y === y1) {
                return true;
            }

            // Bresenham step
            const e2 = 2 * err;
            if (e2 > -dy) { err -= dy; x += sx; }
            if (e2 < dx) { err += dx; y += sy; }
        }
    }

    /**
     * Check if tile blocks line of sight
     */
    isBlockingTile(x, y) {
        const key = tileKey(x, y);
        const landingId = this.game.tileDataMap.get(key);

        if (landingId === undefined) return true; // Off-map

        const landing = this.game.landingTypes[landingId];
        if (!landing) return true;

        return landing.blocks_vision === 'yes';
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
