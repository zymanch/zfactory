/**
 * Manages fluid pipe systems on the client side
 * Handles system data, lookups, and provides info for tooltips
 */
export class PipeSystemManager {
    constructor(game) {
        this.game = game;
        this.systems = new Map(); // pipe_system_id => system data
        this.entityToSystem = new Map(); // entity_id => pipe_system_id
    }

    /**
     * Load systems data from server
     * @param {Object} systemsData - Object with pipe_system_id as keys
     */
    loadSystems(systemsData) {
        this.systems.clear();
        this.entityToSystem.clear();

        for (const systemId in systemsData) {
            const system = systemsData[systemId];
            this.systems.set(parseInt(systemId), system);

            // Map each entity to its system
            for (const entityId of system.entity_ids) {
                this.entityToSystem.set(entityId, parseInt(systemId));
            }
        }

        console.log(`[PipeSystemManager] Loaded ${this.systems.size} pipe systems`);
    }

    /**
     * Get system for specific entity
     * @param {number} entityId
     * @returns {Object|null}
     */
    getSystemForEntity(entityId) {
        const systemId = this.entityToSystem.get(entityId);
        return systemId ? this.systems.get(systemId) : null;
    }

    /**
     * Get system information for tooltip
     * @param {number} entityId
     * @returns {Object|null}
     */
    getSystemInfo(entityId) {
        const system = this.getSystemForEntity(entityId);
        if (!system) return null;

        const resource = system.resource_id ? this.game.resources[system.resource_id] : null;
        const fillPercent = system.max_capacity > 0
            ? Math.round((system.current_amount / system.max_capacity) * 100)
            : 0;

        return {
            resourceName: resource ? resource.name : 'Empty',
            resourceId: system.resource_id,
            currentAmount: system.current_amount,
            maxCapacity: system.max_capacity,
            fillPercent: fillPercent,
        };
    }

    /**
     * Check if entity is a pipe
     * @param {number} entityTypeId
     * @returns {boolean}
     */
    isPipeEntity(entityTypeId) {
        // Pipes: 131, 132, 135, 136, 140, 141
        return [131, 132, 135, 136, 140, 141].includes(entityTypeId);
    }

    /**
     * Get fluid color for resource ID
     * @param {number} resourceId
     * @returns {number} PIXI color
     */
    getFluidColor(resourceId) {
        const colors = {
            300: 0x3498db, // Water - blue
            301: 0x2c3e50, // Crude Oil - dark
            302: 0x95a5a6, // Natural Gas - gray
            303: 0xe74c3c, // Lava - red/orange
        };
        return colors[resourceId] || 0xffffff;
    }

    /**
     * Add fluid to pipe system (client-side validation only)
     * @param {number} pipeEntityId - Any pipe entity in the system
     * @param {number} resourceId - Fluid resource ID (300-303)
     * @param {number} amount - Amount to add
     * @returns {boolean} - Success
     */
    addFluid(pipeEntityId, resourceId, amount) {
        const system = this.getSystemForEntity(pipeEntityId);
        if (!system) {
            console.warn(`[PipeSystemManager] No system found for pipe entity ${pipeEntityId}`);
            return false;
        }

        // Check for mixing (different fluid already in system)
        if (system.resource_id && system.resource_id !== resourceId) {
            console.warn(`[PipeSystemManager] Cannot mix fluids: system has ${system.resource_id}, trying to add ${resourceId}`);
            return false;
        }

        // Check capacity
        if (system.current_amount + amount > system.max_capacity) {
            console.warn(`[PipeSystemManager] System overflow: ${system.current_amount} + ${amount} > ${system.max_capacity}`);
            return false;
        }

        // Update local state (server will sync back)
        system.current_amount += amount;
        if (!system.resource_id) {
            system.resource_id = resourceId;
        }

        console.log(`[PipeSystemManager] Added ${amount} of resource ${resourceId} to system (now: ${system.current_amount}/${system.max_capacity})`);

        // TODO: Send update to server
        // this.game.sendPipeSystemUpdate(system.pipe_system_id);

        return true;
    }

    /**
     * Take fluid from pipe system (client-side validation only)
     * @param {number} pipeEntityId - Any pipe entity in the system
     * @param {number} resourceId - Fluid resource ID (300-303)
     * @param {number} amount - Amount to take
     * @returns {number} - Actual amount taken
     */
    takeFluid(pipeEntityId, resourceId, amount) {
        const system = this.getSystemForEntity(pipeEntityId);
        if (!system) {
            console.warn(`[PipeSystemManager] No system found for pipe entity ${pipeEntityId}`);
            return 0;
        }

        // Check if system has this fluid
        if (system.resource_id !== resourceId) {
            console.warn(`[PipeSystemManager] Wrong fluid: system has ${system.resource_id}, trying to take ${resourceId}`);
            return 0;
        }

        // Take what's available
        const actualAmount = Math.min(amount, system.current_amount);
        system.current_amount -= actualAmount;

        // Clear resource_id if empty
        if (system.current_amount === 0) {
            system.resource_id = null;
        }

        console.log(`[PipeSystemManager] Took ${actualAmount} of resource ${resourceId} from system (remaining: ${system.current_amount}/${system.max_capacity})`);

        // TODO: Send update to server
        // this.game.sendPipeSystemUpdate(system.pipe_system_id);

        return actualAmount;
    }

    /**
     * Consume fluid from pipe system by system ID
     * Used by crafting system to consume fluids during production
     * @param {number} systemId - Pipe system ID
     * @param {number} amount - Amount to consume
     * @returns {boolean} - Success
     */
    consumeFluid(systemId, amount) {
        const system = this.systems.get(systemId);
        if (!system) {
            console.warn(`[PipeSystemManager] No system found with ID ${systemId}`);
            return false;
        }

        if (system.current_amount < amount) {
            console.warn(`[PipeSystemManager] Not enough fluid: has ${system.current_amount}, needs ${amount}`);
            return false;
        }

        // Consume fluid
        system.current_amount -= amount;

        // Clear resource_id if empty
        if (system.current_amount === 0) {
            system.resource_id = null;
        }

        console.log(`[PipeSystemManager] Consumed ${amount} fluid from system ${systemId} (remaining: ${system.current_amount}/${system.max_capacity})`);

        // TODO: Send update to server
        // this.game.sendPipeSystemUpdate(systemId);

        return true;
    }
}
