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
}
