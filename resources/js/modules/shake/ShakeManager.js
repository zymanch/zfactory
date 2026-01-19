/**
 * ShakeManager - Manages landscape shake zones with duplicate landing rendering
 * Uses fog of war pattern: creates duplicate landing sprites ONLY for shake tiles
 *
 * Performance optimizations:
 * - Early exit if no shake zones exist
 * - Viewport culling (only visible tiles)
 * - Lazy sprite creation
 * - Smooth sin/cos animation
 */
export class ShakeManager {
    constructor(game) {
        this.game = game;
        this.shakeZones = new Map();  // "x_y" => intensity (0.00-1.00)
        this.shakeLandingSprites = new Map();  // "x_y" => PIXI.Sprite
        this.shakeLayer = null;  // Container for shake sprites (Z_INDEX.SHAKE = 100)
        this.hasShakeZones = false;  // Early exit flag
        this.shakeTime = 0;  // Accumulated time for animation
        this.lastUpdateTime = performance.now();
        this.initialized = false;  // Flag to track if layer is created
        this.lastDamageTick = 0;  // Last tick when damage was applied
        this.damageInterval = 180;  // Apply damage every 180 ticks (3 seconds at 60fps)
    }

    /**
     * Initialize shake manager - load zones from tiles data
     * NOTE: Layer and sprites are created lazily in update() after assets are loaded
     */
    init() {
        // Load shake zones from tiles data (dictionary format {"x_y": intensity})
        if (this.game.shakeZones && Object.keys(this.game.shakeZones).length > 0) {
            for (const [key, intensity] of Object.entries(this.game.shakeZones)) {
                this.shakeZones.set(key, parseFloat(intensity));
            }
            this.hasShakeZones = this.shakeZones.size > 0;
        }
    }

    /**
     * Ensure shake layer is created (called on first update)
     */
    ensureInitialized() {
        if (this.initialized) {
            return;
        }

        // Create shake layer container (Z_INDEX.SHAKE = 1.5, between TERRAIN and ENTITIES)
        this.shakeLayer = this.game.graphics.createContainer({
            sortableChildren: false,
            zIndex: 1.5  // Between landing (1) and entities (2)
        });
        this.game.worldContainer.addChild(this.shakeLayer);
        this.initialized = true;
    }

    /**
     * Main update loop - called every frame from game loop
     * @param {number} deltaTime - Delta time in seconds
     */
    update(deltaTime) {
        // CRITICAL OPTIMIZATION: Early exit if no shake zones exist
        if (!this.hasShakeZones) {
            return;
        }

        // Wait for tileManager to be ready (has loaded tiles)
        if (!this.game.tileManager || this.game.tileManager.loadedTiles.size === 0) {
            return;
        }

        // Ensure layer is created (happens on first update after assets are loaded)
        this.ensureInitialized();

        // Update shake time for smooth animation
        const now = performance.now();
        const deltaMs = now - this.lastUpdateTime;
        this.lastUpdateTime = now;
        this.shakeTime += deltaMs / 1000;  // Convert to seconds

        // Update duplicate landing sprites (viewport-aware)
        this.updateShakeTiles();

        // Update entity sprite shake offsets
        this.updateEntityShake();

        // Update deposit sprite shake offsets
        this.updateDepositShake();

        // Apply damage to entities on shake tiles
        this.applyShakeDamage();
    }

    /**
     * Update shake landing sprites within viewport
     */
    updateShakeTiles() {
        const viewport = this.game.calculateViewport();
        const { startX, startY, width, height } = viewport;
        const newKeys = new Set();

        for (let y = startY; y < startY + height; y++) {
            for (let x = startX; x < startX + width; x++) {
                const key = `${x}_${y}`;
                const intensity = this.shakeZones.get(key);

                // CRITICAL: Only create sprite if shake exists and > 0
                if (!intensity || intensity <= 0) {
                    continue;
                }

                newKeys.add(key);

                // Lazy sprite creation - only create if not exists
                if (!this.shakeLandingSprites.has(key)) {
                    const sprite = this.createShakeLandingSprite(x, y);
                    if (sprite) {
                        this.shakeLayer.addChild(sprite);
                        this.shakeLandingSprites.set(key, sprite);
                    }
                }

                // Apply shake offset to landing sprite
                const sprite = this.shakeLandingSprites.get(key);
                if (sprite) {
                    this.applyShakeOffset(sprite, intensity, x, y);
                }
            }
        }

        // Cleanup sprites outside viewport
        this.cleanupSprites(newKeys);
    }

    /**
     * Create duplicate landing sprite for shake tile
     * Uses TileLayerManager to create the sprite (same way as regular tiles)
     * @param {number} tileX - Tile X coordinate
     * @param {number} tileY - Tile Y coordinate
     * @returns {PIXI.Sprite|null}
     */
    createShakeLandingSprite(tileX, tileY) {
        try {
            const key = `${tileX}_${tileY}`;

            // Get landing_id from tiles data
            const landingId = this.game.tilesData[key];
            if (landingId === undefined) {
                return null;
            }

            // Use tileManager to create the sprite (ensures consistency)
            const sprite = this.game.tileManager.createTileWithTransitions(landingId, tileX, tileY);
            if (!sprite) {
                return null;
            }

            // Store base position (will be offset by shake)
            sprite.baseX = sprite.x;
            sprite.baseY = sprite.y;

            return sprite;
        } catch (error) {
            console.error(`[ShakeManager] Error creating shake sprite at (${tileX}, ${tileY}):`, error);
            return null;
        }
    }

    /**
     * Apply shake offset to sprite using smooth sin/cos animation
     * @param {PIXI.Sprite} sprite - Sprite to shake
     * @param {number} intensity - Shake intensity (0.00-1.00)
     * @param {number} baseTileX - Base tile X (for variation)
     * @param {number} baseTileY - Base tile Y (for variation)
     */
    applyShakeOffset(sprite, intensity, baseTileX, baseTileY) {
        if (!sprite) {
            return;
        }

        // Maximum offset: 3px at intensity=1.0, scales linearly, capped at 3px
        const maxOffset = Math.min(3 * intensity, 3);

        // Unique angle per tile (based on position) for natural look
        const angle = this.shakeTime * 8 + (baseTileX + baseTileY);

        // Sin/cos for smooth circular motion
        const offsetX = Math.sin(angle) * maxOffset;
        const offsetY = Math.cos(angle * 1.3) * maxOffset;  // Different frequency for Y

        sprite.x = sprite.baseX + offsetX;
        sprite.y = sprite.baseY + offsetY;
    }

    /**
     * Update entity sprite shake (all entities on shake tiles shake too)
     */
    updateEntityShake() {
        // Apply shake to all visible entity sprites
        for (const [key, sprite] of this.game.loadedEntities) {
            const entity = this.game.entityData.get(key);
            if (!entity || !sprite.baseX) {
                continue;
            }

            // Calculate average shake intensity at entity position
            const intensity = this.getEntityShakeIntensity(entity);

            if (intensity > 0) {
                // Apply shake offset
                this.applyShakeOffset(sprite, intensity, entity.x, entity.y);
            } else {
                // Reset to base position (no shake)
                sprite.x = sprite.baseX;
                sprite.y = sprite.baseY;
            }
        }
    }

    /**
     * Calculate average shake intensity for entity (multi-tile support)
     * @param {Object} entity - Entity object
     * @returns {number} Average shake intensity
     */
    getEntityShakeIntensity(entity) {
        const entityType = this.game.entityTypes[entity.entity_type_id];
        if (!entityType) {
            return 0;
        }

        let totalShake = 0;
        let tileCount = 0;

        // Get average shake from all tiles under entity footprint
        for (let dy = 0; dy < entityType.height; dy++) {
            for (let dx = 0; dx < entityType.width; dx++) {
                const key = `${entity.x + dx}_${entity.y + dy}`;
                const intensity = this.shakeZones.get(key) || 0;
                totalShake += intensity;
                tileCount++;
            }
        }

        return tileCount > 0 ? totalShake / tileCount : 0;
    }

    /**
     * Update deposit sprite shake (all deposits on shake tiles shake too)
     */
    updateDepositShake() {
        if (!this.game.depositLayerManager) {
            return;
        }

        // Apply shake to all deposit sprites
        for (const [depositId, sprite] of Object.entries(this.game.depositLayerManager.sprites)) {
            const deposit = this.game.depositLayerManager.deposits[depositId];
            if (!deposit || !sprite.baseX) {
                continue;
            }

            // Calculate average shake intensity at deposit position
            const intensity = this.getDepositShakeIntensity(deposit);

            if (intensity > 0) {
                // Apply shake offset
                this.applyShakeOffset(sprite, intensity, deposit.x, deposit.y);
            } else {
                // Reset to base position (no shake)
                sprite.x = sprite.baseX;
                sprite.y = sprite.baseY;
            }
        }
    }

    /**
     * Calculate average shake intensity for deposit (multi-tile support)
     * @param {Object} deposit - Deposit object
     * @returns {number} Average shake intensity
     */
    getDepositShakeIntensity(deposit) {
        const depositType = this.game.depositTypes[deposit.deposit_type_id];
        if (!depositType) {
            return 0;
        }

        let totalShake = 0;
        let tileCount = 0;

        // Get average shake from all tiles under deposit footprint
        for (let dy = 0; dy < depositType.height; dy++) {
            for (let dx = 0; dx < depositType.width; dx++) {
                const key = `${deposit.x + dx}_${deposit.y + dy}`;
                const intensity = this.shakeZones.get(key) || 0;
                totalShake += intensity;
                tileCount++;
            }
        }

        return tileCount > 0 ? totalShake / tileCount : 0;
    }

    /**
     * Apply shake damage to entities (runs every 180 ticks = 3 seconds)
     */
    applyShakeDamage() {
        const currentTick = this.game.gameTick;

        // Only apply damage every 180 ticks
        if (currentTick - this.lastDamageTick < this.damageInterval) {
            return;
        }

        this.lastDamageTick = currentTick;

        // Get all entities
        for (const [key, entity] of this.game.entityData) {
            // Skip if not built
            if (entity.state !== 'built') {
                continue;
            }

            // Calculate total shake force for this entity
            const shakeForce = this.calculateEntityShakeForce(entity);

            if (shakeForce <= 0) {
                continue;
            }

            // Check if protected by stabilizer
            if (this.isProtectedByStabilizer(entity)) {
                continue;
            }

            // Apply damage: 0.5 damage per 3 sec at 1.0 intensity
            const damage = shakeForce * 0.5;
            const entityType = this.game.entityTypes[entity.entity_type_id];
            const maxDurability = entityType?.max_durability || 100;

            // Initialize durability if not set
            if (typeof entity.durability !== 'number') {
                entity.durability = maxDurability;
            }

            entity.durability -= damage;

            if (entity.durability < 0) {
                entity.durability = 0;
            }

            // Update sprite if durability reached 0
            if (entity.durability === 0) {
                const sprite = this.game.loadedEntities.get(key);
                if (sprite) {
                    const textureKey = this.game.getEntityTextureKey(entity, false);
                    const texture = this.game.textures[textureKey];
                    if (texture) {
                        sprite.texture = texture;
                    }
                }
            }
        }
    }

    /**
     * Calculate total shake force for entity (sum of all tiles under it)
     */
    calculateEntityShakeForce(entity) {
        const entityType = this.game.entityTypes[entity.entity_type_id];
        if (!entityType) {
            return 0;
        }

        let totalShake = 0;

        // Get sum of shake from all tiles under entity footprint
        for (let dy = 0; dy < entityType.height; dy++) {
            for (let dx = 0; dx < entityType.width; dx++) {
                const key = `${entity.x + dx}_${entity.y + dy}`;
                const intensity = this.shakeZones.get(key) || 0;
                totalShake += intensity;
            }
        }

        return totalShake;
    }

    /**
     * Check if entity is protected by any active stabilizer
     * TODO: Implement stabilizer logic when stabilizer buildings are added
     */
    isProtectedByStabilizer(entity) {
        // For now, no protection
        return false;
    }

    /**
     * Cleanup sprites outside viewport
     * @param {Set} validKeys - Set of keys that should be kept
     */
    cleanupSprites(validKeys) {
        for (const [key, sprite] of this.shakeLandingSprites) {
            if (!validKeys.has(key)) {
                this.shakeLayer.removeChild(sprite);
                sprite.destroy();
                this.shakeLandingSprites.delete(key);
            }
        }
    }

    /**
     * Cleanup on destroy
     */
    destroy() {
        // Destroy all sprites
        for (const [key, sprite] of this.shakeLandingSprites) {
            sprite.destroy();
        }
        this.shakeLandingSprites.clear();

        // Destroy layer
        if (this.shakeLayer) {
            this.shakeLayer.destroy({ children: true });
            this.shakeLayer = null;
        }

        this.shakeZones.clear();
    }
}
