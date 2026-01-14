/**
 * Test Fixtures
 *
 * Common test data for various game entities and systems.
 */

/**
 * Entity Type Fixtures
 */
export const entityTypeFixtures = {
    conveyor: {
        entity_type_id: 100,
        type: 'conveyor',
        name: 'Conveyor',
        folder: 'conveyor',
        max_durability: 100,
        width: 1,
        height: 1,
        power: 0,
        costs: { 2: 5 }, // Iron plate: 5
        recipes: [],
        behavior: {
            behaviorClass: 'ConveyorBehavior',
            checksFog: true,
            checksLanding: true,
            checksCollision: true,
            requiresTarget: false
        }
    },

    furnace: {
        entity_type_id: 101,
        type: 'building',
        name: 'Small Furnace',
        folder: 'small_furnace',
        max_durability: 150,
        width: 2,
        height: 2,
        power: 100,
        costs: { 2: 10, 5: 5 }, // Iron: 10, Stone: 5
        recipes: [1, 2, 3],
        behavior: {
            behaviorClass: 'CraftingBehavior',
            checksFog: true,
            checksLanding: true,
            checksCollision: true,
            requiresTarget: false
        }
    },

    miningDrill: {
        entity_type_id: 102,
        type: 'mining',
        name: 'Mining Drill',
        folder: 'mining_drill',
        max_durability: 200,
        width: 2,
        height: 2,
        power: 150,
        costs: { 2: 15, 3: 5 }, // Iron: 15, Copper: 5
        recipes: [],
        behavior: {
            behaviorClass: 'MiningBehavior',
            checksFog: true,
            checksLanding: false,
            checksCollision: true,
            requiresTarget: true
        }
    },

    pipe: {
        entity_type_id: 200,
        type: 'pipe',
        name: 'Pipe',
        folder: 'pipe',
        max_durability: 100,
        width: 1,
        height: 1,
        power: 0,
        costs: { 2: 3 }, // Iron: 3
        recipes: [],
        behavior: {
            behaviorClass: 'PipeBehavior',
            checksFog: true,
            checksLanding: true,
            checksCollision: true,
            requiresTarget: false
        }
    },

    powerPole: {
        entity_type_id: 300,
        type: 'electricity',
        name: 'Power Pole',
        folder: 'power_pole',
        max_durability: 100,
        width: 1,
        height: 1,
        power: 0,
        costs: { 2: 2, 4: 2 }, // Iron: 2, Copper wire: 2
        recipes: [],
        behavior: {
            behaviorClass: 'ElectricityBehavior',
            checksFog: true,
            checksLanding: true,
            checksCollision: true,
            requiresTarget: false
        }
    }
};

/**
 * Resource Fixtures
 */
export const resourceFixtures = {
    ironOre: { resource_id: 1, name: 'Iron Ore', icon: 'iron_ore.png' },
    ironPlate: { resource_id: 2, name: 'Iron Plate', icon: 'iron_plate.png' },
    copperOre: { resource_id: 3, name: 'Copper Ore', icon: 'copper_ore.png' },
    copperPlate: { resource_id: 4, name: 'Copper Plate', icon: 'copper_plate.png' },
    stone: { resource_id: 5, name: 'Stone', icon: 'stone.png' },
    water: { resource_id: 20, name: 'Water', icon: 'water.png' }
};

/**
 * Recipe Fixtures
 */
export const recipeFixtures = {
    ironSmelting: {
        recipe_id: 1,
        name: 'Iron Smelting',
        ticks: 30,
        inputs: [{ resource_id: 1, amount: 1 }], // Iron Ore
        outputs: [{ resource_id: 2, amount: 1 }] // Iron Plate
    },

    copperSmelting: {
        recipe_id: 2,
        name: 'Copper Smelting',
        ticks: 30,
        inputs: [{ resource_id: 3, amount: 1 }], // Copper Ore
        outputs: [{ resource_id: 4, amount: 1 }] // Copper Plate
    }
};

/**
 * Entity Fixtures
 */
export const entityFixtures = {
    conveyor1: {
        entity_id: 1,
        entity_type_id: 100,
        state: 'built',
        durability: 100,
        x: 320,
        y: 240
    },

    furnace1: {
        entity_id: 10,
        entity_type_id: 101,
        state: 'built',
        durability: 150,
        x: 640,
        y: 480
    },

    drill1: {
        entity_id: 20,
        entity_type_id: 102,
        state: 'built',
        durability: 200,
        x: 960,
        y: 720
    }
};

/**
 * System Fixtures
 */
export const systemFixtures = {
    electricitySystem: {
        system_id: 1,
        total_electricity: 100,
        entity_ids: [10, 11, 12]
    },

    pipeSystem: {
        pipe_system_id: 1,
        resource_id: 20,
        current_amount: 50,
        max_capacity: 100,
        entity_ids: [15, 16, 17, 18]
    }
};

/**
 * Crafting State Fixtures
 */
export const craftingStateFixtures = {
    active: {
        entity_id: 10,
        recipe_id: 1,
        ticks_remaining: 15
    },

    completed: {
        entity_id: 10,
        recipe_id: 1,
        ticks_remaining: 0
    }
};
