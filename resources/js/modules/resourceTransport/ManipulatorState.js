/**
 * ManipulatorState - State of a manipulator (inserter)
 */
export class ManipulatorState {
    constructor(entity, entityType, game) {
        this.entityId = entity.entity_id;
        this.x = parseInt(entity.x);
        this.y = parseInt(entity.y);
        this.orientation = entityType.orientation || 'right';
        this.power = parseInt(entityType.power) || 100;
        this.game = game;

        // Reach: Long Manipulator = 2 tiles, Short = 1 tile
        this.reach = entityType.name.includes('Long') ? 2 : 1;

        // Current state
        this.status = 'idle'; // 'idle' | 'picking' | 'carrying' | 'placing'
        this.resourceId = null;
        this.resourceAmount = 0;

        // Arm position in pixels (centered: -centerPx to +centerPx, 0 = at manipulator center)
        const tileWidth = game.config.tileWidth;
        this.position_px = 0;

        // Center position - calculated as reach * tileWidth * 1.5
        this.centerPositionPx = this.reach * tileWidth * 1.5;

        // Total reach distance in pixels (from -centerPx to +centerPx)
        this.totalReachPx = this.centerPositionPx;

        // Links (set during link calculation)
        this.sourceEntityId = null;  // Where to pick from (behind)
        this.targetEntityId = null;  // Where to place (in front)
    }

    /**
     * Check if manipulator is idle (not holding anything)
     */
    isIdle() {
        return this.status === 'idle' && !this.resourceId;
    }

    /**
     * Check if manipulator is holding a resource
     */
    hasResource() {
        return this.resourceId !== null;
    }

    /**
     * Calculate arm movement speed (pixels per frame at 60 FPS)
     */
    getArmSpeed() {
        const tileWidth = this.game.config.tileWidth;
        // power=100 means full swing in 30 frames (0.5 seconds at 60 FPS)
        // Full swing = reach * tileWidth pixels
        return (this.power / 100) * (tileWidth / 30);
    }

    /**
     * Load state from saved data
     */
    loadFromSaved(data) {
        this.resourceId = data.resource_id;
        this.resourceAmount = data.amount || 1;
        this.position_px = parseInt(data.position_px) || 0;
        this.status = data.status || 'idle';
    }

    /**
     * Get data for saving
     */
    getSaveData() {
        if (this.status === 'idle' && !this.resourceId) return null;

        return {
            entity_id: this.entityId,
            resource_id: this.resourceId,
            amount: this.resourceAmount,
            position_px: this.position_px,
            status: this.status
        };
    }

    /**
     * Clear resource from manipulator
     */
    clear() {
        this.resourceId = null;
        this.resourceAmount = 0;
        this.status = 'idle';
        this.position_px = 0;
    }

    /**
     * Pick up resource
     */
    pickResource(resourceId, amount) {
        this.resourceId = resourceId;
        this.resourceAmount = amount;
        this.status = 'carrying';
    }

    /**
     * Get opposite direction
     */
    static getOppositeOrientation(orientation) {
        const opposites = {
            'up': 'down',
            'down': 'up',
            'left': 'right',
            'right': 'left'
        };
        return opposites[orientation] || 'right';
    }

    /**
     * Get source position (behind the manipulator)
     */
    getSourcePosition() {
        return this.getPositionInDirection(
            ManipulatorState.getOppositeOrientation(this.orientation),
            this.reach
        );
    }

    /**
     * Get target position (in front of the manipulator)
     */
    getTargetPosition() {
        return this.getPositionInDirection(this.orientation, this.reach);
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
}

export default ManipulatorState;
