import { ConveyorVariantManager } from './ConveyorVariantManager.js';

/**
 * ConveyorManager - Handles conveyor belt animations and connections
 *
 * Features:
 * - Animated conveyors (8 frames, 100ms per frame)
 * - 16 connection variants based on 4-bit mask
 * - Auto-updates connections when conveyors are placed/removed
 * - Texture atlases: 512×192px (16 variants × 8 frames)
 */
export class ConveyorManager {
    constructor(game) {
        this.game = game;
        this.variantManager = new ConveyorVariantManager(game);

        // Variant cache: variantCache[entityId] = variant (0-15)
        // Cached to avoid recalculating on every animation frame
        this.variantCache = new Map();

        // Atlas storage: atlases[orientation][state] = texture
        this.atlases = {
            'conveyor': {},
            'conveyor_up': {},
            'conveyor_down': {},
            'conveyor_left': {},
            // Underground belts (animated)
            'underground_belt_in': {},
            'underground_belt_in_down': {},
            'underground_belt_in_left': {},
            'underground_belt_in_up': {},
            'underground_belt_out': {},
            'underground_belt_out_down': {},
            'underground_belt_out_left': {},
            'underground_belt_out_up': {}
        };

        // Conveyor sprite registry: conveyorSprites[entityId] = sprite
        this.conveyorSprites = new Map();

        // Animation state
        this.frameCount = 0;
        this.currentFrame = 0;
        this.lastFrameTime = 0;

        // Constants
        this.TILE_WIDTH = 64;
        this.TILE_HEIGHT = 64;
        this.ANIMATION_FRAMES = 8;
        this.FRAME_DURATION = 100; // milliseconds
        this.FRAME_RATE = 8; // game ticks per frame update

        // Map internal orientation names to manifest keys
        this.orientationMapping = {
            'conveyor': 'right',
            'conveyor_up': 'up',
            'conveyor_down': 'down',
            'conveyor_left': 'left',
            // Underground belts map to themselves (folder name = manifest key)
            'underground_belt_in': 'underground_belt_in',
            'underground_belt_in_down': 'underground_belt_in_down',
            'underground_belt_in_left': 'underground_belt_in_left',
            'underground_belt_in_up': 'underground_belt_in_up',
            'underground_belt_out': 'underground_belt_out',
            'underground_belt_out_down': 'underground_belt_out_down',
            'underground_belt_out_left': 'underground_belt_out_left',
            'underground_belt_out_up': 'underground_belt_out_up'
        };
    }

    /**
     * Load all conveyor atlases (60 total: 5 states × 12 orientations)
     */
    async loadAtlases() {
        const orientations = [
            'conveyor', 'conveyor_up', 'conveyor_down', 'conveyor_left',
            'underground_belt_in', 'underground_belt_in_down', 'underground_belt_in_left', 'underground_belt_in_up',
            'underground_belt_out', 'underground_belt_out_down', 'underground_belt_out_left', 'underground_belt_out_up'
        ];
        const states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];

        console.log('Loading conveyor atlases...');

        for (const orientation of orientations) {
            this.atlases[orientation] = {};

            for (const state of states) {
                // Map internal orientation to manifest key
                const manifestOrientation = this.orientationMapping[orientation];
                const textureKey = `conveyor_${state}_${manifestOrientation}`;

                const texture = this.game.graphics.getTexture(textureKey);
                if (texture) {
                    this.atlases[orientation][state] = texture;
                    console.log(`Loaded: ${textureKey}`);
                } else {
                    console.warn(`Texture not found: ${textureKey}`);
                }
            }
        }

        console.log('All conveyor atlases loaded.');
    }

    /**
     * Register a conveyor sprite for animation and connection management
     */
    registerConveyor(entityId, sprite) {
        this.conveyorSprites.set(entityId, sprite);

        // Add to spatial index for fast neighbor lookup
        const key = `entity_${entityId}`;
        const entity = this.game.entityData.get(key);
        if (entity) {
            this.variantManager.addToIndex(entity);

            // NOTE: Variant calculation deferred until updateAllConnections()
            // This ensures all neighbors are in spatial index first
        }
    }

    /**
     * Unregister a conveyor sprite (when removed)
     */
    unregisterConveyor(entityId) {
        // Remove from spatial index
        const key = `entity_${entityId}`;
        const entity = this.game.entityData.get(key);
        if (entity) {
            this.variantManager.removeFromIndex(entity);
        }

        this.conveyorSprites.delete(entityId);
        this.variantCache.delete(entityId);
    }

    /**
     * Get connection variant (0-15) based on neighboring conveyors
     * Bit mask: [DOWN][UP][RIGHT][LEFT]
     * Delegates to ConveyorVariantManager for isolated, testable logic
     */
    getConnectionVariant(entity) {
        return this.variantManager.calculateVariant(entity);
    }

    /**
     * Get neighboring entities at 4 cardinal directions
     * NOTE: Kept for backward compatibility, but variantManager has its own implementation
     */
    getNeighbors(entity) {
        return this.variantManager.getNeighbors(entity);
    }

    /**
     * Get entity at specific tile coordinates
     * NOTE: Kept for backward compatibility, but variantManager has its own implementation
     */
    getEntityAt(x, y) {
        return this.variantManager.getConveyorAt(x, y);
    }

    /**
     * Check if entity is a conveyor (for spatial index and variant calculation)
     * NOTE: Animation support checked separately - some conveyors use fallback textures
     */
    isConveyor(entity) {
        if (!entity) return false;

        const entityType = this.game.entityTypes[entity.entity_type_id];
        return entityType && entityType.type === 'conveyor';
    }

    /**
     * Check if entity has animation atlas support
     */
    hasAnimationAtlas(entity) {
        if (!entity) return false;

        const entityType = this.game.entityTypes[entity.entity_type_id];
        if (!entityType || entityType.type !== 'conveyor') return false;

        const folder = entityType.folder;
        return folder && this.atlases.hasOwnProperty(folder);
    }

    // REMOVED: isIncomingConveyor, isOutgoingToNeighbor methods
    // These have been moved to ConveyorVariantManager for isolated, testable logic

    /**
     * Get current entity state based on durability
     */
    getEntityState(entity) {
        if (entity.state === 'blueprint') {
            return 'blueprint';
        }

        const entityType = this.game.entityTypes[entity.entity_type_id];
        const maxDurability = entityType.max_durability || 100;
        const durability = entity.durability || maxDurability;
        const isDamaged = durability < (maxDurability * 0.5);

        return isDamaged ? 'damaged' : 'normal';
    }

    /**
     * Get texture from atlas for specific conveyor state, variant, and frame
     */
    getConveyorTexture(entity, isHovered, currentFrame) {
        const entityType = this.game.entityTypes[entity.entity_type_id];
        const orientation = entityType.folder; // 'conveyor', 'conveyor_up', etc.
        const baseState = this.getEntityState(entity);
        const state = isHovered ? `${baseState}_selected` : baseState;

        // Use cached variant instead of recalculating on every frame
        const variant = this.variantCache.get(entity.entity_id) || 0;
        const frameIndex = currentFrame % this.ANIMATION_FRAMES;

        const atlas = this.atlases[orientation]?.[state];
        if (!atlas) {
            console.warn(`Atlas not found: ${orientation}/${state}`);
            return null;
        }

        // Calculate coordinates in atlas
        // Atlas: 1024×512px = 16 variants (X) × 8 frames (Y)
        // Variant уже правильный - PHP повернул биты при генерации спрайтов
        const x = variant * this.TILE_WIDTH;
        const y = frameIndex * this.TILE_HEIGHT;

        // Map internal orientation to manifest key for createTextureFromAtlas
        const manifestOrientation = this.orientationMapping[orientation];
        const atlasKey = `conveyor_${state}_${manifestOrientation}`;

        return this.game.graphics.createTextureFromAtlas(
            atlasKey,
            this.game.graphics.createRectangle(x, y, this.TILE_WIDTH, this.TILE_HEIGHT)
        );
    }

    /**
     * Update all conveyor animations (called every game tick)
     */
    update() {
        const now = performance.now();

        // Update frame based on time
        if (now - this.lastFrameTime >= this.FRAME_DURATION) {
            this.currentFrame = (this.currentFrame + 1) % this.ANIMATION_FRAMES;
            this.lastFrameTime = now;

            // Update all conveyor textures
            this.updateAllConveyorTextures();
        }
    }

    /**
     * Update textures for all conveyor sprites
     */
    updateAllConveyorTextures() {
        for (const [entityId, sprite] of this.conveyorSprites) {
            const key = `entity_${entityId}`;
            const entity = this.game.entityData.get(key);

            if (!entity) continue;

            const isHovered = this.game.hoveredEntity === key;
            const texture = this.getConveyorTexture(entity, isHovered, this.currentFrame);

            if (texture) {
                sprite.texture = texture;
            }
        }
    }

    /**
     * Update connections for all conveyors
     * Called when a conveyor is placed or removed
     */
    updateAllConnections() {
        // Recalculate and cache variants for all conveyors
        for (const [entityId, sprite] of this.conveyorSprites) {
            const key = `entity_${entityId}`;
            const entity = this.game.entityData.get(key);

            if (entity) {
                const variant = this.variantManager.calculateVariant(entity);
                this.variantCache.set(entityId, variant);
            }
        }

        // Update textures with new variants
        this.updateAllConveyorTextures();
    }

    /**
     * Update single conveyor texture (for hover/unhover)
     */
    updateConveyorTexture(entityId, isHovered) {
        const sprite = this.conveyorSprites.get(entityId);
        if (!sprite) return;

        const key = `entity_${entityId}`;
        const entity = this.game.entityData.get(key);
        if (!entity) return;

        // Ensure variant is cached for this entity
        if (!this.variantCache.has(entityId)) {
            const variant = this.variantManager.calculateVariant(entity);
            this.variantCache.set(entityId, variant);
        }

        const texture = this.getConveyorTexture(entity, isHovered, this.currentFrame);
        if (texture) {
            sprite.texture = texture;
        }
    }
}
