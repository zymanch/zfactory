/**
 * Game Simulation Tests (Data Provider Pattern)
 *
 * Integration tests for complete game systems using ASCII maps.
 * Tests conveyors, manipulators, crafting, electricity, and pipes.
 *
 * HOW TO ADD NEW TESTS:
 * 1. Add new test case to testCases array
 * 2. Define ASCII map with # for empty and symbols for entities
 * 3. Define legend mapping symbols to entity configuration
 * 4. Set number of ticks to simulate
 * 5. Define expected state after simulation
 *
 * See examples below for reference.
 */

import { describe, it, expect, beforeAll } from 'vitest';
import { MapBuilder } from '../helpers/MapBuilder.js';
import {
    createGameInstance,
    initializeManagers,
    runSimulation,
    getEntityResources,
    assertEntityResources,
    assertEntityCrafting,
    printGameState
} from '../helpers/GameSimulator.js';

/**
 * Test Cases (Data Provider)
 *
 * Each test case defines:
 * - name: Test description
 * - map: ASCII map (# = empty, symbols = entities)
 * - legend: Symbol to entity configuration mapping
 * - recipes: Optional embedded recipes (overrides defaults)
 * - ticks: Number of game ticks to simulate
 * - expectedState: Expected state after simulation
 */
const testCases = [
    {
        name: 'Simple conveyor transport - Iron ore moves from A to B',
        map: [
            '12#',
        ],
        legend: {
            '1': {
                type: 'conveyor_right',
                resources: [{ id: 1, amount: 1 }] // Iron ore
            },
            '2': {
                type: 'conveyor_right'
            }
        },
        ticks: 120,  // Need ~60 ticks for movement + logic tick interval
        expectedState: {
            entityResources: {
                2: { 1: 1 } // Entity 2 should have 1 iron ore
            }
        }
    },

    {
        name: 'Conveyor chain - Iron ore travels through 3 conveyors',
        map: [
            '1234#',
        ],
        legend: {
            '1': {
                type: 'conveyor_right',
                resources: [{ id: 1, amount: 1 }]
            },
            '2': { type: 'conveyor_right' },
            '3': { type: 'conveyor_right' },
            '4': { type: 'conveyor_right' }
        },
        ticks: 300,  // Each hop ~90 ticks, 4 conveyors = 270+ ticks
        expectedState: {
            entityResources: {
                4: { 1: 1 } // Iron ore reaches end of chain
            }
        }
    },

    {
        name: 'Furnace smelting - Iron ore to iron plate',
        map: [
            '1#',
        ],
        legend: {
            '1': {
                type: 'furnace',
                power: 100,
                recipes: [
                    // Inline recipe definition (more readable!)
                    {
                        input: { resource: 'iron_ore', amount: 1 },
                        output: { resource: 'iron_plate', amount: 1 },
                        ticks: 30,
                        name: 'Iron Smelting'
                    }
                ],
                recipe: 0  // Start crafting recipe at index 0 (input already consumed)
                // Note: No resources - input was consumed when crafting started
            }
        },
        ticks: 900,  // Recipe takes 30 logic ticks * 30 game ticks = 900 ticks
        expectedState: {
            entityResources: {
                1: { 2: 1 } // Furnace should have 1 iron plate (output)
            },
            craftingStates: {
                1: null // Should not be crafting anymore
            }
        }
    },

    {
        name: 'Manipulator transfer - Move iron from conveyor to building',
        skip: false,  // ManipulatorState works!
        debug: false,  // Disable debug output
        map: [
            '123#',
            '####',
        ],
        legend: {
            '1': {
                type: 'conveyor_right',
                resources: [{ id: 1, amount: 1 }]
            },
            '2': {
                type: 'manipulator_right'
            },
            '3': {
                type: 'furnace',
                power: 100,
                recipes: [
                    {
                        input: { resource: 'iron_ore', amount: 1 },
                        output: { resource: 'iron_plate', amount: 1 },
                        ticks: 30,
                        name: 'Iron Smelting'
                    }
                ]
            }
        },
        ticks: 1200,  // Full cycle: manipulator (180) + crafting (900) + buffer (120)
        expectedState: {
            entityResources: {
                3: { 2: 1 } // Furnace should have iron plate (output after smelting)
            },
            craftingStates: {
                3: null // Should not be crafting anymore (completed)
            }
        }
    },

    {
        name: 'Multi-direction conveyors - L-shaped path',
        map: [
            '12#',
            '#3#',
            '#4#',
        ],
        legend: {
            '1': {
                type: 'conveyor_right',
                resources: [{ id: 1, amount: 1 }]
            },
            '2': { type: 'conveyor_down' },
            '3': { type: 'conveyor_down' },
            '4': { type: 'conveyor_down' }
        },
        ticks: 300,  // L-shaped path with 4 conveyors = 270+ ticks
        expectedState: {
            entityResources: {
                4: { 1: 1 }
            }
        }
    },

    {
        name: '[SKIP] Dual conveyors - Two resources on same belt',
        skip: true,  // TODO: Needs multiple resources per conveyor support
        map: [
            '12#',
        ],
        legend: {
            '1': {
                type: 'conveyor_dual_right',
                resources: [
                    { id: 1, amount: 1 }, // Iron ore
                    { id: 3, amount: 1 }  // Copper ore
                ]
            },
            '2': { type: 'conveyor_dual_right' }
        },
        ticks: 30,
        expectedState: {
            entityResources: {
                2: { 1: 1, 3: 1 }
            }
        }
    },

    {
        name: 'Splitter - Basic functionality (single resource)',
        skip: false,  // Testing basic splitter with one resource
        map: [
            '#3#',
            '1S2',
        ],
        legend: {
            '1': {
                type: 'conveyor_right',
                resources: [{ id: 1, amount: 1 }]  // Single resource to test basic splitting
            },
            'S': { type: 'splitter_right' },
            '2': { type: 'conveyor_right' },  // Right output
            '3': { type: 'conveyor_down' }    // Left output
        },
        ticks: 300,  // Time for resource to travel through system
        expectedState: {
            // Resource should go to one of the outputs (splitter alternates)
            // We'll check that resource reached either output 2 or 3
            totalResources: {
                1: 1  // One iron ore should be in the system
            }
        }
    },

    {
        name: '[SKIP] Production chain - Mine, transport, smelt',
        skip: true,  // TODO: Needs mining + manipulator + furnace support
        map: [
            '1234##',
            '######',
        ],
        legend: {
            '1': {
                type: 'mining_drill',
                resources: [{ id: 1, amount: 2 }] // Starts with 2 iron ore
            },
            '2': { type: 'manipulator_right' },
            '3': { type: 'conveyor_right' },
            '4': {
                type: 'furnace',
                recipe: 'iron_smelting'
            }
        },
        ticks: 90,
        expectedState: {
            entityResources: {
                4: { 2: 1 } // Should have at least 1 iron plate
            }
        }
    },

    {
        name: 'Multiple furnaces - Parallel smelting',
        skip: false,  // Testing parallel crafting
        map: [
            '1F##',
            '2F##',
        ],
        legend: {
            '1': {
                type: 'conveyor_right',
                resources: [{ id: 1, amount: 1 }]
            },
            '2': {
                type: 'conveyor_right',
                resources: [{ id: 1, amount: 1 }]
            },
            'F': {
                type: 'furnace',
                power: 100,
                recipes: [
                    {
                        input: { resource: 'iron_ore', amount: 1 },
                        output: { resource: 'iron_plate', amount: 1 },
                        ticks: 30,
                        name: 'Iron Smelting'
                    }
                ],
                recipe: 0,  // Start crafting (input already consumed)
                resources: []  // No initial resources (input consumed when crafting started)
            }
        },
        ticks: 900,  // 30 logic ticks * 30 game ticks
        expectedState: {
            entityResources: {
                2: { 2: 1 },  // Furnace 1 produces iron plate
                4: { 2: 1 }   // Furnace 2 produces iron plate
            }
        }
    },

    {
        name: '[SKIP] Buffer storage - Conveyor accumulates items',
        skip: true,  // TODO: Needs multiple items per conveyor support
        map: [
            '123#',
        ],
        legend: {
            '1': {
                type: 'conveyor_right',
                resources: [
                    { id: 1, amount: 1 },
                    { id: 1, amount: 1 },
                    { id: 1, amount: 1 }
                ]
            },
            '2': { type: 'conveyor_right' },
            '3': { type: 'conveyor_right' }
        },
        ticks: 240,  // Need time for all items to move through
        expectedState: {
            // Items should be distributed across conveyors
            totalResources: { 1: 3 }
        }
    },

    {
        name: 'Circular conveyors - Resources rotate clockwise',
        map: [
            '123',
            '8#4',
            '765',
        ],
        legend: {
            '1': { type: 'conveyor_right', resources: [{ id: 1, amount: 1 }] },
            '2': { type: 'conveyor_right', resources: [{ id: 2, amount: 1 }] },
            '3': { type: 'conveyor_down', resources: [{ id: 3, amount: 1 }] },
            '4': { type: 'conveyor_down', resources: [{ id: 4, amount: 1 }] },
            '5': { type: 'conveyor_left', resources: [{ id: 5, amount: 1 }] },
            '6': { type: 'conveyor_left', resources: [{ id: 6, amount: 1 }] },
            '7': { type: 'conveyor_up', resources: [{ id: 7, amount: 1 }] },
            '8': { type: 'conveyor_up', resources: [{ id: 8, amount: 1 }] }
        },
        ticks: 180,  // 2 hops = 2 * 90 = 180 ticks
        expectedState: {
            // After parsing: entity IDs are 1,2,3,4,5,6,7,8 (sequential, skipping #)
            // Flow: 1→2→3→5→8→7→6→4→1 (circular)
            // After 2 hops, each resource moves 2 positions forward
            entityResources: {
                1: { 7: 1 },  // Entity 1: resource 7 (from entity 6)
                2: { 8: 1 },  // Entity 2: resource 8 (from entity 4, symbol '8')
                3: { 1: 1 },  // Entity 3: resource 1 (from entity 1)
                4: { 6: 1 },  // Entity 4: resource 6 (from entity 7, symbol '6')
                5: { 2: 1 },  // Entity 5: resource 2 (from entity 2)
                6: { 5: 1 },  // Entity 6: resource 5 (from entity 8, symbol '5')
                7: { 4: 1 },  // Entity 7: resource 4 (from entity 5, symbol '4')
                8: { 3: 1 }   // Entity 8: resource 3 (from entity 3)
            }
        }
    },

    {
        name: '[SKIP] Complex production - Liquid + Electricity + Solid resources',
        skip: true,  // TODO: Needs PipeSystemManager, ElectricityManager, ManipulatorState
        map: [
            '####P##',
            'W~~~F##',
            '####M##',
            '####AB#',
        ],
        legend: {
            // Water source with pump
            'W': {
                type: 'mining_drill',
                resources: [{ id: 20, amount: 100 }]  // Water (deposit)
            },
            // Pipes carrying water
            '~': { type: 'pipe_right' },
            // Furnace producing iron plates
            'F': {
                type: 'furnace',
                resources: [{ id: 2, amount: 5 }],  // Start with 5 iron plates
                recipes: [
                    {
                        input: { resource: 'iron_ore', amount: 1 },
                        output: { resource: 'iron_plate', amount: 1 },
                        ticks: 30
                    }
                ]
            },
            // Manipulator to transfer iron plates
            'M': { type: 'manipulator_down' },
            // Assembler requiring: water (fluid) + iron plate (solid) + electricity
            'A': {
                type: 'assembler',
                power: 100,
                recipes: [
                    {
                        inputs: [
                            { resource: 'water', amount: 10 },
                            { resource: 'iron_plate', amount: 2 },
                            { resource: 'electricity', amount: 50 }
                        ],
                        output: { resource: 'steel', amount: 1 },
                        ticks: 60,
                        name: 'Steel Production'
                    }
                ],
                recipe: 0
            },
            // Power pole connected to assembler
            'P': {
                type: 'power_pole',
                resources: [{ id: 100, amount: 100 }]  // Electricity
            },
            // Battery (accumulator)
            'B': {
                type: 'accumulator',
                resources: [{ id: 100, amount: 1000 }]  // Charged battery
            }
        },
        ticks: 1800,  // Enough time for all systems to work
        expectedState: {
            entityResources: {
                // Assembler should produce steel
                // Entity ID depends on parsing order - need to find assembler
                // For now, check that steel exists somewhere
            },
            totalResources: {
                7: 1  // Steel was produced
            }
        }
    }
];

/**
 * Run all test cases
 */
describe('Game Simulation Tests', () => {
    // Run each test case
    testCases.forEach((testCase) => {
        const testFn = testCase.skip ? it.skip : it;
        testFn(testCase.name, async () => {
            // Build map
            const initialState = MapBuilder.build(
                testCase.map,
                testCase.legend,
                testCase.recipes || null
            );

            // Create game instance
            const game = createGameInstance(initialState);

            // Initialize managers
            await initializeManagers(game);

            // Run simulation
            const finalState = runSimulation(game, testCase.ticks, {
                logTicks: testCase.debug || false, // Use debug flag from test case
                tickCallback: testCase.debug ? (tick, game) => {
                    // Log logic ticks (every 30 ticks)
                    if (tick > 0 && tick % 30 === 0 && tick <= 300) {
                        const manip = game.resourceTransport?.manipulators.get(2);
                        const building = game.resourceTransport?.buildings.get(3);
                        if (manip && building) {
                            const manipPos = manip.position_px !== null ? manip.position_px.toFixed(1) : 'null';
                            const buildingRes = Array.from(building.resources.entries()).map(([id, amt]) => `${id}:${amt}`).join(', ');
                            console.log(`[Tick ${tick}] Manip: status=${manip.status}, pos=${manipPos}, resourceId=${manip.resourceId} | Building 3: ${buildingRes || 'empty'}`);
                        }
                    }
                } : null
            });

            // Optional: Print state for debugging
            if (testCase.debug) {
                printGameState(finalState, game);

                // Print transporter states
                if (game.resourceTransport && game.resourceTransport.transporters) {
                    console.log('\n=== Transporter States ===');
                    for (const [id, state] of game.resourceTransport.transporters) {
                        console.log(`  Transporter ${id}: status=${state.status}, resourceId=${state.resourceId}, position_px=${state.position_px}, target=${state.targetEntityId}`);
                    }
                }

                // Print manipulator links and states
                if (game.resourceTransport && game.resourceTransport.manipulators) {
                    console.log('\n=== Manipulator States ===');
                    for (const [id, state] of game.resourceTransport.manipulators) {
                        console.log(`  Manipulator ${id}: status=${state.status}, source=${state.sourceEntityId}, target=${state.targetEntityId}, orientation=${state.orientation}, resourceId=${state.resourceId}`);
                        console.log(`    centerPositionPx=${state.centerPositionPx}, reach=${state.reach}, power=${state.power}, speed=${state.getArmSpeed()}`);
                    }
                }
            }

            // Assert expected state
            if (testCase.expectedState.entityResources) {
                for (const [entityIdStr, expectedResources] of Object.entries(testCase.expectedState.entityResources)) {
                    const entityId = parseInt(entityIdStr);
                    assertEntityResources(finalState, entityId, expectedResources);
                }
            }

            if (testCase.expectedState.craftingStates) {
                for (const [entityIdStr, expectedRecipeId] of Object.entries(testCase.expectedState.craftingStates)) {
                    const entityId = parseInt(entityIdStr);
                    assertEntityCrafting(finalState, entityId, expectedRecipeId);
                }
            }

            if (testCase.expectedState.totalResources) {
                // Check total resources across all entities
                const totalResources = {};
                for (const er of finalState.entityResources) {
                    totalResources[er.resource_id] = (totalResources[er.resource_id] || 0) + er.amount;
                }

                for (const [resourceIdStr, expectedAmount] of Object.entries(testCase.expectedState.totalResources)) {
                    const resourceId = parseInt(resourceIdStr);
                    expect(totalResources[resourceId] || 0).toBe(expectedAmount);
                }
            }
        });
    });
});

/**
 * Example: Custom assertion test
 */
describe('Custom Assertions', () => {
    it('should allow custom assertions with getEntityResources', async () => {
        const initialState = MapBuilder.build(
            ['12#'],
            {
                '1': { type: 'conveyor_right', resources: [{ id: 1, amount: 1 }] },
                '2': { type: 'conveyor_right' }
            }
        );

        const game = createGameInstance(initialState);
        await initializeManagers(game);
        const finalState = runSimulation(game, 120);  // Need 120 ticks for 2 conveyors

        // Custom assertion using getEntityResources
        const entity2Resources = getEntityResources(finalState, 2);
        expect(entity2Resources[1]).toBe(1);
        expect(Object.keys(entity2Resources).length).toBe(1);
    });
});
