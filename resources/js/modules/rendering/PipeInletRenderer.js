/**
 * PipeInletRenderer - Renders inlet sprites where pipes connect to buildings
 * Shows 15px inlet sprites on the edges of buildings that have pipe connections
 */
import * as PIXI from 'pixi.js';

export class PipeInletRenderer {
    constructor(game) {
        this.game = game;
        this.container = new PIXI.Container();
        this.container.name = 'PipeInlets';

        this.atlasTexture = null;
        this.spriteCache = new Map(); // entity_id + direction => sprite

        // Pipe entity type IDs
        this.PIPE_TYPE_IDS = [131, 132, 135, 136, 140, 141];

        // Atlas layout: 20 sprites (4 directions × 5 states)
        // Order: [state][direction] where directions = [top, right, down, left]
        this.SPRITE_SIZE = 64;
        this.states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];
        this.directions = ['top', 'right', 'down', 'left'];

        this.loadAtlas();
    }

    /**
     * Load inlet atlas texture from GraphicsEngine
     */
    async loadAtlas() {
        this.atlasTexture = this.game.graphics.getTexture('pipe_inlet_atlas');
        if (!this.atlasTexture) {
            console.warn('[PipeInletRenderer] Atlas not loaded, inlet rendering disabled');
        }
    }

    /**
     * Get texture for specific state and direction
     * @param {string} state - 'normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'
     * @param {string} direction - 'top', 'right', 'down', 'left'
     * @returns {PIXI.Texture}
     */
    getTexture(state, direction) {
        if (!this.atlasTexture) return null;

        const stateIndex = this.states.indexOf(state);
        const dirIndex = this.directions.indexOf(direction);

        if (stateIndex === -1 || dirIndex === -1) {
            console.warn(`[PipeInletRenderer] Invalid state or direction: ${state}, ${direction}`);
            return null;
        }

        // Calculate position in atlas
        const index = stateIndex * 4 + dirIndex; // 4 directions per state
        const x = index * this.SPRITE_SIZE;

        return new PIXI.Texture(
            this.atlasTexture.baseTexture,
            new PIXI.Rectangle(x, 0, this.SPRITE_SIZE, this.SPRITE_SIZE)
        );
    }

    /**
     * Update inlet sprites for all entities
     * Called each frame
     */
    update() {
        if (!this.atlasTexture) return;

        const tileSize = this.game.config.tileWidth || 64;
        const currentSprites = new Set();

        // Find all buildings/mining/storage entities
        for (const [key, entity] of this.game.entityData) {
            const entityType = this.game.entityTypes[entity.entity_type_id];
            if (!entityType) continue;

            // Only process buildings, mining, and storage entities
            if (!['building', 'mining', 'storage'].includes(entityType.type)) continue;

            // Check for pipe connections in 4 directions
            const connections = this.findPipeConnections(entity);

            for (const direction of connections) {
                const spriteKey = `${entity.entity_id}_${direction}`;
                currentSprites.add(spriteKey);

                // Get or create sprite
                let sprite = this.spriteCache.get(spriteKey);
                if (!sprite) {
                    sprite = new PIXI.Sprite();
                    sprite.anchor.set(0.5, 0.5);
                    this.container.addChild(sprite);
                    this.spriteCache.set(spriteKey, sprite);
                }

                // Determine state
                const state = this.getEntityState(entity);

                // Update texture
                const texture = this.getTexture(state, direction);
                if (texture) {
                    sprite.texture = texture;
                }

                // Position sprite at entity edge
                const position = this.getInletPosition(entity, direction, tileSize);
                sprite.x = position.x;
                sprite.y = position.y;
                sprite.visible = true;
            }
        }

        // Remove sprites for entities that no longer have connections
        for (const [spriteKey, sprite] of this.spriteCache) {
            if (!currentSprites.has(spriteKey)) {
                sprite.visible = false;
                sprite.destroy();
                this.spriteCache.delete(spriteKey);
            }
        }
    }

    /**
     * Find pipe connections for entity
     * @param {Object} entity
     * @returns {Array<string>} - Array of directions: 'top', 'right', 'down', 'left'
     */
    findPipeConnections(entity) {
        const tileSize = this.game.config.tileWidth || 64;
        const connections = [];

        const checks = [
            { direction: 'top', dx: 0, dy: -tileSize },
            { direction: 'right', dx: tileSize, dy: 0 },
            { direction: 'down', dx: 0, dy: tileSize },
            { direction: 'left', dx: -tileSize, dy: 0 }
        ];

        for (const check of checks) {
            const x = entity.x + check.dx;
            const y = entity.y + check.dy;

            // Use spatial index if available
            let neighborId;
            if (this.game.resourceTransportManager?.spatialIndex) {
                neighborId = this.game.resourceTransportManager.spatialIndex.getAt(x, y);
            } else {
                // Fallback: manual search
                for (const [key, e] of this.game.entityData) {
                    if (e.x === x && e.y === y) {
                        neighborId = e.entity_id;
                        break;
                    }
                }
            }

            if (neighborId) {
                const neighborEntity = this.game.entityData.get(`entity_${neighborId}`);
                if (neighborEntity && this.PIPE_TYPE_IDS.includes(neighborEntity.entity_type_id)) {
                    connections.push(check.direction);
                }
            }
        }

        return connections;
    }

    /**
     * Get entity visual state for inlet sprite
     * @param {Object} entity
     * @returns {string} - 'normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'
     */
    getEntityState(entity) {
        const entityType = this.game.entityTypes[entity.entity_type_id];
        const isSelected = this.game.selectedEntity?.entity_id === entity.entity_id;

        // Blueprint state
        if (entity.state === 'blueprint') {
            return 'blueprint';
        }

        // Check durability
        const maxDurability = parseInt(entityType?.max_durability) || 100;
        const isDamaged = entity.durability < (maxDurability * 0.5);

        if (isSelected) {
            return isDamaged ? 'damaged_selected' : 'normal_selected';
        } else {
            return isDamaged ? 'damaged' : 'normal';
        }
    }

    /**
     * Get inlet sprite position at entity edge
     * @param {Object} entity
     * @param {string} direction - 'top', 'right', 'down', 'left'
     * @param {number} tileSize
     * @returns {Object} - {x, y}
     */
    getInletPosition(entity, direction, tileSize) {
        const halfTile = tileSize / 2;

        switch (direction) {
            case 'top':
                return { x: entity.x, y: entity.y - halfTile };
            case 'right':
                return { x: entity.x + halfTile, y: entity.y };
            case 'down':
                return { x: entity.x, y: entity.y + halfTile };
            case 'left':
                return { x: entity.x - halfTile, y: entity.y };
            default:
                return { x: entity.x, y: entity.y };
        }
    }

    /**
     * Cleanup
     */
    destroy() {
        for (const sprite of this.spriteCache.values()) {
            sprite.destroy();
        }
        this.spriteCache.clear();
        this.container.destroy();
    }
}

export default PipeInletRenderer;
