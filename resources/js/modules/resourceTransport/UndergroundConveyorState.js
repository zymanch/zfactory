/**
 * UndergroundConveyorState - State of an underground conveyor (entrance or exit)
 *
 * Underground conveyors transport resources "underground" between entrance and exit
 * - Entrance: resources move to center (0px) and then teleport to exit
 * - Exit: resources appear at center (0px) and move to end (32px)
 */
export class UndergroundConveyorState {
    constructor(entity, entityType, game, isEntrance) {
        this.entityId = entity.entity_id;
        this.x = parseInt(entity.x);
        this.y = parseInt(entity.y);
        this.orientation = entityType.orientation || 'right';
        this.power = parseInt(entityType.power) || 100;
        this.game = game;
        this.isEntrance = isEntrance;

        // Centered coordinate system: position_px = 0 at center
        // Entrance: resource moves from -32 to 0 (then teleports)
        // Exit: resource appears at 0 and moves to 32
        this.position_px = 0;

        // Current resource being transported
        this.resourceId = null;
        this.resourceAmount = 0;

        // Link to paired entity
        this.exitEntityId = null;  // For entrance: ID of exit entity
        this.distance = 0;          // Distance in tiles to exit

        // Links for normal conveyor connection
        this.inputEntityId = null;
        this.outputEntityId = null;

        // Load link configuration if entrance
        if (this.isEntrance) {
            this.loadLink();
        }
    }

    /**
     * Load link configuration from server (for entrance only)
     */
    async loadLink() {
        try {
            const response = await fetch(`/game/underground-link?entity_id=${this.entityId}`);
            const data = await response.json();

            if (data.result === 'success' && data.link) {
                this.exitEntityId = data.link.exit_entity_id;
                this.distance = data.link.distance || 0;
            }
        } catch (error) {
            console.error('Failed to load underground link:', error);
        }
    }

    /**
     * Get input direction
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
     * Get output direction
     */
    getOutputDirection() {
        return this.orientation;
    }

    /**
     * Get input position
     */
    getInputPosition() {
        const direction = this.getInputDirection();
        return this.getPositionInDirection(direction, 1);
    }

    /**
     * Get output position
     */
    getOutputPosition() {
        const direction = this.getOutputDirection();
        return this.getPositionInDirection(direction, 1);
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
     * Check if underground conveyor is idle
     */
    isIdle() {
        return this.resourceId === null;
    }

    /**
     * Check if underground conveyor is carrying a resource
     */
    hasResource() {
        return this.resourceId !== null;
    }

    /**
     * Can receive resource (check if not full)
     */
    canReceiveResource(resourceId) {
        return this.isIdle();
    }

    /**
     * Receive resource from input
     */
    receiveResource(resourceId, amount) {
        this.resourceId = resourceId;
        this.resourceAmount = amount;

        if (this.isEntrance) {
            // Start at input side (-32px)
            this.position_px = -32;
        } else {
            // Exit: resource appears at center (0px)
            this.position_px = 0;
        }
    }

    /**
     * Receive resource from underground (for exit only)
     */
    receiveFromUnderground(resourceId, amount) {
        if (!this.isEntrance) {
            this.receiveResource(resourceId, amount);
        }
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
     * Update underground conveyor state (called each frame at 60 FPS)
     */
    update() {
        if (!this.hasResource()) {
            return;
        }

        const speed = this.getSpeed();

        if (this.isEntrance) {
            // Entrance: move from -32px to 0px, then teleport
            if (this.position_px < 0) {
                this.position_px += speed;

                // Reached center - teleport to exit
                if (this.position_px >= 0) {
                    this.position_px = 0;
                    this.teleportToExit();
                }
            }
        } else {
            // Exit: move from 0px to 32px
            if (this.position_px < 32) {
                this.position_px += speed;

                // Reached end - transfer to output
                if (this.position_px >= 32) {
                    this.position_px = 32;
                    this.transferToOutput();
                }
            }
        }
    }

    /**
     * Teleport resource to exit (entrance only)
     */
    teleportToExit() {
        if (!this.exitEntityId) {
            // No exit connected - resource is lost
            this.clear();
            return;
        }

        // Get exit state
        const exitState = this.game.resourceTransportManager.getState(this.exitEntityId);
        if (!exitState) {
            this.clear();
            return;
        }

        // Transfer resource to exit
        if (typeof exitState.receiveFromUnderground === 'function') {
            exitState.receiveFromUnderground(this.resourceId, this.resourceAmount);
            this.clear();
        } else {
            // Fallback
            this.clear();
        }
    }

    /**
     * Transfer resource to output (exit only)
     */
    transferToOutput() {
        if (!this.outputEntityId) {
            // No output connected - clear resource
            this.clear();
            return;
        }

        // Get output state
        const outputState = this.game.resourceTransportManager.getState(this.outputEntityId);
        if (!outputState) {
            this.clear();
            return;
        }

        // Try to transfer
        if (typeof outputState.canReceiveResource === 'function' && !outputState.canReceiveResource(this.resourceId)) {
            // Output is full - wait
            return;
        }

        // Transfer resource
        if (typeof outputState.receiveResource === 'function') {
            outputState.receiveResource(this.resourceId, this.resourceAmount);
            this.clear();
        } else {
            this.clear();
        }
    }

    /**
     * Clear resource
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

        if (data.link) {
            this.exitEntityId = data.link.exit_entity_id;
            this.distance = data.link.distance || 0;
        }
    }

    /**
     * Get data for saving
     */
    getSaveData() {
        if (!this.hasResource()) return null;

        return {
            entity_id: this.entityId,
            resource_id: this.resourceId,
            amount: this.resourceAmount,
            position_px: this.position_px
        };
    }
}

export default UndergroundConveyorState;
