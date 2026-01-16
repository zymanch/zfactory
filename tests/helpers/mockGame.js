/**
 * Mock Game Object
 *
 * Provides a minimal mock of the Game instance for testing modules.
 * Each test can customize the mock by overriding properties.
 */

import { vi } from 'vitest';
import { FakeGraphicsEngine } from './FakeGraphicsEngine.js';

/**
 * Creates a mock Game object with common properties and methods
 *
 * @param {Object} overrides - Properties to override in the mock
 * @returns {Object} Mock game object
 */
export function createMockGame(overrides = {}) {
    const mockGame = {
        // Core data structures
        entities: [],
        entityData: new Map(),  // Map of entity data (key: 'entity_{id}', value: entity object)
        entityTypes: {},
        resources: {},
        recipes: {},
        userResources: {},
        deposits: [],

        // Managers (will be mocked by individual tests)
        electricityManager: null,
        pipeManager: null,
        craftingManager: null,
        transportManager: null,

        // Camera
        camera: {
            x: 0,
            y: 0,
            zoom: 1,
            worldToScreen: vi.fn((x, y) => ({ x, y })),
            screenToWorld: vi.fn((x, y) => ({ x, y })),
            getViewportBounds: vi.fn(() => ({
                minX: 0,
                minY: 0,
                maxX: 1280,
                maxY: 960
            }))
        },

        // GraphicsEngine (replaces direct PixiJS usage)
        graphics: null,  // Set below

        // PixiJS app mock (kept for backward compatibility during migration)
        app: {
            stage: {
                addChild: vi.fn(),
                removeChild: vi.fn()
            },
            renderer: {
                width: 1280,
                height: 960
            },
            ticker: {
                add: vi.fn(),
                remove: vi.fn()
            }
        },

        // Grid/tile system
        gridSize: 64,

        // Methods
        getEntity: vi.fn((entityId) => {
            return mockGame.entities.find(e => e.entity_id === entityId);
        }),

        getEntityType: vi.fn((entityTypeId) => {
            return mockGame.entityTypes[entityTypeId];
        }),

        getResource: vi.fn((resourceId) => {
            return mockGame.resources[resourceId];
        }),

        updateEntity: vi.fn(),
        removeEntity: vi.fn(),

        // Event system
        emit: vi.fn(),
        on: vi.fn(),
        off: vi.fn(),

        // Apply overrides
        ...overrides
    };

    // Initialize GraphicsEngine if not provided
    if (!mockGame.graphics) {
        mockGame.graphics = new FakeGraphicsEngine({});
    }

    return mockGame;
}

/**
 * Creates mock entity data
 *
 * @param {Object} data - Entity properties
 * @returns {Object} Mock entity
 */
export function createMockEntity(data = {}) {
    return {
        entity_id: 1,
        entity_type_id: 100,
        state: 'built',
        durability: 100,
        x: 320,
        y: 240,
        ...data
    };
}

/**
 * Creates mock entity type data
 *
 * @param {Object} data - Entity type properties
 * @returns {Object} Mock entity type
 */
export function createMockEntityType(data = {}) {
    return {
        entity_type_id: 100,
        type: 'building',
        name: 'Test Building',
        folder: 'test_building',
        max_durability: 100,
        width: 1,
        height: 1,
        power: 0,
        costs: {},
        recipes: [],
        behavior: {
            behaviorClass: 'DefaultEntityBehavior',
            checksFog: true,
            checksLanding: true,
            checksCollision: true,
            requiresTarget: false
        },
        ...data
    };
}
