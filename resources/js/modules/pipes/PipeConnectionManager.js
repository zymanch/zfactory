import * as PIXI from 'pixi.js';

/**
 * PipeConnectionManager - Handles pipe connections and variants
 *
 * Features:
 * - 16 connection variants based on 4-bit mask (right, down, left, up)
 * - Auto-updates connections when pipes are placed/removed
 * - No animations (pipes are static, unlike conveyors)
 */
export class PipeConnectionManager {
    constructor(game) {
        this.game = game;

        // Pipe entity type IDs (horizontal, vertical, tanks, underground)
        this.PIPE_TYPES = [131, 132, 135, 136, 140, 141];

        // Variant names (0-15) corresponding to binary masks
        this.VARIANT_NAMES = [
            'none',         // 0000: no connections
            'right',        // 0001: right only
            'down',         // 0010: down only
            'right_down',   // 0011: corner
            'left',         // 0100: left only
            'horizontal',   // 0101: straight horizontal
            'left_down',    // 0110: corner
            't_down',       // 0111: T bottom
            'up',           // 1000: up only
            'up_right',     // 1001: corner
            'vertical',     // 1010: straight vertical
            't_right',      // 1011: T right
            'up_left',      // 1100: corner
            't_up',         // 1101: T top
            't_left',       // 1110: T left
            'cross'         // 1111: cross (all 4)
        ];

        // Pipe sprite registry: pipeSprites[entityId] = sprite
        this.pipeSprites = new Map();

        // Variant textures cache: variantTextures[state][variant] = texture
        this.variantTextures = {};
    }

    /**
     * Load pipe variant textures from atlases (normal, normal_selected, damaged, damaged_selected)
     * Each atlas: 1024x64 (16 variants × 64px each)
     */
    async loadVariantTextures() {
        this.atlases = {};
        const states = ['normal', 'normal_selected', 'damaged', 'damaged_selected'];

        for (const state of states) {
            const atlasUrl = this.game.assetUrl(
                `${this.game.config.tilesPath}entities/pipe/pipe_atlas_${state}.png?v=${Date.now()}`
            );

            try {
                const atlasTexture = await PIXI.Assets.load(atlasUrl);
                this.atlases[state] = atlasTexture;
            } catch (e) {
                console.error(`Failed to load pipe atlas ${state}:`, e);
            }
        }
    }

    /**
     * Register a pipe sprite for connection management
     */
    registerPipe(entityId, sprite) {
        this.pipeSprites.set(entityId, sprite);
    }

    /**
     * Unregister a pipe sprite (when removed)
     */
    unregisterPipe(entityId) {
        this.pipeSprites.delete(entityId);
    }

    /**
     * Check if entity is a pipe
     */
    isPipe(entity) {
        if (!entity) return false;
        return this.PIPE_TYPES.includes(parseInt(entity.entity_type_id));
    }

    /**
     * Get connection variant (0-15) based on neighboring pipes
     * Bit mask: right(1), down(2), left(4), up(8)
     */
    getConnectionVariant(entity) {
        // Safety check
        if (!entity) {
            return 0;
        }

        const neighbors = this.getNeighbors(entity);
        let variant = 0;

        // Check each direction for pipe neighbors
        const hasRight = this.isPipe(neighbors.right);
        const hasDown = this.isPipe(neighbors.down);
        const hasLeft = this.isPipe(neighbors.left);
        const hasUp = this.isPipe(neighbors.up);

        if (hasRight) variant |= 1;  // Bit 0: right
        if (hasDown) variant |= 2;    // Bit 1: down
        if (hasLeft) variant |= 4;    // Bit 2: left
        if (hasUp) variant |= 8;      // Bit 3: up

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
        // Safety check
        if (!this.game || !this.game.entityData) {
            return null;
        }

        for (const [key, entity] of this.game.entityData) {
            if (parseInt(entity.x) === x && parseInt(entity.y) === y) {
                return entity;
            }
        }
        return null;
    }

    /**
     * Get entity state based on durability
     */
    getEntityState(entity) {
        if (entity.state === 'blueprint') {
            return 'blueprint';
        }

        const entityType = this.game.entityTypes[entity.entity_type_id];
        const maxDurability = entityType?.max_durability || 100;
        const durability = entity.durability || maxDurability;
        const isDamaged = durability < (maxDurability * 0.5);

        return isDamaged ? 'damaged' : 'normal';
    }

    /**
     * Get texture for pipe based on connection variant and state
     * @param {Object} entity - Entity data
     * @param {boolean} isHovered - Is mouse hovering over pipe
     * @returns {PIXI.Texture}
     */
    getPipeTexture(entity, isHovered = false) {
        const baseState = this.getEntityState(entity);
        const state = isHovered ? `${baseState}_selected` : baseState;
        const variant = this.getConnectionVariant(entity);

        // Get atlas for this state
        const atlas = this.atlases[state];
        if (!atlas) {
            console.warn(`Pipe atlas not found: ${state}`);
            return this.game.textures['entity_131_normal'];
        }

        // Extract texture from atlas
        const rect = new PIXI.Rectangle(variant * 64, 0, 64, 64);
        return new PIXI.Texture({
            source: atlas.source,
            frame: rect
        });
    }

    /**
     * Update connections for all pipes
     * Called when a pipe is placed or removed
     */
    updateAllConnections() {
        for (const [entityId, container] of this.pipeSprites) {
            const key = `entity_${entityId}`;
            const entity = this.game.entityData.get(key);

            if (!entity) continue;

            const texture = this.getPipeTexture(entity);

            if (texture) {
                // Update pipe sprite (first child in container)
                const pipeSprite = container.children[0];
                if (pipeSprite) {
                    pipeSprite.texture = texture;
                }
            }
        }
    }

    /**
     * Update single pipe texture (for hover/unhover)
     * @param {number} entityId
     * @param {boolean} isHovered
     */
    updatePipeTexture(entityId, isHovered = false) {
        const container = this.pipeSprites.get(entityId);
        if (!container) return;

        const key = `entity_${entityId}`;
        const entity = this.game.entityData.get(key);
        if (!entity) return;

        const texture = this.getPipeTexture(entity, isHovered);
        if (texture) {
            // Update pipe sprite (first child in container)
            const pipeSprite = container.children[0];
            if (pipeSprite) {
                pipeSprite.texture = texture;
            }
        }
    }
}

export default PipeConnectionManager;
