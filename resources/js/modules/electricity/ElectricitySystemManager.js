/**
 * Manages electricity systems on the client side
 * Handles system data, lookups, and provides info for tooltips
 */
export class ElectricitySystemManager {
    constructor(game) {
        this.game = game;
        this.systems = new Map(); // system_id => system data
        this.entityToSystem = new Map(); // entity_id => system_id
    }

    /**
     * Load systems data from server
     * @param {Object} systemsData - Object with system_id as keys
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

        console.log(`[ElectricitySystemManager] Loaded ${this.systems.size} electricity systems`);
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

        const fillPercent = system.total_capacity > 0
            ? Math.round((system.total_electricity / system.total_capacity) * 100)
            : 0;

        return {
            totalElectricity: system.total_electricity,
            totalCapacity: system.total_capacity,
            fillPercent: fillPercent,
            isPowered: system.total_electricity > 0,
        };
    }

    /**
     * Check if entity has enough electricity
     * @param {number} entityId
     * @param {number} amount - Amount needed (default 1)
     * @returns {boolean}
     */
    hasElectricity(entityId, amount = 1) {
        const system = this.getSystemForEntity(entityId);
        if (!system) return false;

        return system.total_electricity >= amount;
    }

    /**
     * Update system electricity amount (client-side state)
     * @param {number} systemId
     * @param {number} newAmount
     */
    updateSystemElectricity(systemId, newAmount) {
        const system = this.systems.get(systemId);
        if (!system) {
            console.warn(`[ElectricitySystemManager] System ${systemId} not found`);
            return;
        }

        system.total_electricity = Math.max(0, Math.min(newAmount, system.total_capacity));
    }

    /**
     * Check if entity is an electricity entity
     * @param {number} entityTypeId
     * @returns {boolean}
     */
    isElectricityEntity(entityTypeId) {
        // Pylons: 900-902, Batteries: 910-912, Generators: 920-922
        return (entityTypeId >= 900 && entityTypeId <= 902) ||
               (entityTypeId >= 910 && entityTypeId <= 912) ||
               (entityTypeId >= 920 && entityTypeId <= 922);
    }

    /**
     * Get electricity entity role
     * @param {number} entityTypeId
     * @returns {string|null} 'pylon'|'battery'|'generator'|null
     */
    getElectricityRole(entityTypeId) {
        if (entityTypeId >= 900 && entityTypeId <= 902) return 'pylon';
        if (entityTypeId >= 910 && entityTypeId <= 912) return 'battery';
        if (entityTypeId >= 920 && entityTypeId <= 922) return 'generator';
        return null;
    }

    /**
     * Get power radius for entity type
     * @param {number} entityTypeId
     * @returns {number}
     */
    getPowerRadius(entityTypeId) {
        const entityType = this.game.entityTypes[entityTypeId];
        if (!entityType) return 0;

        const role = this.getElectricityRole(entityTypeId);
        if (role === 'pylon') {
            return parseInt(entityType.power) || 0;
        }
        return 0;
    }

    /**
     * Check if coordinate is electrified (within any pylon's radius)
     * @param {number} x - World X coordinate
     * @param {number} y - World Y coordinate
     * @returns {boolean}
     */
    isCoordinateElectrified(x, y) {
        // Find all built electricity entities
        for (const [entityId, entityData] of this.game.entityData) {
            const entity = this.game.loadedEntities.get(entityId);
            if (!entity || entity.state !== 'built') continue;

            const entityType = this.game.entityTypes[entity.entity_type_id];
            if (!entityType) continue;

            const role = this.getElectricityRole(entity.entity_type_id);
            if (!role) continue;

            // Get power radius
            const radius = this.getPowerRadius(entity.entity_type_id);
            if (radius <= 0) continue;

            // Calculate distance
            const dx = x - entity.x;
            const dy = y - entity.y;
            const distanceSq = dx * dx + dy * dy;
            const radiusSq = radius * radius;

            if (distanceSq <= radiusSq) {
                return true;
            }
        }

        return false;
    }
}
