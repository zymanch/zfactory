/**
 * TransporterState - State of a conveyor belt
 */
export class TransporterState {
    constructor(entity, entityType) {
        this.entityId = entity.entity_id;
        this.x = parseInt(entity.x);
        this.y = parseInt(entity.y);
        this.orientation = entityType.orientation || 'right';
        this.power = parseInt(entityType.power) || 100;

        // Current state
        this.status = 'empty'; // 'empty' | 'carrying' | 'waiting_transfer'
        this.resourceId = null;
        this.resourceAmount = 0;

        // Position of resource on belt (0.0 = start, 1.0 = end)
        this.resourcePosition = 0.0;

        // Lateral offset when resource entered from side (-0.5 to 0.5, 0 = center)
        this.lateralOffset = 0.0;

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
     * Calculate movement speed (tiles per tick)
     */
    getSpeed() {
        // power=100 means 1 tile per 60 ticks (1 second)
        return (this.power / 100) / 60;
    }

    /**
     * Load state from saved data
     */
    loadFromSaved(data) {
        this.resourceId = data.resource_id;
        this.resourceAmount = data.amount || 1;
        this.resourcePosition = parseFloat(data.position) || 0;
        this.lateralOffset = parseFloat(data.lateral_offset) || 0;
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
            position: this.resourcePosition,
            lateral_offset: this.lateralOffset,
            status: this.status
        };
    }

    /**
     * Clear resource from conveyor
     */
    clear() {
        this.resourceId = null;
        this.resourceAmount = 0;
        this.resourcePosition = 0;
        this.lateralOffset = 0;
        this.status = 'empty';
        this.willTransfer = false;
    }

    /**
     * Set resource on conveyor
     */
    setResource(resourceId, amount, position = 0, lateralOffset = 0) {
        this.resourceId = resourceId;
        this.resourceAmount = amount;
        this.resourcePosition = position;
        this.lateralOffset = lateralOffset;
        this.status = 'carrying';
    }
}

export default TransporterState;
