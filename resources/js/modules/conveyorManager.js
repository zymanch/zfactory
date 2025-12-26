import * as PIXI from 'pixi.js';

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

        // Atlas storage: atlases[orientation][state] = texture
        this.atlases = {
            'conveyor': {},
            'conveyor_up': {},
            'conveyor_down': {},
            'conveyor_left': {}
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
    }

    /**
     * Load all conveyor atlases (20 total: 5 states × 4 orientations)
     */
    async loadAtlases() {
        const orientations = ['conveyor', 'conveyor_up', 'conveyor_down', 'conveyor_left'];
        const states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];

        console.log('Loading conveyor atlases...');

        for (const orientation of orientations) {
            this.atlases[orientation] = {};

            for (const state of states) {
                const url = this.game.assetUrl(
                    `${this.game.config.tilesPath}entities/${orientation}/${state}_atlas.png`
                );

                try {
                    this.atlases[orientation][state] = await PIXI.Assets.load(url);
                    console.log(`Loaded: ${orientation}/${state}_atlas.png`);
                } catch (e) {
                    console.error(`Failed to load conveyor atlas: ${url}`, e);
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
    }

    /**
     * Unregister a conveyor sprite (when removed)
     */
    unregisterConveyor(entityId) {
        this.conveyorSprites.delete(entityId);
    }

    /**
     * Get connection variant (0-15) based on neighboring conveyors
     * Bit mask: [DOWN][UP][RIGHT][LEFT]
     */
    getConnectionVariant(entity) {
        const neighbors = this.getNeighbors(entity);
        const entityType = this.game.entityTypes[entity.entity_type_id];
        const currentOrientation = entityType.image_url;
        let variant = 0;

        // Устанавливаем бит если:
        // 1. Сосед входящий (движется к нам) ИЛИ
        // 2. Мы исходящие (движемся к соседу)
        if (this.isIncomingConveyor(neighbors.left, 'left') || this.isOutgoingToNeighbor(currentOrientation, 'left')) variant |= 1;  // Bit 0
        if (this.isIncomingConveyor(neighbors.right, 'right') || this.isOutgoingToNeighbor(currentOrientation, 'right')) variant |= 2; // Bit 1
        if (this.isIncomingConveyor(neighbors.up, 'up') || this.isOutgoingToNeighbor(currentOrientation, 'up')) variant |= 4;    // Bit 2
        if (this.isIncomingConveyor(neighbors.down, 'down') || this.isOutgoingToNeighbor(currentOrientation, 'down')) variant |= 8;  // Bit 3

        return variant;
    }

    /**
     * Get neighboring entities at 4 cardinal directions
     */
    getNeighbors(entity) {
        const x = parseInt(entity.x);
        const y = parseInt(entity.y);

        return {
            left: this.getEntityAt(x - 1, y),
            right: this.getEntityAt(x + 1, y),
            up: this.getEntityAt(x, y - 1),
            down: this.getEntityAt(x, y + 1)
        };
    }

    /**
     * Get entity at specific tile coordinates
     */
    getEntityAt(x, y) {
        for (const [key, entity] of this.game.entityData) {
            if (parseInt(entity.x) === x && parseInt(entity.y) === y) {
                return entity;
            }
        }
        return null;
    }

    /**
     * Check if entity is a conveyor of any orientation
     */
    isConveyor(entity) {
        if (!entity) return false;

        const entityType = this.game.entityTypes[entity.entity_type_id];
        if (!entityType) return false;

        return entityType.type === 'transporter';
    }

    /**
     * Check if neighboring conveyor is incoming (moving towards current conveyor)
     * @param {Object} neighbor - соседний entity
     * @param {string} direction - направление относительно текущего ('left', 'right', 'up', 'down')
     * @returns {boolean}
     */
    isIncomingConveyor(neighbor, direction) {
        if (!this.isConveyor(neighbor)) return false;

        const entityType = this.game.entityTypes[neighbor.entity_type_id];
        const orientation = entityType.image_url; // 'conveyor', 'conveyor_up', etc.

        // Конвейер входящий если его направление противоположно его положению
        const incomingMap = {
            'left': 'conveyor',        // слева -> должен двигать вправо (RIGHT)
            'right': 'conveyor_left',  // справа -> должен двигать влево (LEFT)
            'up': 'conveyor_down',     // сверху -> должен двигать вниз (DOWN)
            'down': 'conveyor_up'      // снизу -> должен двигать вверх (UP)
        };

        return orientation === incomingMap[direction];
    }

    /**
     * Check if current conveyor is outgoing to neighbor (moving towards neighbor)
     * @param {string} currentOrientation - текущая ориентация ('conveyor', 'conveyor_up', etc.)
     * @param {string} neighborDirection - направление соседа ('left', 'right', 'up', 'down')
     * @returns {boolean}
     */
    isOutgoingToNeighbor(currentOrientation, neighborDirection) {
        // Проверяем движемся ли мы в сторону соседа
        const outgoingMap = {
            'left': 'conveyor_left',   // движемся влево
            'right': 'conveyor',       // движемся вправо
            'up': 'conveyor_up',       // движемся вверх
            'down': 'conveyor_down'    // движемся вниз
        };

        return currentOrientation === outgoingMap[neighborDirection];
    }

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
        const orientation = entityType.image_url; // 'conveyor', 'conveyor_up', etc.
        const baseState = this.getEntityState(entity);
        const state = isHovered ? `${baseState}_selected` : baseState;
        const variant = this.getConnectionVariant(entity);
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

        return new PIXI.Texture({
            source: atlas.source,
            frame: new PIXI.Rectangle(x, y, this.TILE_WIDTH, this.TILE_HEIGHT)
        });
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

        const texture = this.getConveyorTexture(entity, isHovered, this.currentFrame);
        if (texture) {
            sprite.texture = texture;
        }
    }
}
