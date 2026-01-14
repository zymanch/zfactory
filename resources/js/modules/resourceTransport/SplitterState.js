/**
 * SplitterState - State of a conveyor splitter (Y-shaped)
 *
 * Splitters have:
 * - 1 input from opposite direction of orientation
 * - 2 outputs at 90° angles (left and right relative to input)
 * - Round-robin distribution: alternates resources between outputs
 */
export class SplitterState {
    constructor(entity, entityType, game) {
        this.entityId = entity.entity_id;
        this.x = parseInt(entity.x);
        this.y = parseInt(entity.y);
        this.orientation = entityType.orientation || 'right';
        this.power = parseInt(entityType.power) || 100;
        this.game = game;

        // Centered coordinate system: position_px = 0 at splitter center
        this.position_px = 0;

        // Current resource being transported
        this.resourceId = null;
        this.resourceAmount = 0;

        // Last output direction ('left' or 'right')
        this.lastOutputDirection = 'right';

        // Links
        this.inputEntityId = null;
        this.leftOutputEntityId = null;
        this.rightOutputEntityId = null;
    }

    /**
     * Get input direction (opposite of orientation)
     */
    getInputDirection() {
        const opposites = {
            'up': 'down',
            'down': 'up',
            'left': 'right',
            'right': 'left'
        };
        return opposites[this.orientation] || 'down';
    }

    /**
     * Get output directions (left and right relative to input)
     * Returns { left: direction, right: direction }
     */
    getOutputDirections() {
        // For right/left orientation: outputs are up and down
        // For up/down orientation: outputs are left and right

        switch (this.orientation) {
            case 'right':
                return { left: 'up', right: 'down' };
            case 'left':
                return { left: 'down', right: 'up' };
            case 'down':
                return { left: 'right', right: 'left' };
            case 'up':
                return { left: 'left', right: 'right' };
            default:
                return { left: 'up', right: 'down' };
        }
    }

    /**
     * Get next output direction (round-robin)
     * @returns {string} 'left' or 'right'
     */
    getNextOutput() {
        // Toggle between left and right
        const next = this.lastOutputDirection === 'left' ? 'right' : 'left';
        this.lastOutputDirection = next;
        return next;
    }

    /**
     * Get input position
     */
    getInputPosition() {
        const direction = this.getInputDirection();
        return this.getPositionInDirection(direction, 1);
    }

    /**
     * Get left output position
     */
    getLeftOutputPosition() {
        const outputs = this.getOutputDirections();
        return this.getPositionInDirection(outputs.left, 1);
    }

    /**
     * Get right output position
     */
    getRightOutputPosition() {
        const outputs = this.getOutputDirections();
        return this.getPositionInDirection(outputs.right, 1);
    }

    /**
     * Get position at distance in direction
     */
    getPositionInDirection(direction, distance) {
        let x = this.x;
        let y = this.y;

        switch (direction) {
            case 'up':    y -= distance; break;
            case 'down':  y += distance; break;
            case 'left':  x -= distance; break;
            case 'right': x += distance; break;
        }

        return { x, y };
    }

    /**
     * Check if splitter is idle (not carrying anything)
     */
    isIdle() {
        return this.resourceId === null;
    }

    /**
     * Check if splitter is carrying a resource
     */
    hasResource() {
        return this.resourceId !== null;
    }

    /**
     * Receive resource from input
     */
    receiveResource(resourceId, amount) {
        this.resourceId = resourceId;
        this.resourceAmount = amount;
        this.position_px = -32; // Start at input side (left side of splitter)
    }

    /**
     * Calculate movement speed (pixels per frame at 60 FPS)
     */
    getSpeed() {
        const tileWidth = this.game.config.tileWidth;
        // power=100 means traverse 64px in 30 frames (0.5 seconds at 60 FPS)
        return (this.power / 100) * (tileWidth / 30);
    }

    /**
     * Update splitter state (called each frame at 60 FPS)
     */
    update() {
        if (!this.hasResource()) {
            return;
        }

        const speed = this.getSpeed();

        // Move resource from input (-32px) to center (0px)
        if (this.position_px < 0) {
            this.position_px += speed;

            // Reached center - decide which output to use
            if (this.position_px >= 0) {
                this.position_px = 0;
                this.transferToOutput();
            }
        }
    }

    /**
     * Transfer resource to next output (round-robin)
     */
    transferToOutput() {
        const outputDirection = this.getNextOutput();
        const targetEntityId = outputDirection === 'left' ? this.leftOutputEntityId : this.rightOutputEntityId;

        if (!targetEntityId) {
            // No output connected - clear resource
            this.clear();
            return;
        }

        // Get target state
        const targetState = this.game.resourceTransportManager.getState(targetEntityId);
        if (!targetState) {
            this.clear();
            return;
        }

        // Try to transfer to output conveyor/entity
        if (typeof targetState.canReceiveResource === 'function' && !targetState.canReceiveResource(this.resourceId)) {
            // Output is full or can't accept - wait
            return;
        }

        // Transfer resource
        if (typeof targetState.receiveResource === 'function') {
            targetState.receiveResource(this.resourceId, this.resourceAmount);
            this.clear();
        } else {
            // Fallback: just clear
            this.clear();
        }
    }

    /**
     * Clear resource from splitter
     */
    clear() {
        this.resourceId = null;
        this.resourceAmount = 0;
        this.position_px = 0;
    }

    /**
     * Load state from saved data
     */
    loadFromSaved(data) {
        this.resourceId = data.resource_id;
        this.resourceAmount = data.amount || 1;
        this.position_px = parseInt(data.position_px) || 0;
        this.lastOutputDirection = data.last_output_direction || 'right';
    }

    /**
     * Get data for saving
     * NEW (2026-01): No entity_id - will be dict key
     */
    getSaveData() {
        if (!this.hasResource()) return null;

        return {
            // No entity_id - will be dict key
            resource_id: this.resourceId,
            amount: this.resourceAmount,
            position_px: this.position_px,
            last_output_direction: this.lastOutputDirection
        };
    }
}

export default SplitterState;
