/**
 * GameSimulator - Simulates game logic without rendering
 *
 * Runs game systems (conveyors, crafting, electricity, pipes) for N ticks
 * without PixiJS rendering. Used for integration testing game logic.
 */

import { vi } from 'vitest';
import { createMockGame } from './mockGame.js';

/**
 * Create minimal game instance for simulation
 *
 * @param {Object} initialState - State from MapBuilder
 * @returns {Object} Mock game instance with managers
 */
export function createGameInstance(initialState) {
    const game = createMockGame({
        entities: initialState.entities,
        entityTypes: initialState.entityTypes,
        resources: initialState.resources,
        recipes: initialState.recipes
    });

    // Convert entities array to Map (entity_id => entity)
    game.entityData = new Map();
    for (const entity of initialState.entities) {
        game.entityData.set(entity.entity_id, entity);
    }

    // Store initial states for managers
    game.initialEntityResources = initialState.entityResources || [];
    game.initialCraftingStates = initialState.craftingStates || [];
    game.initialTransportStates = initialState.transportStates || [];
    game.initialPipeSystems = initialState.pipeSystems || {};
    game.initialElectricitySystems = initialState.electricitySystems || {};

    // Config
    game.config = {
        autoSaveInterval: 60,
        ticksPerSecond: 60,
        tileWidth: 64  // Required by TransporterState for centerPositionPx
    };

    // Grid size
    game.gridSize = 64;

    return game;
}

/**
 * Initialize game managers
 *
 * @param {Object} game - Game instance
 * @returns {Object} Initialized managers
 */
export async function initializeManagers(game) {
    // Dynamic import to avoid loading in other tests
    const { ResourceTransportManager } = await import('../../resources/js/modules/resourceTransport/ResourceTransportManager.js');
    const { ElectricitySystemManager } = await import('../../resources/js/modules/electricity/ElectricitySystemManager.js');
    const { PipeSystemManager } = await import('../../resources/js/modules/pipes/PipeSystemManager.js');

    const resourceTransport = new ResourceTransportManager(game);
    const electricityManager = new ElectricitySystemManager(game);
    const pipeManager = new PipeSystemManager(game);

    // Attach managers to game
    game.resourceTransport = resourceTransport;
    game.electricityManager = electricityManager;
    game.pipeManager = pipeManager;

    // Initialize resource transport
    resourceTransport.init();

    // Load pipe systems
    if (game.initialPipeSystems && Object.keys(game.initialPipeSystems).length > 0) {
        pipeManager.loadSystems(game.initialPipeSystems);
    }

    return {
        resourceTransport,
        electricityManager,
        pipeManager
    };
}

/**
 * Run simulation for N ticks
 *
 * @param {Object} game - Game instance with managers
 * @param {number} ticks - Number of ticks to simulate
 * @param {Object} options - Simulation options
 * @returns {Object} Final game state
 */
export function runSimulation(game, ticks, options = {}) {
    const {
        logTicks = false,
        tickCallback = null
    } = options;

    for (let i = 0; i < ticks; i++) {
        // Run resource transport tick (conveyors, manipulators, crafting)
        if (game.resourceTransport) {
            game.resourceTransport.tick();
        }

        // Optional callback after each tick
        if (tickCallback) {
            tickCallback(i, game);
        }

        if (logTicks && (i % 10 === 0 || i === ticks - 1)) {
            console.log(`[Tick ${i + 1}/${ticks}]`);
        }
    }

    return extractGameState(game);
}

/**
 * Extract current game state after simulation
 *
 * @param {Object} game - Game instance
 * @returns {Object} Current state
 */
export function extractGameState(game) {
    const state = {
        entities: Array.from(game.entityData.values()),
        entityResources: [],
        craftingStates: [],
        transportStates: []
    };

    // Extract entity resources from buildings
    if (game.resourceTransport) {
        for (const [entityId, buildingState] of game.resourceTransport.buildings) {
            for (const [resourceId, amount] of buildingState.resources.entries()) {
                if (amount > 0) {
                    state.entityResources.push({
                        entity_id: entityId,
                        resource_id: resourceId,
                        amount: amount
                    });
                }
            }
        }

        // Extract crafting states
        for (const [entityId, buildingState] of game.resourceTransport.buildings) {
            if (buildingState.craftingRecipeId) {
                state.craftingStates.push({
                    entity_id: entityId,
                    recipe_id: buildingState.craftingRecipeId,
                    ticks_remaining: buildingState.craftingTicksRemaining
                });
            }

            // Note: No need to extract crafting state if not crafting
            // This allows us to verify crafting completed (null state)
        }

        // Extract transport states (conveyors, manipulators)
        for (const [entityId, transporterState] of game.resourceTransport.transporters) {
            // Transporter has a single resource (not items array)
            if (transporterState.resourceId) {
                state.entityResources.push({
                    entity_id: entityId,
                    resource_id: transporterState.resourceId,
                    amount: transporterState.resourceAmount || 1
                });
            }
        }

        for (const [entityId, manipulatorState] of game.resourceTransport.manipulators) {
            if (manipulatorState.heldItem) {
                state.transportStates.push({
                    entity_id: entityId,
                    held_item: {
                        resource_id: manipulatorState.heldItem.resourceId,
                        progress: manipulatorState.swingProgress
                    }
                });
            }
        }
    }

    return state;
}

/**
 * Assert entity has resources
 *
 * @param {Object} state - Game state
 * @param {number} entityId - Entity ID
 * @param {Object} expectedResources - Expected resources { resourceId: amount }
 */
export function assertEntityResources(state, entityId, expectedResources) {
    const entityResources = state.entityResources.filter(er => er.entity_id === entityId);

    for (const [resourceId, expectedAmount] of Object.entries(expectedResources)) {
        const rid = parseInt(resourceId);
        const resource = entityResources.find(er => er.resource_id === rid);

        if (!resource) {
            throw new Error(
                `Entity ${entityId} does not have resource ${rid}. ` +
                `Available: ${entityResources.map(er => `${er.resource_id}:${er.amount}`).join(', ')}`
            );
        }

        if (resource.amount !== expectedAmount) {
            throw new Error(
                `Entity ${entityId} has ${resource.amount} of resource ${rid}, ` +
                `expected ${expectedAmount}`
            );
        }
    }
}

/**
 * Assert entity is crafting
 *
 * @param {Object} state - Game state
 * @param {number} entityId - Entity ID
 * @param {number|null} expectedRecipeId - Expected recipe ID (null = not crafting)
 */
export function assertEntityCrafting(state, entityId, expectedRecipeId) {
    const craftingState = state.craftingStates.find(cs => cs.entity_id === entityId);

    if (expectedRecipeId === null) {
        if (craftingState) {
            throw new Error(
                `Entity ${entityId} is crafting recipe ${craftingState.recipe_id}, ` +
                `expected to not be crafting`
            );
        }
    } else {
        if (!craftingState) {
            throw new Error(
                `Entity ${entityId} is not crafting, expected recipe ${expectedRecipeId}`
            );
        }

        if (craftingState.recipe_id !== expectedRecipeId) {
            throw new Error(
                `Entity ${entityId} is crafting recipe ${craftingState.recipe_id}, ` +
                `expected ${expectedRecipeId}`
            );
        }
    }
}

/**
 * Get entity resources as object
 *
 * @param {Object} state - Game state
 * @param {number} entityId - Entity ID
 * @returns {Object} Resources { resourceId: amount }
 */
export function getEntityResources(state, entityId) {
    const result = {};
    const entityResources = state.entityResources.filter(er => er.entity_id === entityId);

    for (const er of entityResources) {
        result[er.resource_id] = er.amount;
    }

    return result;
}

/**
 * Print game state (for debugging)
 *
 * @param {Object} state - Game state
 * @param {Object} game - Game instance
 */
export function printGameState(state, game) {
    console.log('\n=== Game State ===');
    console.log(`Entities: ${state.entities.length}`);

    for (const entity of state.entities) {
        const entityType = game.entityTypes[entity.entity_type_id];
        const resources = state.entityResources.filter(er => er.entity_id === entity.entity_id);
        const crafting = state.craftingStates.find(cs => cs.entity_id === entity.entity_id);

        console.log(
            `  Entity ${entity.entity_id}: ${entityType.name} at (${entity.x}, ${entity.y})`
        );

        if (resources.length > 0) {
            console.log(
                `    Resources: ${resources.map(r => `${game.resources[r.resource_id].name}:${r.amount}`).join(', ')}`
            );
        }

        if (crafting) {
            console.log(
                `    Crafting: Recipe ${crafting.recipe_id}, ${crafting.ticks_remaining} ticks remaining`
            );
        }
    }

    console.log('==================\n');
}
