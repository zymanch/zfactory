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
        this.buildSpatialIndex();
    }

    /**
     * Build spatial index for all conveyors
     * Called once on init and when conveyors are added/removed
     */
    buildSpatialIndex() {
        this.spatialIndex.clear();

        console.log('[ConveyorVariant] Building spatial index...');
        let count = 0;
        let shipCount = 0;

        for (const [key, entity] of this.game.entityData) {
            const entityType = this.game.entityTypes[entity.entity_type_id];
            if (entityType && entityType.type === 'conveyor') {
                const coordKey = `${parseInt(entity.x)}_${parseInt(entity.y)}`;
                this.spatialIndex.set(coordKey, entity);
                count++;

                // DEBUG: Log ship conveyors
                if (entity.y === 44 && entity.x >= 100 && entity.x <= 104) {
                    console.log(`  Ship conveyor: ${entity.entity_id} at (${entity.x}, ${entity.y}) -> key "${coordKey}" -> ${entityType.folder}`);
                    shipCount++;
                }
            }
        }

        console.log(`[ConveyorVariant] Index built: ${count} conveyors total, ${shipCount} ship conveyors at y=44`);
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
        const entityType = this.game.entityTypes[entity.entity_type_id];
        const orientation = entityType.folder;

        // DEBUG: Log for ship conveyors at y=44
        const isDebugEntity = entity.y === 44 && entity.x >= 101 && entity.x <= 103;
        if (isDebugEntity) {
            console.log(`[ConveyorVariant] calculateVariant for entity ${entity.entity_id} at (${entity.x}, ${entity.y}):`, {
                orientation,
                entity_type_id: entity.entity_type_id,
                neighbors: {
                    left: neighbors.left ? `${neighbors.left.entity_id} (${this.game.entityTypes[neighbors.left.entity_type_id]?.folder})` : null,
                    right: neighbors.right ? `${neighbors.right.entity_id} (${this.game.entityTypes[neighbors.right.entity_type_id]?.folder})` : null,
                    up: neighbors.up ? `${neighbors.up.entity_id} (${this.game.entityTypes[neighbors.up.entity_type_id]?.folder})` : null,
                    down: neighbors.down ? `${neighbors.down.entity_id} (${this.game.entityTypes[neighbors.down.entity_type_id]?.folder})` : null
                }
            });
        }

        let variant = 0;

        // Bit 0 (LEFT): Left connection exists
        if (this.hasLeftConnection(entity, neighbors.left, orientation)) {
            variant |= 1;
        }

        // Bit 1 (RIGHT): Right connection exists
        if (this.hasRightConnection(entity, neighbors.right, orientation)) {
            variant |= 2;
        }

        // Bit 2 (UP): Top connection exists
        if (this.hasTopConnection(entity, neighbors.up, orientation)) {
            variant |= 4;
        }

        // Bit 3 (DOWN): Bottom connection exists
        if (this.hasBottomConnection(entity, neighbors.down, orientation)) {
            variant |= 8;
        }

        if (isDebugEntity) {
            console.log(`[ConveyorVariant] Final variant for ${entity.entity_id}: ${variant} (binary: ${variant.toString(2).padStart(4, '0')})`);
        }

        return variant;
    }

    /**
     * Get neighboring entities (all entity.type='conveyor')
     */
    getNeighbors(entity) {
        const x = parseInt(entity.x);
        const y = parseInt(entity.y);

        // DEBUG: Log spatial index contents for ship conveyors
        const isDebugEntity = entity.y === 44 && entity.x >= 101 && entity.x <= 103;
        if (isDebugEntity) {
            console.log(`[ConveyorVariant] Spatial index around (${x}, ${y}):`);
            for (let dx = -1; dx <= 1; dx++) {
                for (let dy = -1; dy <= 1; dy++) {
                    const checkX = x + dx;
                    const checkY = y + dy;
                    const key = `${checkX}_${checkY}`;
                    const found = this.spatialIndex.get(key);
                    if (found) {
                        const foundType = this.game.entityTypes[found.entity_type_id];
                        console.log(`  ${key}: ${found.entity_id} (${foundType?.folder})`);
                    }
                }
            }
        }

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
    hasLeftConnection(entity, leftNeighbor, currentOrientation) {
        if (!leftNeighbor) return false;

        const neighborType = this.game.entityTypes[leftNeighbor.entity_type_id];
        const neighborOrientation = neighborType.folder;

        // Connection exists if:
        // 1. Neighbor is outgoing TO us (moving right) OR
        // 2. We are outgoing TO neighbor (moving left)

        const neighborMovingToUs = this.isMovingRight(neighborOrientation);
        const weMovingToNeighbor = this.isMovingLeft(currentOrientation);

        return neighborMovingToUs || weMovingToNeighbor;
    }

    /**
     * Check if right neighbor creates a connection
     */
    hasRightConnection(entity, rightNeighbor, currentOrientation) {
        if (!rightNeighbor) return false;

        const neighborType = this.game.entityTypes[rightNeighbor.entity_type_id];
        const neighborOrientation = neighborType.folder;

        // Connection exists if:
        // 1. Neighbor is outgoing TO us (moving left) OR
        // 2. We are outgoing TO neighbor (moving right)

        const neighborMovingToUs = this.isMovingLeft(neighborOrientation);
        const weMovingToNeighbor = this.isMovingRight(currentOrientation);

        return neighborMovingToUs || weMovingToNeighbor;
    }

    /**
     * Check if top neighbor creates a connection
     */
    hasTopConnection(entity, topNeighbor, currentOrientation) {
        if (!topNeighbor) return false;

        const neighborType = this.game.entityTypes[topNeighbor.entity_type_id];
        const neighborOrientation = neighborType.folder;

        // Connection exists if:
        // 1. Neighbor is outgoing TO us (moving down) OR
        // 2. We are outgoing TO neighbor (moving up)

        const neighborMovingToUs = this.isMovingDown(neighborOrientation);
        const weMovingToNeighbor = this.isMovingUp(currentOrientation);

        return neighborMovingToUs || weMovingToNeighbor;
    }

    /**
     * Check if bottom neighbor creates a connection
     */
    hasBottomConnection(entity, bottomNeighbor, currentOrientation) {
        if (!bottomNeighbor) return false;

        const neighborType = this.game.entityTypes[bottomNeighbor.entity_type_id];
        const neighborOrientation = neighborType.folder;

        // Connection exists if:
        // 1. Neighbor is outgoing TO us (moving up) OR
        // 2. We are outgoing TO neighbor (moving down)

        const neighborMovingToUs = this.isMovingUp(neighborOrientation);
        const weMovingToNeighbor = this.isMovingDown(currentOrientation);

        return neighborMovingToUs || weMovingToNeighbor;
    }

    /**
     * Helper: Check if orientation moves right
     */
    isMovingRight(orientation) {
        return orientation === 'conveyor' ||
               orientation === 'conveyor_dual' ||
               orientation === 'conveyor_fast_dual' ||
               orientation === 'underground_belt_in' ||
               orientation === 'underground_belt_dual_in' ||
               orientation === 'underground_belt_fast_in' ||
               orientation === 'underground_belt_out' ||
               orientation === 'underground_belt_dual_out' ||
               orientation === 'underground_belt_fast_out';
    }

    /**
     * Helper: Check if orientation moves left
     */
    isMovingLeft(orientation) {
        return orientation === 'conveyor_left' ||
               orientation === 'conveyor_dual_left' ||
               orientation === 'conveyor_fast_dual_left' ||
               orientation === 'underground_belt_in_left' ||
               orientation === 'underground_belt_dual_in_left' ||
               orientation === 'underground_belt_fast_in_left' ||
               orientation === 'underground_belt_out_left' ||
               orientation === 'underground_belt_dual_out_left' ||
               orientation === 'underground_belt_fast_out_left';
    }

    /**
     * Helper: Check if orientation moves up
     */
    isMovingUp(orientation) {
        return orientation === 'conveyor_up' ||
               orientation === 'conveyor_dual_up' ||
               orientation === 'conveyor_fast_dual_up' ||
               orientation === 'underground_belt_in_up' ||
               orientation === 'underground_belt_dual_in_up' ||
               orientation === 'underground_belt_fast_in_up' ||
               orientation === 'underground_belt_out_up' ||
               orientation === 'underground_belt_dual_out_up' ||
               orientation === 'underground_belt_fast_out_up';
    }

    /**
     * Helper: Check if orientation moves down
     */
    isMovingDown(orientation) {
        return orientation === 'conveyor_down' ||
               orientation === 'conveyor_dual_down' ||
               orientation === 'conveyor_fast_dual_down' ||
               orientation === 'underground_belt_in_down' ||
               orientation === 'underground_belt_dual_in_down' ||
               orientation === 'underground_belt_fast_in_down' ||
               orientation === 'underground_belt_out_down' ||
               orientation === 'underground_belt_dual_out_down' ||
               orientation === 'underground_belt_fast_out_down';
    }
}

export default ConveyorVariantManager;
