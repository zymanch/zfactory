import * as PIXI from 'pixi.js';

/**
 * Manages electrification layer - blue dots on powered tiles
 * Renders sparse blue glowing dots on tiles within power radius
 */
export class ElectrificationLayerManager {
    constructor(game) {
        this.game = game;
        this.layer = new PIXI.Container();
        this.layer.zIndex = 1.5; // Between landing (1) and entities (2)
        this.sprites = new Map(); // tileKey => sprite

        // Sprite pool for performance
        this.spritePool = [];
        this.texture = null;
    }

    /**
     * Initialize manager
     */
    async init() {
        // Load electrification texture
        const texturePath = this.game.config.tilesPath + 'electrification.png?v=' + this.game.config.assetVersion;
        this.texture = await PIXI.Assets.load(texturePath);

        // Add layer to world container (same as deposits)
        if (this.game.worldContainer) {
            this.game.worldContainer.addChild(this.layer);
        }
    }

    /**
     * Create electrification sprite for tile
     * @param {number} tileX
     * @param {number} tileY
     * @returns {PIXI.Sprite}
     */
    createElectrificationSprite(tileX, tileY) {
        // Reuse sprite from pool if available
        let sprite = this.spritePool.pop();

        if (!sprite) {
            sprite = new PIXI.Sprite(this.texture);
            sprite.anchor.set(0.5, 0.5);
        }

        sprite.x = tileX;
        sprite.y = tileY;
        sprite.visible = true;

        return sprite;
    }

    /**
     * Return sprite to pool
     * @param {PIXI.Sprite} sprite
     */
    returnToPool(sprite) {
        sprite.visible = false;
        this.layer.removeChild(sprite);
        this.spritePool.push(sprite);
    }

    /**
     * Render electrification layer
     * Should be called when electricity entities change or viewport changes
     */
    render() {
        // Clear existing sprites (return to pool)
        for (const [key, sprite] of this.sprites) {
            this.returnToPool(sprite);
        }
        this.sprites.clear();

        // Get viewport bounds (with padding for smooth transitions)
        const bounds = this.game.camera.getViewportBounds();
        const padding = 128;
        const minX = bounds.left - padding;
        const maxX = bounds.right + padding;
        const minY = bounds.top - padding;
        const maxY = bounds.bottom + padding;

        // Find all built electricity entities with power radius
        const powerSources = [];

        for (const [entityId, entity] of this.game.entityData) {
            if (entity.state !== 'built') {
                continue;
            }

            const radius = this.game.electricityManager.getPowerRadius(entity.entity_type_id);

            if (radius <= 0) continue;

            // Convert tile coordinates to pixel coordinates
            const pixelX = entity.x * 64;
            const pixelY = entity.y * 64;

            powerSources.push({
                x: pixelX,
                y: pixelY,
                radius: radius,
            });
        }

        if (powerSources.length === 0) return;

        // Render electrification dots on tiles within radius
        const tileSize = 64;
        const startTileX = Math.floor(minX / tileSize) * tileSize;
        const startTileY = Math.floor(minY / tileSize) * tileSize;
        const endTileX = Math.ceil(maxX / tileSize) * tileSize;
        const endTileY = Math.ceil(maxY / tileSize) * tileSize;

        for (let tileX = startTileX; tileX <= endTileX; tileX += tileSize) {
            for (let tileY = startTileY; tileY <= endTileY; tileY += tileSize) {
                // Check if tile is within any power radius
                let isElectrified = false;

                for (const source of powerSources) {
                    const dx = tileX - source.x;
                    const dy = tileY - source.y;
                    const distanceSq = dx * dx + dy * dy;
                    const radiusSq = source.radius * source.radius;

                    if (distanceSq <= radiusSq) {
                        isElectrified = true;
                        break;
                    }
                }

                if (isElectrified) {
                    const tileKey = `${tileX},${tileY}`;
                    const sprite = this.createElectrificationSprite(tileX, tileY);
                    this.sprites.set(tileKey, sprite);
                    this.layer.addChild(sprite);
                }
            }
        }
    }

    /**
     * Update layer (called when entities change)
     */
    update() {
        this.render();
    }

    /**
     * Destroy manager
     */
    destroy() {
        // Clear all sprites
        for (const [key, sprite] of this.sprites) {
            sprite.destroy();
        }
        this.sprites.clear();

        // Clear pool
        for (const sprite of this.spritePool) {
            sprite.destroy();
        }
        this.spritePool = [];

        // Remove layer
        if (this.layer.parent) {
            this.layer.parent.removeChild(this.layer);
        }
        this.layer.destroy();
    }
}
