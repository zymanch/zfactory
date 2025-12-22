/**
 * SpatialIndex - Fast lookup of entities by tile position
 */
export class SpatialIndex {
    constructor() {
        this.grid = new Map(); // "x_y" → entity_id
    }

    /**
     * Add entity to index
     */
    add(entity) {
        const key = `${entity.x}_${entity.y}`;
        this.grid.set(key, entity.entity_id);
    }

    /**
     * Remove entity from index
     */
    remove(entity) {
        const key = `${entity.x}_${entity.y}`;
        this.grid.delete(key);
    }

    /**
     * Update entity position
     */
    update(entity, oldX, oldY) {
        const oldKey = `${oldX}_${oldY}`;
        this.grid.delete(oldKey);
        this.add(entity);
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
