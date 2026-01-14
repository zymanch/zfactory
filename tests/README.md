# Tests

Unit and integration tests for ZFactory using Vitest.

## Quick Start

```bash
# Run all tests once
npm test

# Run tests in watch mode
npm run test:watch

# Run tests with UI
npm run test:ui

# Run tests with coverage report
npm run test:coverage
```

## Test Structure

```
tests/
├── unit/                    # Unit tests (isolated, mocked)
│   └── ElectricitySystemManager.test.js
├── integration/             # Integration tests (workflows)
│   └── crafting.test.js
├── helpers/                 # Test utilities
│   ├── mockGame.js         # Mock game object
│   ├── mockPixi.js         # Mock PixiJS objects
│   └── fixtures.js         # Common test data
└── setup.js                # Global test setup
```

## Writing Tests

### Unit Test Example

```javascript
import { describe, it, expect, beforeEach } from 'vitest';
import { createMockGame } from '../helpers/mockGame.js';
import MyModule from '@modules/MyModule.js';

describe('MyModule', () => {
    let module;
    let mockGame;

    beforeEach(() => {
        mockGame = createMockGame({
            // Custom game state
        });
        module = new MyModule(mockGame);
    });

    it('should do something', () => {
        const result = module.doSomething();
        expect(result).toBe(true);
    });
});
```

### Integration Test Example

```javascript
import { describe, it, expect } from 'vitest';
import { createMockGame } from '../helpers/mockGame.js';
import { entityFixtures, recipeFixtures } from '../helpers/fixtures.js';

describe('Crafting Workflow', () => {
    it('should complete full crafting cycle', () => {
        const mockGame = createMockGame({
            recipes: { 1: recipeFixtures.ironSmelting },
            entities: [entityFixtures.furnace1]
        });

        // Test complete workflow
    });
});
```

## Mocking

### Mock Game Object

```javascript
import { createMockGame, createMockEntity, createMockEntityType } from '../helpers/mockGame.js';

const mockGame = createMockGame({
    entities: [createMockEntity({ entity_id: 1, x: 64, y: 64 })],
    entityTypes: { 100: createMockEntityType({ entity_type_id: 100 }) }
});
```

### Mock PixiJS

```javascript
import { setupPixiMock, cleanupPixiMock } from '../helpers/mockPixi.js';

beforeEach(() => {
    setupPixiMock();
});

afterEach(() => {
    cleanupPixiMock();
});
```

### Using Fixtures

```javascript
import { entityTypeFixtures, resourceFixtures, recipeFixtures } from '../helpers/fixtures.js';

// Use predefined test data
const furnace = entityTypeFixtures.furnace;
const ironOre = resourceFixtures.ironOre;
const ironSmelting = recipeFixtures.ironSmelting;
```

## Coverage Goals

- **Core Logic** (managers, systems): 80%+
- **UI Modules** (tooltips, panels): 70%+
- **Renderers** (sprites, effects): 50%+

## Best Practices

1. **Isolate units** - Mock all dependencies in unit tests
2. **Test behaviors** - Focus on what code does, not how
3. **Use fixtures** - Reuse common test data
4. **Clear names** - Test names should describe expected behavior
5. **Arrange-Act-Assert** - Structure tests clearly
6. **Clean up** - Reset mocks and state after each test

## Debugging Tests

### Run single test file

```bash
npm test -- tests/unit/ElectricitySystemManager.test.js
```

### Run tests matching pattern

```bash
npm test -- -t "hasElectricity"
```

### Open UI for debugging

```bash
npm run test:ui
```

## CI Integration

Tests should be run in CI pipeline:

```bash
npm test -- --coverage --reporter=junit --outputFile=test-results.xml
```
