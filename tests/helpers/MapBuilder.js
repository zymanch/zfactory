/**
 * MapBuilder - Converts ASCII maps to game entities
 *
 * Parses ASCII map representation and creates entities with initial state.
 * Used in integration tests for setting up complex game scenarios.
 */

/**
 * Parse ASCII map and create entities
 *
 * @param {string[]} mapLines - Array of map lines (e.g., ['###1##', '#122##'])
 * @param {Object} legend - Mapping of symbols to entity configuration
 * @param {Object} recipes - Optional recipes to embed in test (overrides game recipes)
 * @returns {Object} Game state with entities, entityTypes, resources, recipes
 *
 * @example
 * const state = MapBuilder.build(
 *   ['###1##', '#122##', '#3345#'],
 *   {
 *     '1': { type: 'conveyor_down', resources: [{ id: 1, amount: 1 }] },
 *     '2': { type: 'conveyor_left' },
 *     '3': { type: 'conveyor_right' },
 *     '4': { type: 'manipulator_right' },
 *     '5': { type: 'furnace', recipe: 'iron_smelting' }
 *   },
 *   {
 *     iron_smelting: {
 *       inputs: [{ id: 1, amount: 1 }],
 *       outputs: [{ id: 2, amount: 1 }],
 *       ticks: 30
 *     }
 *   }
 * );
 */
export class MapBuilder {
    /**
     * Build game state from ASCII map
     *
     * @param {string[]} mapLines - ASCII map lines
     * @param {Object} legend - Symbol to entity configuration mapping
     * @param {Object} recipes - Optional embedded recipes
     * @returns {Object} Game state
     */
    static build(mapLines, legend, recipes = null) {
        const state = {
            entities: [],
            entityTypes: {},
            resources: this.getDefaultResources(),
            recipes: recipes || this.getDefaultRecipes(),
            entityResources: [],
            craftingStates: [],
            transportStates: [],
            pipeSystems: {},
            electricitySystems: {}
        };

        let entityId = 1;
        // NOTE: getNextPosition uses distance=1, so entities must be 1 pixel apart!
        const gridSize = 1;

        // Parse map
        for (let y = 0; y < mapLines.length; y++) {
            const line = mapLines[y];
            for (let x = 0; x < line.length; x++) {
                const symbol = line[x];

                // Skip empty cells
                if (symbol === '#' || symbol === ' ') continue;

                // Get entity configuration from legend
                const config = legend[symbol];
                if (!config) {
                    throw new Error(`Unknown symbol "${symbol}" at position (${x}, ${y}). Not found in legend.`);
                }

                // Create entity
                const entity = this.createEntity(
                    entityId,
                    x * gridSize,
                    y * gridSize,
                    config
                );

                state.entities.push(entity);

                // Add entity type if not exists
                // Store resolved recipe IDs for this symbol
                let symbolRecipeIds = null;
                if (!state.entityTypes[entity.entity_type_id]) {
                    const entityTypeData = this.getEntityType(config, state);
                    state.entityTypes[entity.entity_type_id] = entityTypeData;
                    symbolRecipeIds = entityTypeData.recipes;
                } else {
                    symbolRecipeIds = state.entityTypes[entity.entity_type_id].recipes;
                }

                // Add entity resources if specified
                if (config.resources && config.resources.length > 0) {
                    // Check if this is a transporter/conveyor (needs transport states)
                    const isTransporter = entity.entity_type_id >= 100 && entity.entity_type_id <= 130;

                    if (isTransporter) {
                        // For conveyors/transporters, create transport states
                        for (const res of config.resources) {
                            state.transportStates.push({
                                entity_id: entityId,
                                resource_id: res.id,
                                amount: res.amount || 1,
                                position_px: res.position_px || 0,  // Position on belt
                                from_direction: res.from_direction || 'down',
                                status: 'carrying'
                            });
                        }
                    } else {
                        // For buildings, add to entityResources
                        for (const res of config.resources) {
                            state.entityResources.push({
                                entity_id: entityId,
                                resource_id: res.id,
                                amount: res.amount
                            });
                        }
                    }
                }

                // Add crafting state if recipe specified
                if (config.recipe !== undefined && config.recipe !== null) {
                    let recipeId = config.recipe;

                    // Handle inline recipe object
                    if (typeof recipeId === 'object') {
                        recipeId = this.addInlineRecipe(recipeId, state);
                    }
                    // Handle numeric index (0 = first recipe in entity's recipes array)
                    else if (typeof recipeId === 'number' && recipeId < 100 && symbolRecipeIds) {
                        if (recipeId < symbolRecipeIds.length) {
                            recipeId = symbolRecipeIds[recipeId];
                        } else {
                            throw new Error(`Recipe index ${recipeId} out of bounds (only ${symbolRecipeIds.length} recipes) for entity at (${x}, ${y})`);
                        }
                    }
                    // Handle string names ('iron_smelting')
                    else if (typeof recipeId === 'string') {
                        recipeId = state.recipes[config.recipe];
                        if (typeof recipeId !== 'number') {
                            throw new Error(`Recipe "${config.recipe}" not found for entity at (${x}, ${y})`);
                        }
                    }

                    const recipe = state.recipes[recipeId];
                    if (!recipe || typeof recipe !== 'object') {
                        throw new Error(`Recipe ID ${recipeId} not found for entity at (${x}, ${y})`);
                    }

                    state.craftingStates.push({
                        entity_id: entityId,
                        recipe_id: recipe.recipe_id,
                        ticks_remaining: recipe.ticks
                    });
                }

                // Add transport state if specified
                if (config.transportState) {
                    state.transportStates.push({
                        entity_id: entityId,
                        ...config.transportState
                    });
                }

                entityId++;
            }
        }

        return state;
    }

    /**
     * Create entity from configuration
     *
     * @param {number} entityId - Entity ID
     * @param {number} x - X coordinate in pixels
     * @param {number} y - Y coordinate in pixels
     * @param {Object} config - Entity configuration from legend
     * @returns {Object} Entity data
     */
    static createEntity(entityId, x, y, config) {
        const entityTypeId = this.getEntityTypeId(config.type);

        return {
            entity_id: entityId,
            entity_type_id: entityTypeId,
            state: config.state || 'built',
            durability: config.durability || 100,
            x: x,
            y: y,
            construction_progress: config.construction_progress || 100
        };
    }

    /**
     * Get entity type ID from type name
     *
     * @param {string} typeName - Entity type name (e.g., 'conveyor_down')
     * @returns {number} Entity type ID
     */
    static getEntityTypeId(typeName) {
        const typeMap = {
            // Conveyors (100-130)
            'conveyor': 100,
            'conveyor_right': 100,
            'conveyor_down': 101,
            'conveyor_left': 102,
            'conveyor_up': 103,
            'conveyor_dual': 123,
            'conveyor_dual_right': 123,
            'conveyor_dual_down': 124,
            'conveyor_dual_left': 125,
            'conveyor_dual_up': 126,

            // Pipes (131-141)
            'pipe': 131,
            'pipe_right': 131,
            'pipe_down': 132,
            'pipe_left': 133,
            'pipe_up': 134,

            // Buildings (200-250)
            'furnace': 200,
            'small_furnace': 200,
            'assembler': 205,

            // Manipulators (700-703)
            'manipulator': 700,
            'manipulator_right': 700,
            'manipulator_down': 701,
            'manipulator_left': 702,
            'manipulator_up': 703,

            // Splitters (800-811)
            'splitter': 800,
            'splitter_right': 800,
            'splitter_down': 801,
            'splitter_left': 802,
            'splitter_up': 803,

            // Electricity (300-310)
            'power_pole': 300,
            'power_pole_small': 300,
            'power_pole_medium': 301,
            'solar_panel': 305,
            'accumulator': 306,

            // Mining (250)
            'mining_drill': 250
        };

        if (!typeMap[typeName]) {
            throw new Error(`Unknown entity type: ${typeName}`);
        }

        return typeMap[typeName];
    }

    /**
     * Get entity type definition
     *
     * @param {Object} config - Entity configuration
     * @param {Object} state - Game state (for adding inline recipes)
     * @returns {Object} Entity type data
     */
    static getEntityType(config, state) {
        const typeName = config.type;
        const entityTypeId = this.getEntityTypeId(typeName);

        // Process inline recipes (convert to IDs)
        let recipeIds = [];
        if (config.recipes) {
            for (const recipeConfig of config.recipes) {
                if (typeof recipeConfig === 'number') {
                    // Already an ID
                    recipeIds.push(recipeConfig);
                } else if (typeof recipeConfig === 'object') {
                    // Inline recipe - convert to game format and add to state
                    const recipeId = this.addInlineRecipe(recipeConfig, state);
                    recipeIds.push(recipeId);
                }
            }
        }

        // Define entity types based on ID ranges
        if (entityTypeId >= 100 && entityTypeId <= 130) {
            // Conveyors (must use 'transporter' type for ResourceTransportManager)
            // Extract orientation from type name (e.g., 'conveyor_right' -> 'right')
            const orientation = typeName.includes('_')
                ? typeName.split('_').pop()
                : 'right';

            return {
                entity_type_id: entityTypeId,
                type: 'transporter',
                name: typeName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                folder: typeName,
                orientation: orientation,  // Critical for ResourceTransportManager
                max_durability: 100,
                width: 1,
                height: 1,
                power: 100,  // Standard speed: 100 = 1 tile per second at 60 FPS
                costs: {},
                recipes: [],
                behavior: {
                    behaviorClass: 'ConveyorBehavior',
                    checksFog: true,
                    checksLanding: true,
                    checksCollision: true,
                    requiresTarget: false
                }
            };
        } else if (entityTypeId >= 131 && entityTypeId <= 141) {
            // Pipes
            return {
                entity_type_id: entityTypeId,
                type: 'pipe',
                name: typeName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                folder: typeName,
                max_durability: 100,
                width: 1,
                height: 1,
                power: 0,
                costs: {},
                recipes: [],
                behavior: {
                    behaviorClass: 'PipeBehavior',
                    checksFog: true,
                    checksLanding: true,
                    checksCollision: true,
                    requiresTarget: false
                }
            };
        } else if (entityTypeId >= 700 && entityTypeId <= 703) {
            // Manipulators
            // Extract orientation from type name (e.g., 'manipulator_right' -> 'right')
            const orientation = typeName.includes('_')
                ? typeName.split('_').pop()
                : 'right';

            return {
                entity_type_id: entityTypeId,
                type: 'manipulator',
                name: typeName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                folder: typeName,
                orientation: orientation,  // Critical for ManipulatorState
                max_durability: 100,
                width: 1,
                height: 1,
                power: 100,  // Standard speed: power=100 for full swing in 30 frames
                costs: {},
                recipes: [],
                behavior: {
                    behaviorClass: 'ManipulatorBehavior',
                    checksFog: true,
                    checksLanding: true,
                    checksCollision: true,
                    requiresTarget: false
                }
            };
        } else if (entityTypeId >= 800 && entityTypeId <= 811) {
            // Splitters
            // Extract orientation from type name (e.g., 'splitter_right' -> 'right')
            const orientation = typeName.includes('_')
                ? typeName.split('_').pop()
                : 'right';

            return {
                entity_type_id: entityTypeId,
                type: 'splitter',
                name: typeName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                folder: typeName,
                orientation: orientation,  // Critical for SplitterState
                max_durability: 100,
                width: 1,
                height: 1,
                power: 15,
                costs: {},
                recipes: [],
                behavior: {
                    behaviorClass: 'SplitterBehavior',
                    checksFog: true,
                    checksLanding: true,
                    checksCollision: true,
                    requiresTarget: false
                }
            };
        } else if (entityTypeId >= 300 && entityTypeId <= 310) {
            // Electricity
            return {
                entity_type_id: entityTypeId,
                type: 'electricity',
                name: typeName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                folder: typeName,
                max_durability: 100,
                width: 1,
                height: 1,
                power: 0,
                costs: {},
                recipes: [],
                behavior: {
                    behaviorClass: 'ElectricityBehavior',
                    checksFog: true,
                    checksLanding: true,
                    checksCollision: true,
                    requiresTarget: false
                }
            };
        } else {
            // Buildings (furnace, assembler, etc.)
            return {
                entity_type_id: entityTypeId,
                type: 'building',
                name: typeName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                folder: typeName,
                max_durability: 150,
                width: 2,
                height: 2,
                power: config.power || 100,
                costs: {},
                recipes: recipeIds,
                behavior: {
                    behaviorClass: 'CraftingBehavior',
                    checksFog: true,
                    checksLanding: true,
                    checksCollision: true,
                    requiresTarget: false
                }
            };
        }
    }

    /**
     * Add inline recipe to state
     *
     * @param {Object} recipeConfig - Inline recipe config
     * @param {Object} state - Game state
     * @returns {number} Recipe ID
     *
     * @example
     * recipeConfig = {
     *   input: { resource: 'iron_ore', amount: 1 },
     *   output: { resource: 'iron_plate', amount: 1 },
     *   ticks: 30,
     *   name: 'Iron Smelting'  // optional
     * }
     * // OR multiple inputs:
     * recipeConfig = {
     *   inputs: [
     *     { resource: 'iron_ore', amount: 2 },
     *     { resource: 'copper_ore', amount: 1 }
     *   ],
     *   output: { resource: 'steel', amount: 1 },
     *   ticks: 60
     * }
     */
    static addInlineRecipe(recipeConfig, state) {
        // Generate new recipe ID (starting from 1000 to avoid conflicts)
        const recipeId = 1000 + Object.keys(state.recipes).filter(k => parseInt(k) >= 1000).length;

        // Resolve resource names to IDs
        const resolveResource = (resourceName) => {
            // If already a number, return as-is
            if (typeof resourceName === 'number') return resourceName;

            // Look up by name
            for (const [id, resource] of Object.entries(state.resources)) {
                if (resource.name.toLowerCase().replace(/ /g, '_') === resourceName.toLowerCase().replace(/ /g, '_')) {
                    return parseInt(id);
                }
            }
            throw new Error(`Resource "${resourceName}" not found in resources`);
        };

        // Handle both single input and multiple inputs
        let inputs = [];
        if (recipeConfig.input) {
            inputs = [recipeConfig.input];
        } else if (recipeConfig.inputs) {
            inputs = recipeConfig.inputs;
        }

        // Convert to game format
        const recipe = {
            recipe_id: recipeId,
            name: recipeConfig.name || `Recipe ${recipeId}`,
            ticks: recipeConfig.ticks || 30
        };

        // Add inputs
        if (inputs[0]) {
            recipe.input1_resource_id = resolveResource(inputs[0].resource);
            recipe.input1_amount = inputs[0].amount;
        }
        if (inputs[1]) {
            recipe.input2_resource_id = resolveResource(inputs[1].resource);
            recipe.input2_amount = inputs[1].amount;
        }
        if (inputs[2]) {
            recipe.input3_resource_id = resolveResource(inputs[2].resource);
            recipe.input3_amount = inputs[2].amount;
        }

        // Add output
        recipe.output_resource_id = resolveResource(recipeConfig.output.resource);
        recipe.output_amount = recipeConfig.output.amount;

        // Add to state
        state.recipes[recipeId] = recipe;

        return recipeId;
    }

    /**
     * Get default resource definitions
     *
     * @returns {Object} Resources indexed by ID
     */
    static getDefaultResources() {
        return {
            1: { resource_id: 1, name: 'Iron Ore', icon: 'iron_ore.png' },
            2: { resource_id: 2, name: 'Iron Plate', icon: 'iron_plate.png' },
            3: { resource_id: 3, name: 'Copper Ore', icon: 'copper_ore.png' },
            4: { resource_id: 4, name: 'Copper Plate', icon: 'copper_plate.png' },
            5: { resource_id: 5, name: 'Stone', icon: 'stone.png' },
            6: { resource_id: 6, name: 'Wood', icon: 'wood.png' },
            7: { resource_id: 7, name: 'Steel', icon: 'steel.png' },
            8: { resource_id: 8, name: 'Plastic', icon: 'plastic.png' },
            10: { resource_id: 10, name: 'Coal', icon: 'coal.png' },
            11: { resource_id: 11, name: 'Iron Gear', icon: 'iron_gear.png' },
            20: { resource_id: 20, name: 'Water', icon: 'water.png' },
            30: { resource_id: 30, name: 'Crude Oil', icon: 'crude_oil.png' },
            100: { resource_id: 100, name: 'Electricity', icon: 'electricity.png' }
        };
    }

    /**
     * Get default recipe definitions
     *
     * @returns {Object} Recipes indexed by recipe_id (matches game format)
     */
    static getDefaultRecipes() {
        return {
            1: {
                recipe_id: 1,
                name: 'Iron Smelting',
                input1_resource_id: 1,
                input1_amount: 1,
                output_resource_id: 2,
                output_amount: 1,
                ticks: 30
            },
            2: {
                recipe_id: 2,
                name: 'Copper Smelting',
                input1_resource_id: 3,
                input1_amount: 1,
                output_resource_id: 4,
                output_amount: 1,
                ticks: 30
            },
            3: {
                recipe_id: 3,
                name: 'Iron Gear',
                input1_resource_id: 2,
                input1_amount: 2,
                output_resource_id: 11,
                output_amount: 1,
                ticks: 60
            },
            // Named aliases for convenience in tests
            iron_smelting: 1,
            copper_smelting: 2,
            iron_gear: 3
        };
    }
}
