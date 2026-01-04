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

        // Current state
        this.status = 'empty'; // 'empty' | 'carrying' | 'waiting_transfer'
        this.resourceId = null;
        this.resourceAmount = 0;

        // Position of resource on belt (centered: -centerPx to +centerPx, 0 = center)
        this.position_px = null;

        // Direction from which resource entered ('up'|'down'|'left'|'right')
        this.fromDirection = null;

        // Center position in pixels - calculated dynamically (tileWidth / 2)
        this.centerPositionPx = game.config.tileWidth / 2;

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
     * Calculate movement speed (pixels per frame at 60 FPS)
     */
    getSpeed() {
        const tileWidth = this.game.config.tileWidth;
        // power=100 means 1 tile per 60 frames (1 second at 60 FPS)
        // Returns pixels per frame
        return (this.power / 100) * (tileWidth / 60);
    }

    /**
     * Load state from saved data
     */
    loadFromSaved(data) {
        this.resourceId = data.resource_id;
        this.resourceAmount = data.amount || 1;
        this.position_px = parseInt(data.position_px) || -this.centerPositionPx;
        this.fromDirection = data.from_direction || 'down';
        this.status = data.status || (this.resourceId ? 'carrying' : 'empty');
    }

    /**
     * Get data for saving
     */
    getSaveData() {
        if (!this.resourceId) return null;

        return {
            entity_id: this.entityId,
            resource_id: this.resourceId,
            amount: this.resourceAmount,
            position_px: this.position_px,
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
        this.position_px = null;
        this.fromDirection = null;
        this.status = 'empty';
        this.willTransfer = false;
    }

    /**
     * Set resource on conveyor
     */
    setResource(resourceId, amount, fromDirection, positionPx = null) {
        this.resourceId = resourceId;
        this.resourceAmount = amount;
        this.fromDirection = fromDirection || 'down';
        // If no position specified, start at entry (-centerPositionPx)
        this.position_px = positionPx !== null ? positionPx : -this.centerPositionPx;
        this.status = 'carrying';
    }
}

export default TransporterState;
