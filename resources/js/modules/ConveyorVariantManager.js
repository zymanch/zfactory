import { ConveyorConnectionHelper } from './ConveyorConnectionHelper.js';

/**
 * ConveyorVariantManager - Isolated logic for calculating conveyor connection variants
 *
 * Pure logic manager (no graphics) that determines which variant (0-15) a conveyor
 * should display based on neighboring conveyors. Works with ALL entity.type='conveyor'
 * including regular conveyors and underground belts.
 *
 * Variant is a 4-bit mask:
 * - Bit 0 (1): LEFT connection
 * - Bit 1 (2): RIGHT connection
 * - Bit 2 (4): UP connection
 * - Bit 3 (8): DOWN connection
 */
export class ConveyorVariantManager {
    constructor(game) {
        this.game = game;

        // Spatial index for fast conveyor lookup by coordinates: Map<"x_y", entity>
        // Dramatically improves performance from O(n) to O(1) per neighbor check
        this.spatialIndex = new Map();

        // NOTE: buildSpatialIndex() removed from constructor - index is built incrementally
        // via addToIndex() calls during entity registration
    }

    /**
     * Build spatial index for all conveyors
     * Called once on init and when conveyors are added/removed
     */
    buildSpatialIndex() {
        this.spatialIndex.clear();

        for (const [key, entity] of this.game.entityData) {
            const entityType = this.game.entityTypes[entity.entity_type_id];
            if (entityType && entityType.type === 'conveyor') {
                const coordKey = `${parseInt(entity.x)}_${parseInt(entity.y)}`;
                this.spatialIndex.set(coordKey, entity);
            }
        }
    }

    /**
     * Add conveyor to spatial index
     */
    addToIndex(entity) {
        const entityType = this.game.entityTypes[entity.entity_type_id];
        if (entityType && entityType.type === 'conveyor') {
            const coordKey = `${parseInt(entity.x)}_${parseInt(entity.y)}`;
            this.spatialIndex.set(coordKey, entity);
        }
    }

    /**
     * Remove conveyor from spatial index
     */
    removeFromIndex(entity) {
        const coordKey = `${parseInt(entity.x)}_${parseInt(entity.y)}`;
        this.spatialIndex.delete(coordKey);
    }

    /**
     * Calculate connection variant (0-15) for a conveyor entity
     * @param {Object} entity - The entity to calculate variant for
     * @returns {number} - Variant index (0-15 bit mask)
     */
    calculateVariant(entity) {
        const neighbors = this.getNeighbors(entity);

        let variant = 0;

        // Bit 0 (LEFT): Left connection exists
        if (this.hasLeftConnection(entity, neighbors.left)) {
            variant |= 1;
        }

        // Bit 1 (RIGHT): Right connection exists
        if (this.hasRightConnection(entity, neighbors.right)) {
            variant |= 2;
        }

        // Bit 2 (UP): Top connection exists
        if (this.hasTopConnection(entity, neighbors.up)) {
            variant |= 4;
        }

        // Bit 3 (DOWN): Bottom connection exists
        if (this.hasBottomConnection(entity, neighbors.down)) {
            variant |= 8;
        }

        return variant;
    }

    /**
     * Get neighboring entities (all entity.type='conveyor')
     */
    getNeighbors(entity) {
        const x = parseInt(entity.x);
        const y = parseInt(entity.y);

        return {
            left: this.getConveyorAt(x - 1, y),
            right: this.getConveyorAt(x + 1, y),
            up: this.getConveyorAt(x, y - 1),
            down: this.getConveyorAt(x, y + 1)
        };
    }

    /**
     * Get conveyor entity at coordinates (any type='conveyor')
     * Uses spatial index for O(1) lookup instead of O(n) iteration
     */
    getConveyorAt(x, y) {
        const coordKey = `${x}_${y}`;
        return this.spatialIndex.get(coordKey) || null;
    }

    /**
     * Check if left neighbor creates a connection
     */
    hasLeftConnection(entity, leftNeighbor) {
        if (!leftNeighbor) return false;

        const currentEntityType = this.game.entityTypes[entity.entity_type_id];
        const neighborType = this.game.entityTypes[leftNeighbor.entity_type_id];

        // Connection exists if:
        // 1. Left neighbor can output to us (to the right) OR
        // 2. We can output to left neighbor (to the left)
        const neighborCanOutputToUs = ConveyorConnectionHelper.canOutputTo(neighborType, 'right');
        const weCanOutputToNeighbor = ConveyorConnectionHelper.canOutputTo(currentEntityType, 'left');

        return neighborCanOutputToUs || weCanOutputToNeighbor;
    }

    /**
     * Check if right neighbor creates a connection
     */
    hasRightConnection(entity, rightNeighbor) {
        if (!rightNeighbor) return false;

        const currentEntityType = this.game.entityTypes[entity.entity_type_id];
        const neighborType = this.game.entityTypes[rightNeighbor.entity_type_id];

        // Connection exists if:
        // 1. Right neighbor can output to us (to the left) OR
        // 2. We can output to right neighbor (to the right)
        const neighborCanOutputToUs = ConveyorConnectionHelper.canOutputTo(neighborType, 'left');
        const weCanOutputToNeighbor = ConveyorConnectionHelper.canOutputTo(currentEntityType, 'right');

        return neighborCanOutputToUs || weCanOutputToNeighbor;
    }

    /**
     * Check if top neighbor creates a connection
     */
    hasTopConnection(entity, topNeighbor) {
        if (!topNeighbor) return false;

        const currentEntityType = this.game.entityTypes[entity.entity_type_id];
        const neighborType = this.game.entityTypes[topNeighbor.entity_type_id];

        // Connection exists if:
        // 1. Top neighbor can output to us (down) OR
        // 2. We can output to top neighbor (up)
        const neighborCanOutputToUs = ConveyorConnectionHelper.canOutputTo(neighborType, 'down');
        const weCanOutputToNeighbor = ConveyorConnectionHelper.canOutputTo(currentEntityType, 'up');

        return neighborCanOutputToUs || weCanOutputToNeighbor;
    }

    /**
     * Check if bottom neighbor creates a connection
     */
    hasBottomConnection(entity, bottomNeighbor) {
        if (!bottomNeighbor) return false;

        const currentEntityType = this.game.entityTypes[entity.entity_type_id];
        const neighborType = this.game.entityTypes[bottomNeighbor.entity_type_id];

        // Connection exists if:
        // 1. Bottom neighbor can output to us (up) OR
        // 2. We can output to bottom neighbor (down)
        const neighborCanOutputToUs = ConveyorConnectionHelper.canOutputTo(neighborType, 'up');
        const weCanOutputToNeighbor = ConveyorConnectionHelper.canOutputTo(currentEntityType, 'down');

        return neighborCanOutputToUs || weCanOutputToNeighbor;
    }
}

export default ConveyorVariantManager;
