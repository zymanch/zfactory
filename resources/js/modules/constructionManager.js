import { getCSRFToken } from './utils.js';

/**
 * ConstructionManager - handles entity construction progress
 * Increases progress over time and finalizes construction when complete
 */
export class ConstructionManager {
    constructor(game) {
        this.game = game;
        this.lastUpdateTime = performance.now();
        this.finishingEntities = new Set(); // Track entities being finalized
    }

    /**
     * Update construction progress for all blueprint entities
     * Called every frame from game loop
     */
    update() {
        const now = performance.now();
        const deltaMs = now - this.lastUpdateTime;
        this.lastUpdateTime = now;

        // Convert to seconds
        const deltaSec = deltaMs / 1000;

        // 60 ticks per second
        const ticksPerSecond = 60;
        const deltaTicks = deltaSec * ticksPerSecond;

        // Update all blueprint entities
        for (const [key, entityData] of this.game.entityData.entries()) {
            if (entityData.state !== 'blueprint') continue;

            // Skip if already being finalized
            if (this.finishingEntities.has(entityData.entity_id)) continue;

            const entityType = this.game.entityTypes[entityData.entity_type_id];
            if (!entityType || !entityType.construction_ticks) continue;

            // Calculate progress increment
            // construction_ticks = total ticks needed for 100%
            const progressPerTick = 100 / entityType.construction_ticks;
            const progressIncrement = progressPerTick * deltaTicks;

            // Update progress
            entityData.construction_progress = (entityData.construction_progress || 0) + progressIncrement;

            // Cap at 100%
            if (entityData.construction_progress >= 100) {
                entityData.construction_progress = 100;
                this.finishingEntities.add(entityData.entity_id);
                this.finishConstruction(entityData.entity_id);
            } else {
                // Update sprite for current progress
                this.updateEntitySprite(entityData);
            }
        }
    }

    /**
     * Update entity sprite based on construction progress
     */
    updateEntitySprite(entityData) {
        const key = `entity_${entityData.entity_id}`;
        const sprite = this.game.loadedEntities.get(key);
        if (!sprite) return;

        const progress = entityData.construction_progress || 0;

        // Calculate which frame to show (10, 20, 30, ..., 90)
        // 0-9% -> 10, 10-19% -> 10, 20-29% -> 20, etc.
        const frameProgress = Math.ceil(progress / 10) * 10;
        const clampedProgress = Math.max(10, Math.min(90, frameProgress));

        const textureKey = `entity_${entityData.entity_type_id}_construction_${clampedProgress}`;
        const texture = this.game.textures[textureKey];

        if (texture) {
            sprite.texture = texture;
        }
    }

    /**
     * Finish construction - send AJAX request to finalize
     */
    async finishConstruction(entityId) {
        const url = this.game.config.finishConstructionUrl;
        if (!url) return;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCSRFToken()
                },
                body: JSON.stringify({ entity_id: entityId })
            });

            // Check if response is ok before parsing
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.result === 'ok') {
                // Remove from finishing set
                this.finishingEntities.delete(entityId);

                // Check if entity was converted to landing (ship floors)
                if (data.converted) {
                    // Entity converted to landing - remove entity sprite and data
                    let tileX, tileY;

                    for (const [key, entityData] of this.game.entityData.entries()) {
                        if (entityData.entity_id === entityId) {
                            // Both ship and island entities already have world coordinates from Entities.php
                            tileX = parseInt(entityData.x);
                            tileY = parseInt(entityData.y);

                            // Remove sprite
                            const sprite = this.game.loadedEntities.get(key);
                            if (sprite) {
                                sprite.destroy();
                                this.game.loadedEntities.delete(key);
                            }

                            // Remove from entity data
                            this.game.entityData.delete(key);
                            break;
                        }
                    }

                    // Add new landing tile to tile data map
                    if (data.converted_to_landing_id && tileX !== undefined && tileY !== undefined) {
                        const landingId = data.converted_to_landing_id;
                        const key = `${tileX}_${tileY}`;

                        console.log(`Creating landing tile: landing_id=${landingId}, world coords=(${tileX}, ${tileY})`);

                        // Add to tile data map
                        this.game.tileDataMap.set(key, landingId);

                        // Create tile sprite with transitions (uses atlas)
                        if (this.game.tileManager) {
                            const sprite = this.game.tileManager.createTileWithTransitions(
                                landingId,
                                tileX,
                                tileY
                            );

                            if (sprite) {
                                this.game.landingLayer.addChild(sprite);
                                this.game.landingLayer.sortChildren(); // Sort by z-index
                                this.game.tileManager.loadedTiles.set(key, sprite);
                                console.log(`✓ Landing tile sprite created and added to layer at z-index ${sprite.zIndex}`);
                                console.log(`Sprite position: (${sprite.x}, ${sprite.y}), size: ${sprite.width}x${sprite.height}`);

                                // Refresh adjacent tiles to update their transitions
                                this.game.tileManager.refreshAdjacentTiles(tileX, tileY);
                            } else {
                                console.error(`✗ Failed to create landing tile sprite (atlas missing?)`);
                            }
                        } else {
                            console.error(`✗ tileManager not available`);
                        }
                    } else {
                        console.error(`✗ Missing data for landing creation:`, {
                            converted_to_landing_id: data.converted_to_landing_id,
                            tileX,
                            tileY
                        });
                    }

                    console.log(`Entity ${entityId} converted to landing at world (${tileX}, ${tileY})`);
                } else {
                    // Normal construction finish - update entity to built state
                    for (const [key, entityData] of this.game.entityData.entries()) {
                        if (entityData.entity_id === entityId) {
                            entityData.state = 'built';
                            entityData.construction_progress = 100;
                            entityData.durability = data.durability;

                            // Update sprite to normal (key is already entity_${entity_id})
                            const sprite = this.game.loadedEntities.get(key);
                            if (sprite) {
                                const textureKey = `entity_${entityData.entity_type_id}_normal`;
                                const texture = this.game.textures[textureKey];
                                if (texture) {
                                    sprite.texture = texture;
                                }

                                // Enable interactivity - attach event handlers
                                sprite.eventMode = 'static';
                                sprite.cursor = 'pointer';

                                // Attach event handlers (same as in createEntitySprite)
                                sprite.on('pointerover', (e) => this.game.onEntityHover(sprite, true, e));
                                sprite.on('pointerout', (e) => this.game.onEntityHover(sprite, false, e));
                                sprite.on('pointermove', (e) => this.game.onEntityMove(e));
                                sprite.on('click', (e) => this.game.onEntityClick(sprite, e));
                            }

                            break;
                        }
                    }

                    console.log(`Construction finished for entity ${entityId}`);
                }
            } else {
                console.error('Failed to finish construction:', data.error);
                // Remove from finishing set on error
                this.finishingEntities.delete(entityId);
            }
        } catch (e) {
            console.error('Failed to finish construction:', e);
            // Remove from finishing set on error
            this.finishingEntities.delete(entityId);
        }
    }
}

export default ConstructionManager;
