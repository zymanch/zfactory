/**
 * ManipulatorState - State of a manipulator (inserter)
 * NEW (2026-01): Tick-based animation system
 */
export class ManipulatorState {
    constructor(entity, entityType, game) {
        this.entityId = entity.entity_id;
        this.entityTypeId = entityType.entity_type_id;
        this.x = parseInt(entity.x);
        this.y = parseInt(entity.y);
        this.orientation = entityType.orientation || 'right';
        this.power = parseInt(entityType.power) || 100;
        this.game = game;

        // Reach: Long Manipulator = 2 tiles, Short = 1 tile
        this.reach = entityType.name.includes('Long') ? 2 : 1;

        // Current state
        this.status = 'idle'; // 'idle' | 'picking' | 'carrying' | 'placing' | 'waiting_pick' | 'waiting_place' | 'returning'
        this.resourceId = null;
        this.resourceAmount = 0;

        // NEW: Tick-based animation (like conveyors)
        this.ticks = 0;
        this.maxTicks = 30;

        // NEW: Overflow dimensions for animation
        this.widthOverflow = entityType.width_overflow || 0;
        this.heightOverflow = entityType.height_overflow || 0;

        // Links (set during link calculation)
        this.sourceEntityId = null;  // Where to pick from (behind)
        this.targetEntityId = null;  // Where to place (in front)

        // Filter configuration (for filtered manipulators)
        this.filterResourceIds = [];         // Array of resource IDs to filter
        this.maxTransferCount = null;        // Max items to transfer (for counting manipulator)
        this.currentTransferCount = 0;       // Current transfer count

        // Check if this is a filtered manipulator (by subtype)
        const subtype = entityType.subtype || 'short';
        this.isFilteredManipulator = subtype.includes('filtered') || subtype.includes('counting');

        // Resource reference (for saving position)
        this.resource = null;
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
     * Get holder position in pixels (calculated from ticks)
     * Returns position relative to manipulator center
     * @returns {number} Position in pixels (-maxDistance to +maxDistance)
     */
    getHolderPositionPx() {
        // Maximum distance = overflow/2 × 64px
        const maxDistance = ((['right', 'left'].includes(this.orientation))
            ? this.widthOverflow
            : this.heightOverflow) / 2 * 64;

        // Direction multiplier based on orientation
        // right/down: source is at +maxDistance, target is at -maxDistance
        // left/up: source is at -maxDistance, target is at +maxDistance
        const directionSign = (this.orientation === 'right' || this.orientation === 'down') ? 1 : -1;

        const progress = this.ticks / this.maxTicks; // 0 to 1

        if (this.status === 'returning') {
            // Returning: ticks 0→30, progress 0→1
            // Position: target → source (-directionSign*maxDistance → +directionSign*maxDistance)
            // When progress=0 (ticks=0): position = -directionSign*maxDistance (at target)
            // When progress=1 (ticks=30): position = +directionSign*maxDistance (at source)
            return directionSign * (2 * progress - 1) * maxDistance;
        } else if (!this.hasResource()) {
            // Picking: ticks 0→30, progress 0→1
            // Position: 0 → source (directionSign*maxDistance)
            return directionSign * progress * maxDistance;
        } else {
            // Carrying: ticks 30→0, progress 1→0
            // Position: source → target (directionSign*maxDistance → -directionSign*maxDistance)
            // When progress=1 (ticks=30): position = directionSign*maxDistance (at source)
            // When progress=0 (ticks=0): position = -directionSign*maxDistance (at target)
            return directionSign * (2 * progress - 1) * maxDistance;
        }
    }

    /**
     * Get tick speed (ticks per frame)
     * power=100 => 1 tick per frame
     * @returns {number} Ticks per frame
     */
    getTickSpeed() {
        return this.power / 100;
    }

    /**
     * Save holder position to resource's position_px field
     * Called when manipulator has a resource
     */
    saveHolderPosition() {
        if (!this.hasResource() || !this.resource) return;

        // Save ticks (0-30) to position_px
        this.resource.position_px = this.ticks;
    }

    /**
     * Load state from saved data
     * NEW (2026-01): Restores ticks from position_px when resource exists
     */
    loadFromSaved(data) {
        if (!data || !data.resource_id) {
            // No resource - initialize in "ready to pick" position
            this.ticks = this.maxTicks;
            this.status = 'idle';
            return;
        }

        // Has resource - restore ticks from position_px
        this.resourceId = data.resource_id;
        this.resourceAmount = data.amount || 1;

        // Parse position_px and clamp to valid range [0, maxTicks]
        let savedTicks = parseInt(data.position_px) || 0;
        this.ticks = Math.max(0, Math.min(this.maxTicks, savedTicks));

        this.status = data.status || 'carrying';

        // Store reference for saving
        this.resource = data;

        // Determine status from ticks
        if (this.ticks >= this.maxTicks) {
            this.status = 'waiting_pick';
        } else if (this.ticks > 0) {
            this.status = 'carrying';
        } else {
            this.status = 'waiting_place';
        }

        // Load filter configuration if present
        if (data.config) {
            this.filterResourceIds = data.config.filter_resource_ids || [];
            this.maxTransferCount = data.config.max_transfer_count || null;
            this.currentTransferCount = data.config.current_transfer_count || 0;
        }
    }

    /**
     * Check if manipulator can pick a specific resource (based on filter and counter)
     */
    canPickResource(resourceId) {
        // Non-filtered manipulators can pick anything
        if (!this.isFilteredManipulator) {
            return true;
        }

        // Check filter
        if (this.filterResourceIds.length > 0) {
            if (!this.filterResourceIds.includes(resourceId)) {
                return false;
            }
        }

        // Check counter limit
        if (this.maxTransferCount !== null && this.currentTransferCount >= this.maxTransferCount) {
            return false;
        }

        return true;
    }

    /**
     * Increment transfer counter after successful transfer
     */
    onTransferComplete() {
        if (this.maxTransferCount !== null) {
            this.currentTransferCount++;
        }
    }

    /**
     * Reset transfer counter
     */
    resetCounter() {
        this.currentTransferCount = 0;
    }

    /**
     * Get data for saving
     * NEW (2026-01): Saves ticks as position_px when resource exists
     */
    getSaveData() {
        if (this.status === 'idle' && !this.resourceId) return null;

        return {
            // No entity_id - will be dict key
            resource_id: this.resourceId,
            amount: this.resourceAmount,
            position_px: this.ticks,  // Save ticks (0-30) as position_px
            status: this.status
        };
    }

    /**
     * Clear resource from manipulator
     * After clearing, manipulator returns to idle state at source position (ready to pick)
     */
    clear() {
        this.resourceId = null;
        this.resourceAmount = 0;
        this.status = 'idle';
        this.ticks = this.maxTicks;  // Return to source position (ready to pick)
        this.resource = null;
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
