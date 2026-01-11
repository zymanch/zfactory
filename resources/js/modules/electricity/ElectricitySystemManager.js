/**
 * Manages electricity systems on the client side
 * Networks are calculated client-side using BFS algorithm
 * Electricity is stored in entity_resource (like normal resources)
 */
export class ElectricitySystemManager {
    constructor(game) {
        this.game = game;
        this.networkCache = new Map(); // entityId => Set<entityId>
    }

    /**
     * Find all electricity entities connected to given entity via BFS
     * @param {number} entityId - Starting entity
     * @returns {Set<number>} Set of connected entity IDs
     */
    findConnectedEntities(entityId) {
        const connected = new Set();
        const queue = [entityId];
        connected.add(entityId);

        // Safety check
        if (!this.game || !this.game.entityData) {
            console.warn('[ElectricitySystemManager] entityData not available');
            return connected;
        }

        while (queue.length > 0) {
            const currentId = queue.shift();
            const currentEntity = this.getEntityData(currentId);
            if (!currentEntity) continue;

            const entityType = this.game.entityTypes[currentEntity.entity_type_id];
            if (!entityType) continue;

            const role = this.getElectricityRole(currentEntity.entity_type_id);
            if (!role) continue;

            const radius = this.getPowerRadius(currentEntity.entity_type_id);

            // Find all electricity entities within radius
            for (const [key, otherEntity] of this.game.entityData.entries()) {
                const otherId = otherEntity.entity_id;

                if (connected.has(otherId)) continue;
                if (otherEntity.state !== 'built') continue;

                const otherRole = this.getElectricityRole(otherEntity.entity_type_id);
                if (!otherRole) continue;

                const dx = otherEntity.x - currentEntity.x;
                const dy = otherEntity.y - currentEntity.y;
                const distSq = dx * dx + dy * dy;

                // Check connection in BOTH directions:
                // 1. Can current entity reach other? (current has radius)
                // 2. Can other entity reach current? (other has radius)
                let isConnected = false;

                // Check if current entity can reach other
                if (radius > 0) {
                    const radiusSq = radius * radius;
                    if (distSq <= radiusSq) {
                        isConnected = true;
                    }
                }

                // Check if other entity can reach current
                if (!isConnected) {
                    const otherRadius = this.getPowerRadius(otherEntity.entity_type_id);
                    if (otherRadius > 0) {
                        const otherRadiusSq = otherRadius * otherRadius;
                        if (distSq <= otherRadiusSq) {
                            isConnected = true;
                        }
                    }
                }

                if (isConnected) {
                    connected.add(otherId);
                    queue.push(otherId);
                }
            }
        }

        return connected;
    }

    /**
     * Get entity data by ID
     * @param {number} entityId
     * @returns {Object|null}
     */
    getEntityData(entityId) {
        if (!this.game || !this.game.entityData) {
            return null;
        }
        const key = `entity_${entityId}`;
        return this.game.entityData.get(key);
    }

    /**
     * Invalidate network cache when entities change
     */
    invalidateNetworkCache() {
        this.networkCache.clear();
    }

    /**
     * Get cached or calculate network
     * @param {number} entityId
     * @returns {Set<number>}
     */
    getNetwork(entityId) {
        if (this.networkCache.has(entityId)) {
            return this.networkCache.get(entityId);
        }

        const network = this.findConnectedEntities(entityId);

        // Cache for all entities in network
        for (const id of network) {
            this.networkCache.set(id, network);
        }


        return network;
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
     * @returns {number} Radius in pixels
     */
    getPowerRadius(entityTypeId) {
        const entityType = this.game.entityTypes[entityTypeId];
        if (!entityType) return 0;

        const role = this.getElectricityRole(entityTypeId);
        if (role === 'pylon') {
            // Convert tiles to pixels (1 tile = 64px)
            return (parseInt(entityType.power) || 0) * 64;
        }
        return 0;
    }

    /**
     * Check if entity has enough electricity in its network
     * @param {number} entityId - Entity ID
     * @param {number} amount - Amount needed
     * @returns {boolean}
     */
    hasElectricity(entityId, amount = 1) {
        // Get network for this entity
        const network = this.getNetwork(entityId);

        let totalElectricity = 0;

        // Sum electricity from all batteries and generators in network
        for (const connectedId of network) {
            const entityData = this.getEntityData(connectedId);
            if (!entityData) continue;

            const entityTypeId = entityData.entity_type_id;
            const role = this.getElectricityRole(entityTypeId);

            // Only batteries and generators can store electricity
            if (role === 'battery' || role === 'generator') {
                const buildingState = this.game.resourceTransportManager?.buildings.get(connectedId);
                if (buildingState) {
                    totalElectricity += buildingState.getResourceAmount(400); // resource_id=400 is electricity
                }
            }
        }

        return totalElectricity >= amount;
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
