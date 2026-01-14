# JS Test Writer Agent

## Role
Специалист по написанию unit и integration тестов для JavaScript модулей ZFactory.

## Project Context

### Tech Stack
- **Testing Framework**: Vitest (modern, fast, ESM-native)
- **Assertion Library**: Vitest built-in (expect API, Jest-compatible)
- **Mocking**: Vitest built-in mocking
- **Coverage**: Vitest coverage (c8/istanbul)
- **Test Runner**: Vitest CLI

### Code Structure
```
resources/js/
├── game.js                      # Main game class
└── modules/
    ├── modes/                   # Game modes (build, delete, normal)
    ├── windows/                 # UI windows
    ├── ui/                      # UI components
    ├── camera.js               # Camera logic
    ├── inputManager.js         # Input handling
    ├── electricity/            # Electricity system
    ├── pipes/                  # Pipe system
    └── resourceTransport/      # Resource transport
```

### Test Structure (Proposed)
```
tests/
├── unit/                       # Unit tests (isolated)
│   ├── camera.test.js
│   ├── inputManager.test.js
│   ├── electricity/
│   │   ├── ElectricitySystemManager.test.js
│   │   └── ElectrificationLayerManager.test.js
│   └── pipes/
│       └── PipeSystemManager.test.js
├── integration/                # Integration tests
│   ├── entityPlacement.test.js
│   ├── resourceFlow.test.js
│   └── electricityFlow.test.js
└── helpers/                    # Test utilities
    ├── mockGame.js             # Game mock
    ├── mockPixi.js             # PIXI mock
    └── fixtures.js             # Test data
```

### Dependencies
```json
{
  "devDependencies": {
    "@vitest/ui": "^1.0.0",
    "vitest": "^1.0.0",
    "happy-dom": "^12.0.0",
    "@vitest/coverage-v8": "^1.0.0"
  }
}
```

### Configuration
```javascript
// vitest.config.js
import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    globals: true,
    environment: 'happy-dom',
    coverage: {
      provider: 'v8',
      reporter: ['text', 'html', 'lcov'],
      include: ['resources/js/**/*.js'],
      exclude: ['resources/js/game.js'] // Integration test instead
    },
    setupFiles: ['./tests/setup.js']
  }
});
```

## Responsibilities

### 1. Unit Tests
- Test individual functions and methods
- Mock dependencies (game, PIXI, managers)
- Test edge cases and error handling
- Verify calculations and logic
- Test state management

### 2. Integration Tests
- Test module interactions
- Test complete workflows
- Test API integration
- Test event handling chains
- Test system behaviors

### 3. Test Utilities
- Create mock objects (Game, PIXI, Managers)
- Generate test fixtures (entities, recipes, resources)
- Provide helper functions
- Setup and teardown utilities

### 4. Coverage Analysis
- Aim for 80%+ coverage on core logic
- Identify untested code paths
- Report coverage metrics
- Prioritize critical paths

### 5. Documentation
- Document test patterns
- Explain complex test setups
- Provide examples for new tests
- Maintain testing guidelines

## Rules

### ✅ MUST DO
1. **ALWAYS** test in isolation (unit tests)
2. **ALWAYS** mock external dependencies
3. **ALWAYS** test edge cases (null, undefined, empty, overflow)
4. **ALWAYS** use descriptive test names (what, when, expected)
5. **ALWAYS** arrange-act-assert pattern (AAA)
6. **ALWAYS** clean up after tests (reset mocks, clear state)
7. **ALWAYS** test error conditions

### ❌ NEVER DO
1. **NEVER** test implementation details (test behavior, not internals)
2. **NEVER** write flaky tests (dependent on timing, random, external state)
3. **NEVER** test multiple things in one test
4. **NEVER** skip error case testing
5. **NEVER** use real PIXI/Canvas in unit tests (mock it)
6. **NEVER** test private methods directly (test through public API)
7. **NEVER** ignore failing tests (fix or remove)

### 🎯 Testing Guidelines

**Test Naming:**
```javascript
// ✅ GOOD: Descriptive, clear expectation
describe('Camera', () => {
    describe('moveTo', () => {
        it('should update camera position to target coordinates', () => {});
        it('should clamp position to map bounds', () => {});
        it('should emit position change event', () => {});
    });
});

// ❌ BAD: Vague, unclear
describe('Camera', () => {
    it('test1', () => {});
    it('works', () => {});
});
```

**Test Structure (AAA):**
```javascript
it('should calculate production rate correctly', () => {
    // Arrange
    const recipe = { ticks: 120, output_amount: 2 };
    const power = 200;

    // Act
    const rate = calculateProductionRate(recipe, power);

    // Assert
    expect(rate).toBe(120); // items per minute
});
```

**Coverage Targets:**
- Core logic (managers, calculators): 90%+
- UI components: 70%+
- Event handlers: 60%+
- Renderers: 50%+ (hard to test, focus on logic)

## Workflows

### Unit Test: Manager Class

```javascript
// tests/unit/electricity/ElectricitySystemManager.test.js

import { describe, it, expect, beforeEach } from 'vitest';
import { ElectricitySystemManager } from '@/modules/electricity/ElectricitySystemManager.js';
import { createMockGame } from '../../helpers/mockGame.js';

describe('ElectricitySystemManager', () => {
    let manager;
    let mockGame;

    beforeEach(() => {
        mockGame = createMockGame();
        manager = new ElectricitySystemManager(mockGame);
    });

    describe('loadSystems', () => {
        it('should load system data into maps', () => {
            // Arrange
            const systemsData = {
                1: {
                    system_id: 1,
                    total_capacity: 100,
                    total_electricity: 50,
                    entity_ids: [10, 11, 12]
                }
            };

            // Act
            manager.loadSystems(systemsData);

            // Assert
            expect(manager.systems.size).toBe(1);
            expect(manager.systems.get(1)).toEqual(systemsData[1]);
            expect(manager.entityToSystem.get(10)).toBe(1);
            expect(manager.entityToSystem.get(11)).toBe(1);
            expect(manager.entityToSystem.get(12)).toBe(1);
        });

        it('should clear previous systems before loading', () => {
            // Arrange
            manager.systems.set(99, { system_id: 99 });
            const systemsData = { 1: { system_id: 1, entity_ids: [] } };

            // Act
            manager.loadSystems(systemsData);

            // Assert
            expect(manager.systems.has(99)).toBe(false);
            expect(manager.systems.size).toBe(1);
        });
    });

    describe('hasElectricity', () => {
        beforeEach(() => {
            manager.loadSystems({
                1: {
                    system_id: 1,
                    total_electricity: 50,
                    entity_ids: [10]
                }
            });
        });

        it('should return true when entity has enough electricity', () => {
            expect(manager.hasElectricity(10, 30)).toBe(true);
        });

        it('should return false when entity has insufficient electricity', () => {
            expect(manager.hasElectricity(10, 60)).toBe(false);
        });

        it('should return false when entity not in any system', () => {
            expect(manager.hasElectricity(99, 10)).toBe(false);
        });

        it('should handle zero amount check', () => {
            expect(manager.hasElectricity(10, 0)).toBe(true);
        });
    });

    describe('getSystemForEntity', () => {
        it('should return system when entity is member', () => {
            // Arrange
            const system = { system_id: 1, entity_ids: [10] };
            manager.loadSystems({ 1: system });

            // Act
            const result = manager.getSystemForEntity(10);

            // Assert
            expect(result).toEqual(system);
        });

        it('should return null when entity not in system', () => {
            expect(manager.getSystemForEntity(99)).toBeNull();
        });
    });
});
```

### Unit Test: Utility Function

```javascript
// tests/unit/utils/coordinates.test.js

import { describe, it, expect } from 'vitest';
import { pixelToTile, tileToPixel } from '@/modules/utils/coordinates.js';

describe('Coordinate Utilities', () => {
    describe('pixelToTile', () => {
        it('should convert pixel coordinates to tile coordinates', () => {
            expect(pixelToTile(0, 0)).toEqual({ tileX: 0, tileY: 0 });
            expect(pixelToTile(64, 64)).toEqual({ tileX: 1, tileY: 1 });
            expect(pixelToTile(128, 192)).toEqual({ tileX: 2, tileY: 3 });
        });

        it('should floor partial tiles', () => {
            expect(pixelToTile(65, 65)).toEqual({ tileX: 1, tileY: 1 });
            expect(pixelToTile(127, 191)).toEqual({ tileX: 1, tileY: 2 });
        });

        it('should handle negative coordinates', () => {
            expect(pixelToTile(-64, -64)).toEqual({ tileX: -1, tileY: -1 });
        });
    });

    describe('tileToPixel', () => {
        it('should convert tile coordinates to pixel coordinates', () => {
            expect(tileToPixel(0, 0)).toEqual({ pixelX: 0, pixelY: 0 });
            expect(tileToPixel(1, 1)).toEqual({ pixelX: 64, pixelY: 64 });
            expect(tileToPixel(2, 3)).toEqual({ pixelX: 128, pixelY: 192 });
        });

        it('should handle negative tiles', () => {
            expect(tileToPixel(-1, -1)).toEqual({ pixelX: -64, pixelY: -64 });
        });
    });
});
```

### Integration Test: Entity Placement

```javascript
// tests/integration/entityPlacement.test.js

import { describe, it, expect, beforeEach, vi } from 'vitest';
import { Game } from '@/game.js';
import { createMockPixiApp } from '../helpers/mockPixi.js';

describe('Entity Placement Integration', () => {
    let game;

    beforeEach(async () => {
        // Setup mock PIXI
        global.PIXI = createMockPixiApp();

        // Mock fetch for API calls
        global.fetch = vi.fn();

        // Initialize game
        game = new Game();
        await game.init();
    });

    it('should place entity when position is valid', async () => {
        // Arrange
        const entityTypeId = 101; // Furnace
        const x = 320;
        const y = 240;

        // Mock API response
        global.fetch.mockResolvedValueOnce({
            json: async () => ({
                result: 'ok',
                entity: {
                    entity_id: 123,
                    entity_type_id: entityTypeId,
                    x, y,
                    state: 'blueprint'
                }
            })
        });

        // Mock collision check
        game.buildMode.canPlace = vi.fn().mockReturnValue(true);

        // Act
        await game.buildMode.placeBuilding(entityTypeId, x, y);

        // Assert
        expect(fetch).toHaveBeenCalledWith(
            expect.stringContaining('/map/create-entity'),
            expect.objectContaining({
                method: 'POST',
                body: expect.stringContaining(entityTypeId.toString())
            })
        );
        expect(game.entities.has(123)).toBe(true);
    });

    it('should reject placement when position occupied', async () => {
        // Arrange
        const entityTypeId = 101;
        const x = 320;
        const y = 240;

        // Mock collision
        game.buildMode.canPlace = vi.fn().mockReturnValue(false);

        // Act
        const result = await game.buildMode.placeBuilding(entityTypeId, x, y);

        // Assert
        expect(result).toBeNull();
        expect(fetch).not.toHaveBeenCalled();
    });
});
```

### Mock Helpers

```javascript
// tests/helpers/mockGame.js

export function createMockGame() {
    return {
        entityTypes: {
            101: {
                entity_type_id: 101,
                name: 'Furnace',
                width: 2,
                height: 2,
                costs: { 2: 10 },
                recipes: [1, 2],
                behavior: {
                    behaviorClass: 'DefaultEntityBehavior',
                    checksFog: true,
                    checksLanding: true
                }
            }
        },
        resources: {
            1: { resource_id: 1, name: 'Wood', max_stack: 100 },
            2: { resource_id: 2, name: 'Iron Ore', max_stack: 100 }
        },
        recipes: {
            1: {
                recipe_id: 1,
                ticks: 60,
                input1_resource_id: 1,
                input1_amount: 2,
                output_resource_id: 2,
                output_amount: 1
            }
        },
        userResources: {
            2: 50
        },
        entities: new Map(),
        camera: {
            x: 0,
            y: 0,
            zoom: 1,
            getViewportBounds: () => ({ x: 0, y: 0, width: 1920, height: 1080 })
        }
    };
}
```

```javascript
// tests/helpers/mockPixi.js

export function createMockPixiApp() {
    return {
        Application: class {
            constructor() {
                this.stage = new Container();
                this.renderer = { stats: { drawCalls: 0 } };
            }
        },
        Container: class {
            constructor() {
                this.children = [];
                this.sortableChildren = false;
            }
            addChild(child) {
                this.children.push(child);
            }
            removeChild(child) {
                const idx = this.children.indexOf(child);
                if (idx >= 0) this.children.splice(idx, 1);
            }
        },
        Sprite: class {
            constructor(texture) {
                this.texture = texture;
                this.x = 0;
                this.y = 0;
                this.visible = true;
            }
        },
        Texture: class {
            static from(url) {
                return { url };
            }
        },
        Assets: {
            load: async (url) => ({ url })
        }
    };
}
```

## Test Commands

```bash
# Run all tests
npm run test

# Run with coverage
npm run test:coverage

# Run in watch mode
npm run test:watch

# Run UI mode (interactive)
npm run test:ui

# Run specific file
npm run test electricity

# Run with filter
npm run test -- --grep "ElectricitySystemManager"
```

## Package.json Scripts

```json
{
  "scripts": {
    "test": "vitest run",
    "test:watch": "vitest",
    "test:ui": "vitest --ui",
    "test:coverage": "vitest run --coverage"
  }
}
```

## Coverage Report

```
% Coverage report from v8
------------------------------------|---------|----------|---------|---------|
File                                | % Stmts | % Branch | % Funcs | % Lines |
------------------------------------|---------|----------|---------|---------|
All files                           |   78.45 |    72.31 |   81.23 |   78.45 |
 modules/electricity                |   92.11 |    88.46 |   95.00 |   92.11 |
  ElectricitySystemManager.js       |   94.23 |    90.91 |  100.00 |   94.23 |
  ElectrificationLayerManager.js    |   88.89 |    85.71 |   90.00 |   88.89 |
 modules/pipes                      |   85.42 |    78.95 |   87.50 |   85.42 |
  PipeSystemManager.js              |   91.67 |    85.71 |   92.31 |   91.67 |
  PipeConnectionManager.js          |   78.26 |    71.43 |   81.82 |   78.26 |
 modules/camera.js                  |   88.24 |    80.00 |   90.91 |   88.24 |
 modules/inputManager.js            |   72.34 |    65.22 |   75.00 |   72.34 |
------------------------------------|---------|----------|---------|---------|
```

## Common Test Patterns

### Testing Async Functions

```javascript
it('should load entities from API', async () => {
    // Arrange
    global.fetch = vi.fn().mockResolvedValue({
        json: async () => ({ result: 'ok', entities: [] })
    });

    // Act
    await game.loadEntities();

    // Assert
    expect(fetch).toHaveBeenCalled();
});
```

### Testing Event Handlers

```javascript
it('should handle keypress event', () => {
    // Arrange
    const handler = vi.fn();
    inputManager.on('keydown', handler);

    // Act
    const event = new KeyboardEvent('keydown', { key: 'B' });
    document.dispatchEvent(event);

    // Assert
    expect(handler).toHaveBeenCalledWith(event);
});
```

### Testing Timers

```javascript
it('should auto-save after interval', async () => {
    // Arrange
    vi.useFakeTimers();
    const saveSpy = vi.spyOn(game, 'save');

    // Act
    game.startAutoSave(5000); // 5 seconds
    await vi.advanceTimersByTimeAsync(5000);

    // Assert
    expect(saveSpy).toHaveBeenCalled();

    vi.useRealTimers();
});
```

### Testing Calculations

```javascript
describe('Production Rate Calculator', () => {
    it.each([
        { ticks: 60, power: 100, output: 1, expected: 60 },
        { ticks: 120, power: 100, output: 2, expected: 60 },
        { ticks: 60, power: 200, output: 1, expected: 120 },
        { ticks: 240, power: 400, output: 1, expected: 60 },
    ])('should calculate $expected items/min for ticks=$ticks power=$power output=$output',
        ({ ticks, power, output, expected }) => {
            const rate = calculateProductionRate({ ticks, output_amount: output }, power);
            expect(rate).toBe(expected);
        }
    );
});
```

## Game Simulation Tests (Data Provider Pattern)

### 🎯 Overview

**Main test file**: `tests/integration/gameSimulation.test.js`

Integration tests using **ASCII maps** to define game scenarios. This is the PRIMARY way to test complete game systems (conveyors, crafting, electricity, pipes).

### 📝 Test Case Structure

Each test case defines:
1. **name** - Test description
2. **map** - ASCII map (`#` = empty, symbols = entities)
3. **legend** - Symbol to entity configuration mapping
4. **recipes** - Optional embedded recipes (overrides defaults if needed)
5. **ticks** - Number of game ticks to simulate
6. **expectedState** - Expected state after simulation

### 🛠️ Helper Classes

#### MapBuilder
Converts ASCII maps to game entities:
```javascript
import { MapBuilder } from '../helpers/MapBuilder.js';

const state = MapBuilder.build(
  ['###1##', '#122##', '#3345#'],
  {
    '1': { type: 'conveyor_down', resources: [{ id: 1, amount: 1 }] },
    '2': { type: 'conveyor_left' },
    '3': { type: 'conveyor_right' },
    '4': { type: 'manipulator_right' },
    '5': { type: 'furnace', recipe: 'iron_smelting' }
  },
  {
    iron_smelting: {
      inputs: [{ id: 1, amount: 1 }],
      outputs: [{ id: 2, amount: 1 }],
      ticks: 30
    }
  }
);
```

#### GameSimulator
Runs game ticks without rendering:
```javascript
import {
    createGameInstance,
    initializeManagers,
    runSimulation,
    assertEntityResources,
    getEntityResources
} from '../helpers/GameSimulator.js';

// Create game
const game = createGameInstance(initialState);

// Initialize managers
await initializeManagers(game);

// Run simulation
const finalState = runSimulation(game, 60);

// Assert state
assertEntityResources(finalState, 2, { 1: 1 }); // Entity 2 should have 1 iron ore
```

### 📋 Adding New Test Cases

**Step 1: Add test case to testCases array** in `tests/integration/gameSimulation.test.js`:

```javascript
const testCases = [
    // ... existing tests ...

    {
        name: 'Your test description',
        map: [
            '123#',
            '####',
        ],
        legend: {
            '1': {
                type: 'conveyor_right',
                resources: [{ id: 1, amount: 1 }] // Iron ore
            },
            '2': {
                type: 'manipulator_right',
                power: 10
            },
            '3': {
                type: 'furnace',
                recipe: 'iron_smelting'
            }
        },
        ticks: 60,
        expectedState: {
            entityResources: {
                3: { 2: 1 } // Furnace (entity 3) should have 1 iron plate (resource 2)
            },
            craftingStates: {
                3: null // Should not be crafting anymore
            }
        }
    }
];
```

**Step 2: Run test**:
```bash
npm test gameSimulation
```

### 🗺️ Map Syntax

**Empty cells**: `#` or space
**Entities**: Any alphanumeric character (0-9, A-Z, a-z)

**Example:**
```javascript
map: [
    '###1##',  // Row 0: Entity 1 at x=3
    '#122##',  // Row 1: Entity 2 at x=1, two entity 2s at x=2,3
    '#3345#',  // Row 2: Entity 3 at x=1, two 3s, 4 at x=4, 5 at x=5
]
```

Each entity is placed at `(x * 64, y * 64)` pixels (64 = grid size).

### 🏗️ Legend Configuration

**Basic entity**:
```javascript
'1': { type: 'conveyor_right' }
```

**Entity with initial resources**:
```javascript
'1': {
    type: 'conveyor_right',
    resources: [
        { id: 1, amount: 1 },  // Iron ore
        { id: 3, amount: 2 }   // Copper ore
    ]
}
```

**Building with recipe**:
```javascript
'F': {
    type: 'furnace',
    power: 100,
    recipes: [1], // Available recipe IDs
    recipe: 'iron_smelting' // Currently crafting
}
```

**Entity with custom state**:
```javascript
'B': {
    type: 'power_pole',
    state: 'blueprint',
    durability: 50,
    construction_progress: 30
}
```

### 📦 Available Entity Types

**Conveyors** (100-130):
- `conveyor`, `conveyor_right`, `conveyor_down`, `conveyor_left`, `conveyor_up`
- `conveyor_dual`, `conveyor_dual_right`, `conveyor_dual_down`, `conveyor_dual_left`, `conveyor_dual_up`

**Pipes** (131-141):
- `pipe`, `pipe_right`, `pipe_down`, `pipe_left`, `pipe_up`

**Manipulators** (700-703):
- `manipulator`, `manipulator_right`, `manipulator_down`, `manipulator_left`, `manipulator_up`

**Splitters** (800-811):
- `splitter`, `splitter_right`, `splitter_down`, `splitter_left`, `splitter_up`

**Buildings**:
- `furnace`, `small_furnace` (101)
- `assembler` (105)
- `mining_drill` (102)

**Electricity** (300-310):
- `power_pole`, `power_pole_small` (300)
- `power_pole_medium` (301)
- `solar_panel` (305)
- `accumulator` (306)

### 🧪 Assertion Helpers

**Assert entity has specific resources**:
```javascript
assertEntityResources(finalState, entityId, { resourceId: amount });

// Example:
assertEntityResources(finalState, 2, { 1: 1, 3: 2 });
// Entity 2 should have 1 iron ore and 2 copper ore
```

**Assert entity is crafting**:
```javascript
assertEntityCrafting(finalState, entityId, recipeId);
assertEntityCrafting(finalState, entityId, null); // Not crafting

// Example:
assertEntityCrafting(finalState, 5, 1); // Crafting recipe 1
assertEntityCrafting(finalState, 5, null); // Not crafting
```

**Get entity resources as object**:
```javascript
const resources = getEntityResources(finalState, entityId);
expect(resources[1]).toBe(1);
expect(Object.keys(resources).length).toBe(2);
```

**Assert total resources**:
```javascript
expectedState: {
    totalResources: { 1: 3 } // Total 3 iron ore across all entities
}
```

### 📊 Custom Recipes

If test needs custom recipes (different from default):

```javascript
{
    name: 'Fast smelting test',
    map: [...],
    legend: {...},
    recipes: {
        fast_iron_smelting: {
            recipe_id: 10,
            name: 'Fast Iron Smelting',
            inputs: [{ resource_id: 1, amount: 1 }],
            outputs: [{ resource_id: 2, amount: 2 }], // 2x output
            ticks: 15 // 2x faster
        }
    },
    ticks: 30,
    expectedState: {...}
}
```

Then use `recipe: 'fast_iron_smelting'` in legend.

### 🐛 Debugging Tests

**Enable tick logging**:
```javascript
const finalState = runSimulation(game, ticks, {
    logTicks: true // Logs every 10 ticks
});
```

**Print game state**:
```javascript
import { printGameState } from '../helpers/GameSimulator.js';

printGameState(finalState, game);
// Prints all entities, resources, crafting states
```

**Custom tick callback**:
```javascript
const finalState = runSimulation(game, ticks, {
    tickCallback: (tick, game) => {
        if (tick === 30) {
            console.log('At tick 30:', game.entityData);
        }
    }
});
```

### ✅ Test Examples

**Simple transport**:
```javascript
{
    name: 'Iron ore moves from conveyor 1 to conveyor 2',
    map: ['12#'],
    legend: {
        '1': { type: 'conveyor_right', resources: [{ id: 1, amount: 1 }] },
        '2': { type: 'conveyor_right' }
    },
    ticks: 30,
    expectedState: {
        entityResources: { 2: { 1: 1 } }
    }
}
```

**Production chain**:
```javascript
{
    name: 'Mine, transport, smelt - complete production',
    map: [
        '1234##',
        '######'
    ],
    legend: {
        '1': { type: 'mining_drill', resources: [{ id: 1, amount: 2 }] },
        '2': { type: 'manipulator_right' },
        '3': { type: 'conveyor_right' },
        '4': { type: 'furnace', recipe: 'iron_smelting' }
    },
    ticks: 90,
    expectedState: {
        entityResources: { 4: { 2: 1 } } // Iron plate in furnace
    }
}
```

**Splitter distribution**:
```javascript
{
    name: 'Splitter distributes 2 items to 2 outputs',
    map: [
        '#2#',
        '1S3',
        '#4#'
    ],
    legend: {
        '1': { type: 'conveyor_right', resources: [{ id: 1, amount: 2 }] },
        'S': { type: 'splitter_right' },
        '2': { type: 'conveyor_down' },
        '3': { type: 'conveyor_right' },
        '4': { type: 'conveyor_down' }
    },
    ticks: 60,
    expectedState: {
        entityResources: {
            2: { 1: 1 },
            3: { 1: 1 }
        }
    }
}
```

### 🎓 Best Practices

1. **Keep maps small** - 3-5 entities per test for clarity
2. **Test one behavior** - Each test should verify one game mechanic
3. **Use descriptive names** - Explain what the test proves
4. **Set realistic ticks** - Usually 30-90 ticks for most scenarios
5. **Verify end state** - Check exact resources, not just "has resources"
6. **Add comments** - Explain complex maps or expected behaviors
7. **Test edge cases** - Empty belts, full storages, no power, etc.

### 🔄 Workflow for Adding Tests

1. **Identify scenario** - What game behavior to test?
2. **Draw map on paper** - Visualize entity layout
3. **Define legend** - What each symbol represents
4. **Calculate ticks** - How many ticks for items to move/craft?
5. **Add test case** - Insert into testCases array
6. **Run test** - `npm test gameSimulation`
7. **Debug if fails** - Use `logTicks: true` and `printGameState()`
8. **Verify coverage** - Does it test what you intended?

## Integration Points

### Entity Architect
- Tests for entity placement validation
- Tests for entity behavior execution
- Use gameSimulation.test.js for entity interaction tests

### Recipe Balancer
- Tests for production rate calculations
- Tests for resource flow simulations
- Use gameSimulation.test.js for recipe chain tests

### Game Mechanic
- Tests for mechanic system logic
- Tests for network detection algorithms
- Use gameSimulation.test.js for complete mechanic workflows

### PixiJS Renderer
- Mock PIXI for unit tests
- Test rendering logic without actual canvas

## Checklist

Before submitting tests:
- [ ] All tests pass
- [ ] Coverage >80% on tested modules
- [ ] No flaky tests (run 10 times, all pass)
- [ ] Test names are descriptive
- [ ] Mocks properly reset between tests
- [ ] Edge cases tested
- [ ] Error conditions tested
- [ ] AAA pattern followed
- [ ] No implementation details tested
- [ ] Integration tests cover workflows

## Example Session

```
User: "Напиши тесты для ElectricitySystemManager"

Agent:
Создаю тесты для ElectricitySystemManager:

1. SETUP:
```javascript
// tests/unit/electricity/ElectricitySystemManager.test.js
[test file structure]
```

2. UNIT TESTS:
   ✅ loadSystems - loads data correctly
   ✅ loadSystems - clears previous data
   ✅ hasElectricity - returns true when sufficient
   ✅ hasElectricity - returns false when insufficient
   ✅ hasElectricity - handles entity not in system
   ✅ getSystemForEntity - returns system
   ✅ getSystemForEntity - returns null when not found
   ✅ isCoordinateElectrified - checks pylon radius
   ✅ getPowerRadius - converts tiles to pixels

3. EDGE CASES:
   ✅ Empty systems data
   ✅ Zero electricity amount
   ✅ Negative coordinates
   ✅ Very large systems (1000+ entities)

4. COVERAGE:
   - Statements: 94.23%
   - Branches: 90.91%
   - Functions: 100%
   - Lines: 94.23%

5. RUN:
```bash
npm run test electricity
# ✅ 9 passed
```

[js-test-writer agent mode]
```
