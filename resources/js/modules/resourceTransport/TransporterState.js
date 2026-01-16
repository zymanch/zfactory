/**
 * TransporterState - State of a conveyor belt
 */
export class TransporterState {
    constructor(entity, entityType, game) {
        this.entityId = entity.entity_id;
        this.x = parseInt(entity.x);
        this.y = parseInt(entity.y);
        this.orientation = entityType.orientation || 'right';
        this.power = parseInt(entityType.power) || 100;
        this.game = game;

        // Underground conveyor detection
        const folder = entityType.folder || '';
        this.isUndergroundIn = folder.includes('underground_belt_') && folder.includes('_in');
        this.isUndergroundOut = folder.includes('underground_belt_') && folder.includes('_out');
        this.undergroundPairId = null;  // For underground_in: ID of underground_out

        // Current state
        this.status = 'empty'; // 'empty' | 'carrying' | 'waiting_transfer'
        this.resourceId = null;
        this.resourceAmount = 0;

        // Movement in ticks instead of pixels (0-30 for full tile traversal)
        // Negative ticks for underground conveyors (distance-based)
        this.ticks = null;

        // Direction from which resource entered ('up'|'down'|'left'|'right')
        this.fromDirection = null;

        // Constants
        this.TICKS_PER_TILE = 30;  // At power=100, resource travels 1 tile in 30 ticks

        // Links (set during link calculation)
        this.targetEntityId = null;      // Where this conveyor sends resources
        this.sourceEntityIds = [];       // Conveyors that feed into this one
        this.straightSourceId = null;    // Source with same orientation (priority)

        // For round-robin side source selection
        this.lastSideSourceIndex = 0;

        // Transfer flag (used in simultaneous transfer logic)
        this.willTransfer = false;
    }

    /**
     * Check if conveyor is empty
     */
    isEmpty() {
        return this.status === 'empty';
    }

    /**
     * Check if conveyor has resource at end (ready to transfer)
     */
    isReadyToTransfer() {
        return this.status === 'waiting_transfer';
    }

    /**
     * Calculate position in pixels from ticks
     * Returns position in range [-32px, +32px] (or more negative for underground)
     */
    getPositionPx() {
        if (this.ticks === null) return null;

        const tileWidth = this.game.config.tileWidth;

        // Map ticks (0-30) to position (-32px to +32px)
        // Formula: position = (ticks / 30) * 64 - 32
        // Note: ticks already account for power (increment = power/100 in animation)
        return (this.ticks / this.TICKS_PER_TILE) * tileWidth - (tileWidth / 2);
    }

    /**
     * Get tick increment per frame based on power
     */
    getTickIncrement() {
        return this.power / 100;  // power=100 → 1 tick/frame, power=200 → 2 ticks/frame
    }

    /**
     * Get current movement phase (1 or 2)
     * Phase 1: moving to center (position < 0)
     * Phase 2: moving to exit (position >= 0)
     */
    getMovementPhase() {
        const pos = this.getPositionPx();
        return pos === null || pos < 0 ? 1 : 2;
    }

    /**
     * Load state from saved data
     */
    loadFromSaved(data) {
        this.resourceId = data.resource_id;
        this.resourceAmount = data.amount || 1;

        // Convert from DB format (position_px: 0-64) to ticks (0-30)
        // DB: 0 = edge, 32 = center, 64 = exit
        // Ticks: 0 = edge, 15 = center, 30 = exit
        // Formula: ticks = (position_px / 64) * 30
        if (data.position_px !== null && data.position_px !== undefined) {
            const dbPos = parseInt(data.position_px);
            const tileWidth = this.game.config.tileWidth;
            this.ticks = (dbPos / tileWidth) * this.TICKS_PER_TILE;
        } else {
            this.ticks = 0;  // Start at edge
        }

        this.fromDirection = data.from_direction || 'down';
        this.status = data.status || (this.resourceId ? 'carrying' : 'empty');
    }

    /**
     * Get data for saving
     * NEW (2026-01): No entity_id - will be dict key
     */
    getSaveData() {
        if (!this.resourceId) return null;

        // Convert from ticks (0-30) to DB format (0-64)
        // Formula: position_px = (ticks / 30) * 64
        const tileWidth = this.game.config.tileWidth;
        const dbPositionPx = (this.ticks / this.TICKS_PER_TILE) * tileWidth;

        return {
            // No entity_id - will be dict key
            resource_id: this.resourceId,
            amount: this.resourceAmount,
            position_px: Math.round(dbPositionPx),
            from_direction: this.fromDirection,
            status: this.status
        };
    }

    /**
     * Clear resource from conveyor
     */
    clear() {
        this.resourceId = null;
        this.resourceAmount = 0;
        this.ticks = null;
        this.fromDirection = null;
        this.status = 'empty';
        this.willTransfer = false;
    }

    /**
     * Set resource on conveyor
     * @param {number} ticks - Optional: explicit tick position (for underground conveyors or special cases)
     */
    setResource(resourceId, amount, fromDirection, ticks = null) {
        this.resourceId = resourceId;
        this.resourceAmount = amount;
        this.fromDirection = fromDirection || 'down';

        if (ticks !== null) {
            // Explicit tick position provided (e.g., underground conveyors)
            this.ticks = ticks;
        } else {
            // Check if entry is from manipulator (places directly at center)
            const isFromManipulator = false; // TODO: detect manipulator placement

            if (isFromManipulator) {
                // Manipulator places at center - skip phase 1
                this.ticks = this.TICKS_PER_TILE / 2;  // 15 ticks = center
            } else {
                // Normal entry from edge - start phase 1
                this.ticks = 0;  // Start at 0 ticks (edge)
            }
        }

        this.status = 'carrying';
    }
}

export default TransporterState;
