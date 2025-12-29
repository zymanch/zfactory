/**
 * SpatialIndex - Fast lookup of entities by tile position
 */
export class SpatialIndex {
    constructor() {
        this.grid = new Map(); // "x_y" → entity_id
    }

    /**
     * Add entity to index (supports multi-tile entities)
     * @param {Object} entity - Entity object with x, y, entity_id
     * @param {number} width - Entity width in tiles (default 1)
     * @param {number} height - Entity height in tiles (default 1)
     */
    add(entity, width = 1, height = 1) {
        const x = parseInt(entity.x);
        const y = parseInt(entity.y);

        // Add all tiles occupied by this entity
        for (let dy = 0; dy < height; dy++) {
            for (let dx = 0; dx < width; dx++) {
                const key = `${x + dx}_${y + dy}`;
                this.grid.set(key, entity.entity_id);
            }
        }
    }

    /**
     * Remove entity from index (supports multi-tile entities)
     * @param {Object} entity - Entity object with x, y, entity_id
     * @param {number} width - Entity width in tiles (default 1)
     * @param {number} height - Entity height in tiles (default 1)
     */
    remove(entity, width = 1, height = 1) {
        const x = parseInt(entity.x);
        const y = parseInt(entity.y);

        // Remove all tiles occupied by this entity
        for (let dy = 0; dy < height; dy++) {
            for (let dx = 0; dx < width; dx++) {
                const key = `${x + dx}_${y + dy}`;
                this.grid.delete(key);
            }
        }
    }

    /**
     * Update entity position (supports multi-tile entities)
     * @param {Object} entity - Entity object with new x, y
     * @param {number} oldX - Old X position
     * @param {number} oldY - Old Y position
     * @param {number} width - Entity width in tiles (default 1)
     * @param {number} height - Entity height in tiles (default 1)
     */
    update(entity, oldX, oldY, width = 1, height = 1) {
        // Remove from old position
        for (let dy = 0; dy < height; dy++) {
            for (let dx = 0; dx < width; dx++) {
                const key = `${oldX + dx}_${oldY + dy}`;
                this.grid.delete(key);
            }
        }

        // Add at new position
        this.add(entity, width, height);
    }

    /**
     * Get entity ID at position
     * @returns {number|null}
     */
    getAt(x, y) {
        return this.grid.get(`${x}_${y}`) || null;
    }

    /**
     * Check if position is occupied
     */
    hasAt(x, y) {
        return this.grid.has(`${x}_${y}`);
    }

    /**
     * Clear all entries
     */
    clear() {
        this.grid.clear();
    }

    /**
     * Get count of indexed entities
     */
    get size() {
        return this.grid.size;
    }
}

export default SpatialIndex;
