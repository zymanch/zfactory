import * as PIXI from 'pixi.js';
import { Z_INDEX } from '../constants.js';

/**
 * ManipulatorLayerManager - manages manipulator sprite rendering
 * Handles animated sprites with overflow areas
 */
export class ManipulatorLayerManager {
    constructor(game) {
        this.game = game;
        this.atlases = new Map(); // entityTypeId → {frames: [textures]}
        this.debugTexts = new Map(); // entityId → PIXI.Text (debug labels)
    }

    /**
     * Initialize - load atlases only (no separate layer)
     */
    async init() {
        // Load atlases for all manipulator types
        await this.loadAtlases();

        console.log(`[ManipulatorLayerManager] Initialized with ${this.atlases.size} atlases`);
    }

    /**
     * Load animation atlases for all manipulator types
     */
    async loadAtlases() {
        const entityTypes = this.game.entityTypes;

        for (const [entityTypeId, entityType] of Object.entries(entityTypes)) {
            if (entityType.type === 'manipulator') {
                const textureKey = `manipulator_animation_${entityTypeId}`;
                const atlas = this.game.graphics.getTexture(textureKey);

                if (atlas) {
                    // Detect atlas format (new: 6 rows, legacy: 1 row)
                    const expectedHeight = entityType.total_height * 64 * 6; // 6 rows
                    const isNewFormat = atlas.height === expectedHeight;

                    if (isNewFormat) {
                        // New format: 6 rows, construction frames in row 5 horizontally
                        const stateFrames = {
                            normal: this.extractFrames(atlas, entityType, 0),
                            damaged: this.extractFrames(atlas, entityType, 1),
                            blueprint: this.extractFrames(atlas, entityType, 2),
                            normal_selected: this.extractFrames(atlas, entityType, 3),
                            damaged_selected: this.extractFrames(atlas, entityType, 4),
                            construction: []
                        };

                        // Load construction frames (row 5, 9 frames horizontally)
                        const frameWidth = entityType.total_width * 64;
                        const frameHeight = entityType.total_height * 64;

                        for (let i = 0; i < 9; i++) {
                            const x = i * frameWidth; // Horizontal in row 5
                            const y = 5 * frameHeight; // Row 5

                            const rect = new PIXI.Rectangle(
                                x,
                                y,
                                frameWidth,
                                frameHeight
                            );
                            stateFrames.construction[i] = new PIXI.Texture({
                                source: atlas.source,
                                frame: rect
                            });
                        }

                        this.atlases.set(parseInt(entityTypeId), { stateFrames, isNewFormat: true });
                        console.log(`[ManipulatorLayerManager] Loaded atlas for ${entityType.name}: NEW format (6 rows, row 5 construction)`);
                    } else {
                        // Legacy format: 1 row (backward compatibility)
                        const frames = this.extractFrames(atlas, entityType, 0);
                        this.atlases.set(parseInt(entityTypeId), { frames, isNewFormat: false });
                        console.warn(`[ManipulatorLayerManager] Legacy format for ${entityType.name} - please regenerate atlas`);
                    }
                } else {
                    console.warn(`[ManipulatorLayerManager] Atlas not found for ${entityType.name} (key: ${textureKey})`);
                }
            }
        }
    }

    /**
     * Extract individual frames from animation atlas
     * @param {PIXI.Texture} atlas - Atlas texture
     * @param {object} entityType - Entity type data
     * @param {number} row - Row index in atlas (default: 0)
     * @returns {Array<PIXI.Texture>}
     */
    extractFrames(atlas, entityType, row = 0) {
        const frameCount = entityType.frame_count;
        const frameWidth = entityType.total_width * 64;
        const frameHeight = entityType.total_height * 64;

        const frames = [];
        for (let i = 0; i < frameCount; i++) {
            const x = i * frameWidth;
            const y = row * frameHeight; // NEW: row offset

            // Create PIXI.Rectangle directly
            const rect = new PIXI.Rectangle(x, y, frameWidth, frameHeight);

            // Create texture using PixiJS v8 syntax
            // Clone the atlas and set the frame region
            const frameTexture = new PIXI.Texture({
                source: atlas.source,
                frame: rect
            });

            frames.push(frameTexture);
        }

        return frames;
    }

    /**
     * Get frame texture for manipulator
     * @param {number} entityTypeId - Entity type ID
     * @param {number} frameIndex - Frame index (0 to frameCount-1)
     * @param {string} state - State name (default: 'normal')
     * @returns {PIXI.Texture|null}
     */
    getFrameTexture(entityTypeId, frameIndex, state = 'normal') {
        const atlasData = this.atlases.get(entityTypeId);
        if (!atlasData) {
            return null;
        }

        let frames;
        if (atlasData.isNewFormat) {
            // NEW: For construction state during blueprint, use construction frames
            // Construction frames are single-frame (no animation), so ignore frameIndex
            if (state === 'construction') {
                frames = atlasData.stateFrames.construction;
                if (!frames || frames.length === 0) {
                    console.warn(`[ManipulatorLayerManager] Construction frames not found for entity type ${entityTypeId}`);
                    return null;
                }
                // frameIndex is progress index (0-8), not animation frame
                const clampedIndex = Math.max(0, Math.min(frames.length - 1, frameIndex));
                return frames[clampedIndex];
            }

            // Normal state frames
            frames = atlasData.stateFrames[state];
            if (!frames) {
                console.warn(`[ManipulatorLayerManager] State '${state}' not found for entity type ${entityTypeId}`);
                return null;
            }
        } else {
            // Legacy format: single frames array
            frames = atlasData.frames;
            if (!frames) {
                return null;
            }
        }

        // Clamp frame index
        const maxFrame = frames.length - 1;
        const clampedIndex = Math.max(0, Math.min(maxFrame, frameIndex));

        return frames[clampedIndex];
    }

    /**
     * Get center frame texture for manipulator (for initial display)
     * @param {number} entityTypeId - Entity type ID
     * @returns {PIXI.Texture|null}
     */
    getCenterFrameTexture(entityTypeId) {
        const entityType = this.game.entityTypes[entityTypeId];
        if (!entityType) return null;

        const centerFrame = entityType.center_frame_index || 0;
        return this.getFrameTexture(entityTypeId, centerFrame, 'normal');
    }

    /**
     * Update animation frames for all manipulators
     * Updates textures of sprites in game.loadedEntities
     */
    render() {
        // Check if resourceTransport is initialized
        if (!this.game.resourceTransport || !this.game.resourceTransport.manipulators) {
            return;
        }

        // For each manipulator, update frame based on holder position
        for (const [entityId, state] of this.game.resourceTransport.manipulators) {
            // Get sprite from loadedEntities (not from this.sprites!)
            const key = `entity_${entityId}`;
            const sprite = this.game.loadedEntities.get(key);
            if (!sprite) {
                if (this.game.gameTick % 60 === 0) {
                    console.warn(`[ManipulatorLayerManager] Sprite not found for entity ${entityId}`);
                }
                continue;
            }

            // Check if entityTypeId exists
            if (!state.entityTypeId) {
                if (this.game.gameTick % 60 === 0) {
                    console.warn(`[ManipulatorLayerManager] entityTypeId missing for entity ${entityId}`);
                }
                continue;
            }

            // Get entity data (key is "entity_X", not just X)
            const entity = this.game.entityData.get(key);
            if (!entity) {
                continue;
            }

            // Get atlas data
            const atlasData = this.atlases.get(state.entityTypeId);
            if (!atlasData) {
                continue;
            }

            let frameTexture;

            if (atlasData.isNewFormat) {
                // New format: choose row based on state
                const row = this.getRowForState(entity, state);

                if (row === 5) {
                    // Construction frames (row 5): map progress to 9 frames
                    const progress = parseInt(entity.construction_progress) || 0;
                    const frameProgress = Math.ceil(progress / 10) * 10; // Round to 10, 20, 30...
                    const clampedProgress = Math.max(10, Math.min(90, frameProgress));
                    const constructionIndex = (clampedProgress / 10) - 1; // 0-8

                    frameTexture = atlasData.stateFrames.construction[constructionIndex];
                } else {
                    // State rows (0-4): animated frames
                    const stateNames = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];
                    const stateName = stateNames[row];
                    const frames = atlasData.stateFrames[stateName];

                    if (frames && frames.length > 0) {
                        // Calculate frame index from holder position
                        let frameIndex = this.getFrameIndex(state);

                        // Clamp to valid range (safety check)
                        frameIndex = Math.max(0, Math.min(frames.length - 1, frameIndex));

                        frameTexture = frames[frameIndex];
                    } else {
                        console.warn(`[ManipulatorLayerManager] No frames for state '${stateName}', entity ${entityId}`);
                    }
                }
            } else {
                // Legacy format: single row
                const frameIndex = this.getFrameIndex(state);
                frameTexture = atlasData.frames[frameIndex];
            }

            if (frameTexture) {
                // Only update texture if it changed (avoid unnecessary updates)
                if (sprite.texture !== frameTexture) {
                    sprite.texture = frameTexture;
                }
            }

            // Debug: Show holder position as text above manipulator
            const position_px = state.getHolderPositionPx();
            const frameIndex = this.getFrameIndex(state);
            this.updateDebugText(entityId, state, sprite, position_px, frameIndex);
        }
    }

    /**
     * Update debug text showing holder position above manipulator
     */
    updateDebugText(entityId, state, sprite, position_px, frameIndex) {
        let debugText = this.debugTexts.get(entityId);

        // Create debug text if it doesn't exist
        if (!debugText) {
            debugText = new PIXI.Text({
                text: '',
                style: {
                    fontFamily: 'Arial',
                    fontSize: 12,
                    fill: 0xffffff,
                    stroke: 0x000000,
                    strokeThickness: 2
                }
            });
            debugText.anchor.set(0.5, 1); // Center horizontally, bottom anchor
            this.game.entityLayer.addChild(debugText);
            this.debugTexts.set(entityId, debugText);
        }

        // Update text content with resource indicator
        const hasResource = state.hasResource() ? '+' : '-';
        const statusShort = state.status.substring(0, 4); // First 4 chars of status
        debugText.text = `${hasResource} pos:${Math.round(position_px)} f:${frameIndex} t:${Math.round(state.ticks)} [${statusShort}]`;

        // Position above sprite
        const entityType = this.game.entityTypes[state.entityTypeId];
        const heightOffset = entityType.total_height * 64;
        debugText.x = sprite.x + (entityType.total_width * 64) / 2;
        debugText.y = sprite.y - 5; // 5px above sprite
        debugText.zIndex = sprite.zIndex + 1000; // Always on top
    }

    /**
     * Determine which row to use based on entity state
     * @param {object} entity - Entity data from game
     * @param {object} state - ManipulatorState object
     * @returns {number} Row index (0-5)
     */
    getRowForState(entity, state) {
        const entityType = this.game.entityTypes[state.entityTypeId];
        if (!entityType) {
            return 0;
        }

        // Construction progress
        if (entity.state === 'blueprint') {
            const progress = parseInt(entity.construction_progress) || 0;

            if (progress < 100) {
                // Row 5: construction frames (0-9% → 10%, 10-19% → 10%, 20-29% → 20%, etc.)
                return 5;
            } else {
                // Completed construction - row 2 (blueprint)
                return 2;
            }
        }

        // Normal gameplay states
        const durability = parseInt(entity.durability) || entityType.max_durability;
        const maxDurability = parseInt(entityType.max_durability) || 100;
        const isDamaged = durability < (maxDurability * 0.5);

        // Check if selected (from game selection manager)
        const isSelected = this.game.selectedEntityId === entity.entity_id;

        if (isSelected) {
            return isDamaged ? 4 : 3; // damaged_selected or normal_selected
        }

        return isDamaged ? 1 : 0; // damaged or normal
    }

    /**
     * Calculate frame index from manipulator state
     */
    getFrameIndex(state) {
        // Check if entityTypeId exists
        if (!state.entityTypeId) {
            return 0;
        }

        const entityType = this.game.entityTypes[state.entityTypeId];
        if (!entityType || !entityType.frame_count) {
            return 0;
        }

        // Get holder position in pixels (range: -maxDistance to +maxDistance)
        const position_px = state.getHolderPositionPx();

        // Calculate maxDistance (same as in ManipulatorState.getHolderPositionPx)
        const orientation = state.orientation;
        const widthOverflow = state.widthOverflow || 0;
        const heightOverflow = state.heightOverflow || 0;
        const maxDistance = (['right', 'left'].includes(orientation)
            ? widthOverflow
            : heightOverflow) / 2 * 64;

        // Normalize position_px from [-maxDistance, +maxDistance] to [0, 1]
        const progress = (position_px + maxDistance) / (2 * maxDistance);

        // Convert to frame index [0, frameCount-1]
        const frameIndex = Math.round(progress * (entityType.frame_count - 1));

        // NO INVERSION - frame index matches holder position directly
        // Left orientation: holder moves left (negative position_px) = low frame index
        // Right orientation: holder moves right (positive position_px) = high frame index

        // Clamp to valid range
        return Math.max(0, Math.min(entityType.frame_count - 1, frameIndex));
    }

    /**
     * Remove debug text for a manipulator (called when manipulator is deleted)
     */
    removeDebugText(entityId) {
        const debugText = this.debugTexts.get(entityId);
        if (debugText) {
            this.game.entityLayer.removeChild(debugText);
            debugText.destroy();
            this.debugTexts.delete(entityId);
        }
    }

}
