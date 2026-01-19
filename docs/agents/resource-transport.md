# Resource Transport System

Complete documentation for the tick-based resource movement system in zFactory.

## Table of Contents
- [System Overview](#system-overview)
- [Core Architecture](#core-architecture)
- [Tick-Based Movement](#tick-based-movement)
- [Two-Phase Movement](#two-phase-movement)
- [Underground Conveyors](#underground-conveyors)
- [Splitters](#splitters)
- [Manipulators](#manipulators)
- [Transfer Logic](#transfer-logic)
- [Database Schema](#database-schema)
- [API Endpoints](#api-endpoints)
- [Implementation Details](#implementation-details)

---

## System Overview

The resource transport system handles movement of resources between entities using:
- **Conveyors (Transporters)**: Move resources along paths
- **Splitters**: Distribute resources between multiple outputs
- **Manipulators**: Pick resources from one entity and place to another
- **Buildings**: Consume resources as inputs, produce outputs via crafting

All logic runs **client-side (JavaScript)**. Server (PHP) only handles saving/loading state.

### Key Design Decisions

1. **Tick-based positions** (not pixel-based) - eliminates floating-point errors
2. **Two-phase movement** - smooth transitions when entering from perpendicular directions
3. **Simultaneous transfers** - prevents deadlocks in circular conveyor loops
4. **Event-driven crafting** - starts on resource arrival, not polling

---

## Core Architecture

### File Structure
```
resources/js/modules/resourceTransport/
├── ResourceTransportManager.js  # Main controller, game loop integration
├── TransporterState.js          # Conveyor belt state & logic
├── ManipulatorState.js          # Manipulator arm state & logic
├── BuildingState.js             # Building/mining/storage state
├── SplitterState.js             # Splitter distribution logic
├── ResourceRenderer.js          # Visual rendering of resources
├── SpatialIndex.js              # Fast position lookups
└── UndergroundConveyorState.js  # (legacy, now merged into TransporterState)

resources/js/modules/
├── ConveyorConnectionHelper.js  # Data-driven conveyor connectivity (NEW 2026-01)
├── ConveyorVariantManager.js    # Texture variant calculation (0-15 based on neighbors)
└── conveyorManager.js           # Animation and rendering manager
```

### Manager Responsibilities

**ResourceTransportManager** is the orchestrator:
- Initializes all transporters, manipulators, buildings from entity data
- Runs animation ticks (every frame)
- Runs logic ticks (every 30 frames at 60 FPS = 0.5 seconds)
- Handles transfers between entities
- Manages auto-save to server

**ResourceRenderer** handles visuals only:
- Calculates sprite positions from state
- Applies transparency for underground resources
- Manages sprite lifecycle (create/update/destroy)

**ConveyorConnectionHelper** (NEW 2026-01):
- Data-driven conveyor connectivity using `entity_type.input_connections` and `output_connections`
- Replaces hardcoded orientation checks (removed `isMovingRight/Left/Up/Down` methods)
- Static methods for checking connection capabilities:
  - `canReceiveFrom(entityType, direction, position)` - check if entity accepts input from direction
  - `canOutputTo(entityType, direction, position)` - check if entity can output to direction
  - `isUndergroundIn(entityType)` - has input but no output (resources go underground)
  - `isUndergroundOut(entityType)` - has output but no input (resources come from underground)
  - `isSplitter(entityType)` - has multiple outputs (e.g., 2 perpendicular outputs)
- Supports positioned connections for multi-tile entities (e.g., `up_1`, `up_2`)
- Handles both array (from API) and string (legacy) formats with backward compatibility

---

## Tick-Based Movement

### Why Ticks Instead of Pixels?

**Problem with pixels:**
```javascript
// Old system (pixel-based)
position_px += 2.133;  // 64px / 30 ticks
// After 30 ticks: 2.133 * 30 = 63.99 ≠ 64 (rounding error)
// Resource stops at 63.99px, waits until next logic tick
```

**Solution with ticks:**
```javascript
// New system (tick-based)
ticks += 1;  // Increment by 1 each frame
// After 30 ticks: exactly 30 (no rounding)
// Transfer happens immediately when ticks === 30
```

### TransporterState Fields

```javascript
class TransporterState {
    // Movement
    ticks: number | null          // Current position: 0-30 (null if empty)
    TICKS_PER_TILE: 30           // Constant: full tile = 30 ticks

    // Resource
    resourceId: number | null
    resourceAmount: number
    fromDirection: 'up'|'down'|'left'|'right'  // Entry direction

    // State
    status: 'empty' | 'carrying' | 'waiting_transfer'

    // Configuration
    power: number                 // 100=normal, 200=2x speed
    orientation: 'up'|'down'|'left'|'right'

    // Underground
    isUndergroundIn: boolean
    isUndergroundOut: boolean
    undergroundPairId: string | null
}
```

### Speed and Power

```javascript
getTickIncrement() {
    return this.power / 100;
}

// power=100 → increment=1 → 30 ticks for full tile → normal speed
// power=200 → increment=2 → 15 ticks for full tile → 2x speed
// power=50  → increment=0.5 → 60 ticks for full tile → 0.5x speed
```

### Position Calculation

Position in pixels is calculated **on-the-fly** when rendering:

```javascript
getPositionPx() {
    const tileWidth = 64;  // game.config.tileWidth

    // Map ticks (0-30) to position (-32px to +32px)
    // Formula: position = (ticks / 30) * 64 - 32
    return (this.ticks / this.TICKS_PER_TILE) * tileWidth - (tileWidth / 2);
}

// Examples:
// ticks=0  → position=-32px (entry edge)
// ticks=15 → position=0px   (center)
// ticks=30 → position=+32px (exit edge)
```

---

## Two-Phase Movement

Resources move in **two distinct phases** to handle perpendicular entries smoothly.

### Phase 1: Entry to Center

Resource moves from entry point to conveyor center, direction determined by **fromDirection**.

```
ticks: 0 → 15
position: -32px → 0px (center)
direction: depends on fromDirection
```

### Phase 2: Center to Exit

Resource moves from center to exit, direction determined by **orientation**.

```
ticks: 15 → 30
position: 0px → +32px
direction: depends on orientation
```

### Example: Perpendicular Entry

```
Conveyor A (down) → Conveyor B (right)

Phase 1 on Conveyor B:
- ticks: 0→15, position: -32px→0px
- fromDirection='down' → moves UPWARD to center
- Result: resource visually rises from bottom edge to center

Phase 2 on Conveyor B:
- ticks: 15→30, position: 0px→+32px
- orientation='right' → moves RIGHTWARD from center
- Result: resource moves right from center to exit

Visual path: ↑ (up to center) then → (right to exit)
```

### Animation Loop

```javascript
// ResourceTransportManager.updateTransporterAnimation()
updateTransporterAnimation() {
    for (const [entityId, state] of this.transporters) {
        if (state.isEmpty()) continue;
        if (state.status === 'waiting_transfer') continue;

        // Increment ticks based on power
        const increment = state.getTickIncrement();
        state.ticks += increment;

        // Check if reached end
        if (state.ticks >= state.TICKS_PER_TILE) {
            state.ticks = state.TICKS_PER_TILE;
            state.status = 'waiting_transfer';
        }
    }
}
```

### Rendering Phase Detection

```javascript
// ResourceRenderer.renderConveyorResource()
const position_px = state.getPositionPx();
const movementPhase = state.getMovementPhase();  // 1 if pos<0, else 2

if (movementPhase === 1) {
    // Phase 1: apply offset based on fromDirection
    const distance = Math.abs(position_px);
    switch (state.fromDirection) {
        case 'up':   offsetY = -distance; break;  // moving DOWN to center
        case 'down': offsetY = distance;  break;  // moving UP to center
        case 'left': offsetX = -distance; break;  // moving RIGHT to center
        case 'right': offsetX = distance; break;  // moving LEFT to center
    }
} else {
    // Phase 2: apply offset based on orientation
    const distance = position_px;
    switch (state.orientation) {
        case 'right': offsetX = distance;  break;
        case 'down':  offsetY = distance;  break;
        case 'left':  offsetX = -distance; break;
        case 'up':    offsetY = -distance; break;
    }
}

sprite.x = centerX + offsetX;
sprite.y = centerY + offsetY;
```

---

## Underground Conveyors

Underground conveyors allow resources to "tunnel" under obstacles, becoming semi-transparent during underground travel.

### Pairing Logic

```javascript
// ResourceTransportManager.calculateLinks()
// Phase 0: Calculate underground conveyor pairs (IN → OUT)

for (const [entityId, state] of this.transporters) {
    if (!state.isUndergroundIn) continue;

    // Find nearest OUT with same orientation
    const candidates = [];
    for (const [outId, outState] of this.transporters) {
        if (!outState.isUndergroundOut) continue;
        if (outState.orientation !== state.orientation) continue;

        const dx = outState.x - state.x;
        const dy = outState.y - state.y;

        // Check if OUT is in correct direction
        const isAligned =
            (state.orientation === 'right' && dy === 0 && dx > 0) ||
            (state.orientation === 'down' && dx === 0 && dy > 0) ||
            (state.orientation === 'left' && dy === 0 && dx < 0) ||
            (state.orientation === 'up' && dx === 0 && dy < 0);

        if (isAligned) {
            const distance = Math.abs(dx + dy);
            candidates.push({ id: outId, distance });
        }
    }

    // Select nearest
    if (candidates.length > 0) {
        candidates.sort((a, b) => a.distance - b.distance);
        state.undergroundPairId = candidates[0].id;
    }
}
```

### Underground Transfer

```javascript
// processTransporterTransfers() - Phase 0
for (const [entityId, state] of this.transporters) {
    if (!state.isUndergroundIn) continue;
    if (state.status !== 'waiting_transfer') continue;
    if (!state.undergroundPairId) continue;

    const outState = this.transporters.get(state.undergroundPairId);
    if (!outState || !outState.isEmpty()) continue;

    // Calculate underground distance (edge-to-edge in tiles)
    const dx = Math.abs(outState.x - state.x);
    const dy = Math.abs(outState.y - state.y);
    const distanceTiles = (dx + dy) - 1;

    // Transfer with negative ticks for underground travel
    const undergroundTicks = -(distanceTiles * state.TICKS_PER_TILE);

    outState.resourceId = state.resourceId;
    outState.resourceAmount = state.resourceAmount;
    outState.fromDirection = this.calculateFromDirection(state.undergroundPairId, entityId);
    outState.ticks = undergroundTicks;  // Negative ticks!
    outState.status = 'carrying';

    state.clear();
}
```

### Underground Transparency

Resources are semi-transparent (30% alpha) while "underground":

```javascript
// ResourceRenderer.renderConveyorResource()
const position_px = state.getPositionPx();

// Underground when:
// - On IN belt AFTER reaching center (position >= 0)
// - On OUT belt BEFORE reaching surface (position < 0)
if ((state.isUndergroundIn && position_px >= 0) ||
    (state.isUndergroundOut && position_px < 0)) {
    sprite.alpha = 0.3;  // Semi-transparent
} else {
    sprite.alpha = 1.0;  // Fully visible
}
```

### Example: 3-Tile Underground

```
IN at (10,5) → OUT at (13,5)  [orientation='right']

Distance: |13-10| + |5-5| - 1 = 2 tiles (edge-to-edge)
Underground ticks: -(2 * 30) = -60 ticks

Timeline on OUT conveyor:
ticks=-60 → -30 → 0 → +30
  ↓           ↓     ↓     ↓
underground  surf  cntr  exit
(alpha=0.3)  (1.0) (1.0) (1.0)

Visual position:
ticks=-60 → position=-128px (2 tiles left of center)
ticks=-30 → position=-64px  (1 tile left)
ticks=0   → position=-32px  (entry edge, becomes visible)
ticks=15  → position=0px    (center)
ticks=30  → position=+32px  (exit)
```

---

## Splitters

Splitters distribute resources to multiple outputs in configurable patterns.

### Splitter State

```javascript
class SplitterState {
    entityId: string
    orientation: 'up'|'down'|'left'|'right'
    power: number

    // Distribution mode
    distributionMode: 'left' | 'right' | 'split'
    // - 'left': all to left output
    // - 'right': all to right output
    // - 'split': alternate between left/right

    // Current output selection (for 'split' mode)
    currentOutputIndex: 0 | 1  // 0=left, 1=right

    // Targets
    leftTargetId: string | null
    rightTargetId: string | null
    straightTargetId: string | null
}
```

### Distribution Logic

```javascript
// SplitterState.getTargetForResource()
getTargetForResource() {
    switch (this.distributionMode) {
        case 'left':
            return this.leftTargetId;

        case 'right':
            return this.rightTargetId;

        case 'split':
            // Alternate between outputs
            const target = this.currentOutputIndex === 0
                ? this.leftTargetId
                : this.rightTargetId;

            // Toggle for next resource
            this.currentOutputIndex = 1 - this.currentOutputIndex;

            return target;
    }
}
```

---

## Manipulators

Manipulators pick resources from source and place to target using a robotic arm.

### Manipulator State

```javascript
class ManipulatorState {
    entityId: string
    orientation: 'up'|'down'|'left'|'right'
    power: number
    reach: 1 | 2  // tiles

    // Arm state
    status: 'idle' | 'picking' | 'carrying' | 'placing'
    position_px: number  // -centerPx (source) to +centerPx (target)

    // Resource
    resourceId: number | null
    resourceAmount: number

    // Targets
    sourceEntityId: string | null  // Opposite direction
    targetEntityId: string | null  // Same direction as orientation

    // Position calculation
    centerPositionPx: number  // reach * tileWidth * 1.5
}
```

### Source and Target Calculation

```javascript
// ManipulatorState.constructor
const tileWidth = game.config.tileWidth;
const dx = { up: 0, down: 0, left: -reach, right: reach }[orientation];
const dy = { up: -reach, down: reach, left: 0, right: 0 }[orientation];

// Source is OPPOSITE to orientation
this.sourceX = x - dx;
this.sourceY = y - dy;

// Target is IN DIRECTION of orientation
this.targetX = x + dx;
this.targetY = y + dy;

// Arm reach distance
this.centerPositionPx = reach * tileWidth * 1.5;
```

### Arm Animation

```javascript
// ResourceTransportManager.updateManipulatorAnimation()
updateManipulatorAnimation() {
    for (const [entityId, state] of this.manipulators) {
        const speed = 2;  // pixels per tick

        switch (state.status) {
            case 'picking':
                // Move arm towards source (-centerPx)
                state.position_px = Math.max(-state.centerPositionPx,
                                            state.position_px - speed);
                break;

            case 'carrying':
                // Move arm towards target (+centerPx)
                state.position_px = Math.min(state.centerPositionPx,
                                           state.position_px + speed);
                break;
        }
    }
}
```

### Action Processing

```javascript
// ResourceTransportManager.processManipulatorActions()
processManipulatorActions() {
    for (const [entityId, state] of this.manipulators) {
        switch (state.status) {
            case 'idle':
                // Try to start picking
                this.tryStartManipulatorPicking(state);
                break;

            case 'picking':
                // Check if arm reached source
                if (state.position_px <= -state.centerPositionPx) {
                    const resource = this.takeResourceFrom(state.sourceEntityId, 'manipulator');
                    if (resource) {
                        state.resourceId = resource.resourceId;
                        state.resourceAmount = resource.amount;
                        state.status = 'carrying';
                    } else {
                        state.status = 'idle';
                        state.position_px = 0;
                    }
                }
                break;

            case 'carrying':
                // Check if arm reached target
                if (state.position_px >= state.centerPositionPx) {
                    state.status = 'placing';
                }
                break;

            case 'placing':
                // Try to place resource
                const placed = this.placeResourceTo(
                    state.targetEntityId,
                    state.resourceId,
                    state.resourceAmount
                );
                if (placed) {
                    state.clear();  // Back to idle
                }
                break;
        }
    }
}
```

---

## Transfer Logic

### Simultaneous Transfers (for cycles)

Conveyors in circular loops use **simultaneous transfers** to prevent deadlocks.

```javascript
// processTransporterTransfers()

// Phase 1: Mark who will transfer
for (const [entityId, state] of this.transporters) {
    state.willTransfer = false;

    if (state.status !== 'waiting_transfer') continue;
    if (state.isUndergroundIn) continue;  // Handled separately
    if (!state.targetEntityId) continue;

    const canAccept = this.canEntityAccept(
        state.targetEntityId,
        state.resourceId,
        state.resourceAmount
    );

    if (canAccept === 'yes') {
        state.willTransfer = true;
    } else if (canAccept === 'yes_if_freed') {
        // Target is full but also waiting to transfer (cycle case)
        const targetState = this.transporters.get(state.targetEntityId);
        if (targetState && targetState.status === 'waiting_transfer') {
            state.willTransfer = true;  // Simultaneous transfer
        }
    }
}

// Phase 2: Collect all transfers
const transfers = [];
for (const [entityId, state] of this.transporters) {
    if (!state.willTransfer) continue;

    transfers.push({
        fromId: entityId,
        toId: state.targetEntityId,
        resourceId: state.resourceId,
        resourceAmount: state.resourceAmount
    });
}

// Phase 3: Clear all sources atomically
for (const t of transfers) {
    const fromState = this.transporters.get(t.fromId);
    fromState.clear();
}

// Phase 4: Fill all targets atomically
for (const t of transfers) {
    const targetState = this.transporters.get(t.toId);
    if (targetState) {
        const fromDirection = this.calculateFromDirection(t.toId, t.fromId);
        targetState.setResource(t.resourceId, t.resourceAmount, fromDirection);
    }
}
```

### Priority Rules

When multiple sources feed into one conveyor:

1. **Straight source** (same orientation) has priority
2. **Side sources** alternate in round-robin fashion
3. Selection happens during Phase 1 of transfer logic

```javascript
// canEntityAccept()
canEntityAccept(entityId, resourceId, amount) {
    const state = this.transporters.get(entityId);
    if (!state) return 'no';

    if (state.isEmpty()) {
        return 'yes';
    }

    if (state.status === 'waiting_transfer') {
        // Full but waiting to transfer
        // Will be freed if it also transfers this tick
        return 'yes_if_freed';
    }

    return 'no';
}
```

---

## Database Schema

### entity_resource Table

Stores resources on conveyors and in buildings.

| Column         | Type         | Description                           |
|----------------|--------------|---------------------------------------|
| entity_resource_id | INT UNSIGNED | Primary key (auto-increment)       |
| entity_id      | INT UNSIGNED | FK to entity                          |
| resource_id    | INT UNSIGNED | FK to resource                        |
| amount         | INT UNSIGNED | Stack size                            |
| position_px    | INT NULL     | Position 0-64 (NULL = building storage) |
| from_direction | VARCHAR(10)  | 'up','down','left','right' (conveyors only) |
| status         | VARCHAR(20)  | 'carrying', 'waiting_transfer'        |
| last_output_direction | VARCHAR(10) | For splitters                  |

**Key points:**
- `position_px=NULL` → building/storage inventory
- `position_px=0-64` → resource on conveyor (0=edge, 32=center, 64=exit)
- DB stores pixels, code uses ticks (conversion happens in loadFromSaved/getSaveData)

### ship_entity_resource Table

Same structure as entity_resource but for ship entities.

| Column         | Type         | Description                           |
|----------------|--------------|---------------------------------------|
| ship_entity_resource_id | INT UNSIGNED | Primary key             |
| ship_entity_id | INT UNSIGNED | FK to ship_entity                     |
| (all other columns same as entity_resource) | | |

### entity_crafting Table

Stores active crafting processes.

| Column          | Type         | Description              |
|-----------------|--------------|--------------------------|
| entity_crafting_id | INT UNSIGNED | Primary key           |
| entity_id       | INT UNSIGNED | FK to entity             |
| recipe_id       | INT UNSIGNED | FK to recipe             |
| ticks_remaining | INT UNSIGNED | Ticks until complete     |

### ship_entity_crafting Table

Same structure as entity_crafting but for ship entities.

---

## API Endpoints

### GET /game/entities

Returns all entities with embedded state.

**Response:**
```json
{
  "entities": [
    {
      "entity_id": 196,
      "entity_type_id": 100,
      "x": 10,
      "y": 5,
      "state": "built",
      "durability": 100,

      // Optional: if building has resources
      "resources": [
        {"resource_id": 2, "amount": 50}
      ],

      // Optional: if building is crafting
      "craftingState": {
        "recipe_id": 3,
        "ticks_remaining": 45
      },

      // Optional: if conveyor has resource
      "transportState": {
        "resource_id": 2,
        "amount": 1,
        "position_px": 32,
        "from_direction": "down",
        "status": "carrying"
      }
    }
  ]
}
```

### POST /game/save-state

Saves current transport state.

**Request:**
```json
{
  "entityResources": [
    {"entity_id": 1, "resource_id": 2, "amount": 50}
  ],
  "craftingStates": [
    {"entity_id": 1, "recipe_id": 3, "ticks_remaining": 45}
  ],
  "transportStates": {
    "entity_196": {
      "resource_id": 2,
      "amount": 1,
      "position_px": 32,
      "from_direction": "down",
      "status": "carrying"
    }
  }
}
```

**Note:** `transportStates` is now a dictionary (2026-01 refactoring) instead of array.

---

## Implementation Details

### Game Loop Integration

```javascript
// game.js - main game loop
this.graphics.getTicker().add(() => {
    this.resourceTransport.tick();
    this.resourceRenderer.render();
});
```

### ResourceTransportManager.tick()

```javascript
tick() {
    if (!this.initialized) return;

    // Animation tick (every frame) - smooth visual movement
    this.updateTransporterAnimation();
    this.updateSplitterAnimation();
    this.updateManipulatorAnimation();

    // Logic tick (every N frames) - state changes, transfers, crafting
    this.logicTickCounter++;
    if (this.logicTickCounter >= this.LOGIC_TICK_INTERVAL) {
        this.logicTickCounter = 0;
        this.logicTick();
    }

    // Auto-save check
    this.checkAutoSave();
}
```

### logicTick()

```javascript
logicTick() {
    // Update crafting progress and completion
    this.updateCrafting();

    // Check conveyor status transitions
    this.updateTransporterStatus();

    // Process transfers between conveyors
    this.processTransporterTransfers();

    // Process manipulator state transitions (pickup/place actions)
    this.processManipulatorActions();
}
```

### Auto-Save

```javascript
checkAutoSave() {
    const now = Date.now();

    if (now - this.lastSaveTime >= this.autoSaveInterval) {
        this.saveToServer();
        this.lastSaveTime = now;
    }
}
```

---

## Testing

### Unit Tests

```javascript
// tests/unit/transporterState.test.js
import { TransporterState } from '@/modules/resourceTransport/TransporterState.js';

describe('TransporterState', () => {
    it('converts ticks to position correctly', () => {
        const state = new TransporterState(entity, entityType, mockGame);

        state.ticks = 0;
        expect(state.getPositionPx()).toBe(-32);  // Entry edge

        state.ticks = 15;
        expect(state.getPositionPx()).toBe(0);    // Center

        state.ticks = 30;
        expect(state.getPositionPx()).toBe(32);   // Exit edge
    });

    it('accounts for power in tick increment', () => {
        const state = new TransporterState(entity, entityType, mockGame);

        state.power = 100;
        expect(state.getTickIncrement()).toBe(1);

        state.power = 200;
        expect(state.getTickIncrement()).toBe(2);
    });
});
```

### Integration Tests

Test full transfer cycles using FakeGraphicsEngine to avoid PixiJS dependencies.

---

## Common Issues & Solutions

### Issue: Resources stuck at conveyor end

**Symptom:** Resource reaches end but waits before transferring

**Cause:** Animation tick incremented past 30 but logic tick hasn't run yet

**Solution:** Animation now caps at exactly 30 and sets status='waiting_transfer'

```javascript
if (state.ticks >= state.TICKS_PER_TILE) {
    state.ticks = state.TICKS_PER_TILE;
    state.status = 'waiting_transfer';
}
```

### Issue: Jerky movement at low FPS

**Symptom:** Resource moves in large jumps instead of smoothly

**Cause:** At FPS=3, 30 ticks = 10 seconds of waiting

**Solution:** Logic tick interval is fixed at 30 **game ticks**, not real time. At lower FPS, resources move slower but still smooth frame-by-frame.

### Issue: Underground resources visible too early

**Symptom:** Resource appears on OUT conveyor while still underground

**Cause:** Transparency check using wrong position threshold

**Solution:** Check `position_px < 0` for underground OUT conveyors

```javascript
if ((state.isUndergroundIn && position_px >= 0) ||
    (state.isUndergroundOut && position_px < 0)) {
    sprite.alpha = 0.3;  // Underground
}
```

---

## Future Improvements

### Potential Optimizations

1. **Spatial culling**: Skip animation for off-screen conveyors
2. **Batch transfers**: Group transfers by target type
3. **Dirty flags**: Only update sprites when state changes

### Feature Ideas

1. **Express conveyors**: power=300 for 3x speed
2. **Dual-lane conveyors**: Two resources per conveyor (requires DB changes)
3. **Filters**: Only accept specific resources
4. **Priority splitters**: Send to preferred output first

---

## Version History

- **2026-01-16**: Tick-based system implemented (replaces pixel-based)
- **2026-01-15**: Two-phase movement system
- **2026-01-14**: Underground conveyor support
- **2026-01-10**: Ship entity resource tables created
- **2025-12**: Initial transport system
