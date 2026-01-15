/**
 * Manages fluid pipe systems on the client side
 * NEW (2026-01): Systems calculated locally using BFS instead of loading from server
 * NEW (2026-01): Priority-based fluid distribution: Tanks → Buildings → Mining
 */
export class PipeSystemManager {
    constructor(game) {
        this.game = game;
        this.systems = new Map(); // LOCAL pipe_system_id => system data
        this.entityToSystem = new Map(); // entity_id => LOCAL pipe_system_id
        this.nextSystemId = 1; // Local ID counter

        // Pipe entity type IDs
        this.PIPE_TYPE_IDS = [131, 132, 135, 136, 140, 141];
    }

    /**
     * NEW: Calculate pipe systems locally using BFS
     * Called on game load and when pipes are added/removed
     */
    calculateSystems() {
        this.systems.clear();
        this.entityToSystem.clear();
        this.nextSystemId = 1;

        const processed = new Set();
        const pipeEntityIds = this.getAllPipeEntityIds();

        for (const pipeId of pipeEntityIds) {
            if (processed.has(pipeId)) continue;

            const systemMembers = this.findConnectedPipes(pipeId, processed);
            this.createLocalSystem(systemMembers);
        }

        console.log(`[PipeSystemManager] Calculated ${this.systems.size} pipe systems locally`);
    }

    /**
     * Get all pipe entity IDs from game
     */
    getAllPipeEntityIds() {
        const pipeIds = [];
        for (const [key, entity] of this.game.entityData) {
            if (this.PIPE_TYPE_IDS.includes(entity.entity_type_id)) {
                pipeIds.push(entity.entity_id);
            }
        }
        return pipeIds;
    }

    /**
     * BFS: Find all connected pipes starting from a pipe
     * @param {number} startPipeId
     * @param {Set} processed - Already processed pipes
     * @returns {Array<number>} - Connected pipe entity IDs
     */
    findConnectedPipes(startPipeId, processed) {
        const queue = [startPipeId];
        const system = [];
        processed.add(startPipeId);

        while (queue.length > 0) {
            const pipeId = queue.shift();
            system.push(pipeId);

            // Find neighbors in 4 directions
            const neighbors = this.getNeighborPipes(pipeId);
            for (const neighborId of neighbors) {
                if (!processed.has(neighborId)) {
                    processed.add(neighborId);
                    queue.push(neighborId);
                }
            }
        }

        return system;
    }

    /**
     * Find neighbor pipes (4 directions: up, down, left, right)
     * @param {number} pipeId
     * @returns {Array<number>} - Neighbor pipe entity IDs
     */
    getNeighborPipes(pipeId) {
        const entity = this.game.entityData.get(`entity_${pipeId}`);
        if (!entity) return [];

        const neighbors = [];
        const tileSize = this.game.config.tileWidth || 64;

        const directions = [
            { dx: 0, dy: -tileSize }, // up
            { dx: 0, dy: tileSize },  // down
            { dx: -tileSize, dy: 0 }, // left
            { dx: tileSize, dy: 0 }   // right
        ];

        for (const dir of directions) {
            const x = entity.x + dir.dx;
            const y = entity.y + dir.dy;

            // Use spatialIndex if available
            let neighborId;
            if (this.game.resourceTransportManager?.spatialIndex) {
                neighborId = this.game.resourceTransportManager.spatialIndex.getAt(x, y);
            } else {
                // Fallback: search manually
                for (const [key, e] of this.game.entityData) {
                    if (e.x === x && e.y === y) {
                        neighborId = e.entity_id;
                        break;
                    }
                }
            }

            if (neighborId) {
                const neighborEntity = this.game.entityData.get(`entity_${neighborId}`);
                if (neighborEntity && this.PIPE_TYPE_IDS.includes(neighborEntity.entity_type_id)) {
                    neighbors.push(neighborId);
                }
            }
        }

        return neighbors;
    }

    /**
     * Create local pipe system from member IDs
     * @param {Array<number>} memberIds - Pipe entity IDs
     */
    createLocalSystem(memberIds) {
        const systemId = this.nextSystemId++;

        // Calculate total capacity (sum of all pipe powers)
        let maxCapacity = 0;
        for (const memberId of memberIds) {
            const entity = this.game.entityData.get(`entity_${memberId}`);
            if (entity) {
                const entityType = this.game.entityTypes[entity.entity_type_id];
                maxCapacity += parseInt(entityType?.power) || 0;
            }
        }

        // Calculate current amount from entity resources
        let resourceId = null;
        let currentAmount = 0;

        for (const memberId of memberIds) {
            const entity = this.game.entityData.get(`entity_${memberId}`);
            const resources = entity?.resources || [];

            for (const res of resources) {
                if (this.isFluidResource(res.resource_id)) {
                    resourceId = res.resource_id;
                    currentAmount += res.amount;
                }
            }
        }

        const system = {
            pipe_system_id: systemId, // LOCAL ID
            entity_ids: memberIds,
            resource_id: resourceId,
            current_amount: currentAmount,
            max_capacity: maxCapacity
        };

        this.systems.set(systemId, system);

        // Map entities to system
        for (const memberId of memberIds) {
            this.entityToSystem.set(memberId, systemId);
        }
    }

    /**
     * Check if resource is fluid (type='liquid')
     * @param {number} resourceId
     * @returns {boolean}
     */
    isFluidResource(resourceId) {
        const resource = this.game.resources[resourceId];
        return resource && resource.type === 'liquid';
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
        return this.PIPE_TYPE_IDS.includes(entityTypeId);
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
     * Add fluid to pipe system
     * NEW (2026-01): Distributes fluid with priority: Tanks → Buildings → Mining
     * @param {number} pipeEntityId - Any pipe entity in the system
     * @param {number} resourceId - Fluid resource ID (300-303)
     * @param {number} amount - Amount to add
     * @returns {boolean} - Success
     */
    addFluid(pipeEntityId, resourceId, amount) {
        const systemId = this.entityToSystem.get(pipeEntityId);
        if (!systemId) {
            console.warn(`[PipeSystemManager] No system found for pipe entity ${pipeEntityId}`);
            return false;
        }

        const system = this.systems.get(systemId);

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

        // Update local state
        system.current_amount += amount;
        if (!system.resource_id) {
            system.resource_id = resourceId;
        }

        // NEW: Distribute to entities with priority
        this.distributeFluidToEntities(system, resourceId, amount);

        console.log(`[PipeSystemManager] Added ${amount} of resource ${resourceId} to system (now: ${system.current_amount}/${system.max_capacity})`);

        return true;
    }

    /**
     * NEW: Distribute fluid to entities with priority
     * Priority: Tanks (135, 136) → Buildings (type='building') → Mining (type='mining')
     * @param {Object} system
     * @param {number} resourceId
     * @param {number} amount
     */
    distributeFluidToEntities(system, resourceId, amount) {
        const entities = this.getEntitiesInSystem(system);

        // Separate by priority
        const tanks = entities.filter(e => [135, 136, 140, 141].includes(e.entity_type_id));
        const buildings = entities.filter(e => {
            const type = this.game.entityTypes[e.entity_type_id];
            return type && type.type === 'building';
        });
        const mining = entities.filter(e => {
            const type = this.game.entityTypes[e.entity_type_id];
            return type && type.type === 'mining';
        });

        // Priority list
        const prioritized = [...tanks, ...buildings, ...mining];

        let remaining = amount;
        for (const entity of prioritized) {
            if (remaining <= 0) break;

            const capacity = this.getEntityFluidCapacity(entity);
            const current = this.getEntityFluidAmount(entity, resourceId);
            const free = capacity - current;

            if (free > 0) {
                const toAdd = Math.min(remaining, free);
                this.addFluidToEntity(entity.entity_id, resourceId, toAdd);
                remaining -= toAdd;
            }
        }

        if (remaining > 0) {
            console.log(`[PipeSystemManager] Could not distribute ${remaining} units (no capacity)`);
        }
    }

    /**
     * Get entities in system (both pipes and connected buildings)
     * @param {Object} system
     * @returns {Array}
     */
    getEntitiesInSystem(system) {
        const entities = [];
        for (const entityId of system.entity_ids) {
            const entity = this.game.entityData.get(`entity_${entityId}`);
            if (entity) entities.push(entity);
        }
        return entities;
    }

    /**
     * Get entity fluid capacity
     * @param {Object} entity
     * @returns {number}
     */
    getEntityFluidCapacity(entity) {
        const entityType = this.game.entityTypes[entity.entity_type_id];
        return parseInt(entityType?.storage_per_resource) || 0;
    }

    /**
     * Get entity fluid amount
     * @param {Object} entity
     * @param {number} resourceId
     * @returns {number}
     */
    getEntityFluidAmount(entity, resourceId) {
        const resources = entity.resources || [];
        const res = resources.find(r => r.resource_id === resourceId);
        return res ? res.amount : 0;
    }

    /**
     * Add fluid to entity (update local state)
     * @param {number} entityId
     * @param {number} resourceId
     * @param {number} amount
     */
    addFluidToEntity(entityId, resourceId, amount) {
        const entity = this.game.entityData.get(`entity_${entityId}`);
        if (!entity) return;

        if (!entity.resources) entity.resources = [];

        const existing = entity.resources.find(r => r.resource_id === resourceId);
        if (existing) {
            existing.amount += amount;
        } else {
            entity.resources.push({ resource_id: resourceId, amount });
        }
    }

    /**
     * Take fluid from pipe system
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

        return true;
    }
}
