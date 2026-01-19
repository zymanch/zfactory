import * as PIXI from 'pixi.js';

/**
 * PipeConnectionManager - Handles pipe connections and variants
 *
 * Features:
 * - 16 connection variants based on 4-bit mask (right, down, left, up)
 * - Auto-updates connections when pipes are placed/removed
 * - No animations (pipes are static, unlike conveyors)
 * - Uses pre-loaded atlases from assetManifest (like conveyors)
 */
export class PipeConnectionManager {
    constructor(game) {
        this.game = game;

        // Pipe entity type IDs (all types with pipe_atlas variants)
        this.PIPE_TYPES = [131, 132, 140, 141];

        // Map entity type IDs to their folder names
        this.PIPE_FOLDERS = {
            131: 'pipe',
            132: 'pipe_vertical',
            140: 'underground_pipe_in',
            141: 'underground_pipe_out'
        };

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
     * Note: Pipes don't have blueprint state - use normal instead
     */
    getEntityState(entity) {
        // Pipes don't have blueprint atlases - treat blueprint as normal
        if (entity.state === 'blueprint') {
            return 'normal';
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
        const typeId = parseInt(entity.entity_type_id);
        const folder = this.PIPE_FOLDERS[typeId];
        if (!folder) {
            console.warn(`Pipe folder not found for type ${typeId}`);
            return null;
        }

        const baseState = this.getEntityState(entity);
        const state = isHovered ? `${baseState}_selected` : baseState;
        const variant = this.getConnectionVariant(entity);

        // Get atlas from pre-loaded textures (via assetManifest)
        const atlasKey = `pipe_${folder}_${state}`;
        const atlas = this.game.graphics.getTexture(atlasKey);

        if (!atlas) {
            console.warn(`Pipe atlas not found: ${atlasKey}`);
            return null;
        }

        // Extract texture from atlas (each variant is 64x64, laid out horizontally)
        const rect = this.game.graphics.createRectangle(variant * 64, 0, 64, 64);
        return this.game.graphics.createTextureFromAtlas(atlas, rect);
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
