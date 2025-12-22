# Resource Transport System

System for moving resources between entities via conveyors and manipulators.

## Overview

The resource transport system handles:
- Moving resources on conveyor belts
- Manipulator pick/place operations
- Building crafting processes
- Mining extraction from deposits

All logic runs client-side (JavaScript). Server (PHP) handles saving/loading state.

## Entity Types and Capabilities

| Type        | Stores Resources | Accepts      | Gives        | Crafts | Moves |
|-------------|------------------|--------------|--------------|--------|-------|
| building    | Yes              | recipe input | recipe output| Yes    | No    |
| mining      | Yes              | No           | recipe output| Auto   | No    |
| storage     | Yes              | any          | any          | No     | No    |
| transporter | Yes (1 stack)    | any          | any          | No     | Yes   |
| manipulator | Yes (1 stack)    | any          | any          | No     | Yes   |
| resource    | Yes (deposit)    | No           | No           | No     | No    |

## Resource Flow

```
[Resource Entity] ──placement──> [Mining Drill] ──auto-craft──> raw resources
                                      │
                                      ▼ (manipulator takes)
[Building] <──manipulator puts── [Manipulator] <──takes── [Conveyor Chain]
    │
    │ crafts input → output
    ▼
[Building] ──manipulator takes──> [Manipulator] ──puts──> [Storage/Conveyor]
```

## Conveyor Belt Rules

### Movement
- Resources move along conveyor orientation (up/down/left/right)
- Speed = `power / 100` tiles per second (power=100 is baseline)
- If resource enters from side, it first moves to center lane

### Transfer Priority
1. **Straight source** (same orientation) has priority
2. **Side sources** alternate in round-robin fashion
3. **Manipulator** has lower priority than conveyor

### Circular Conveyors
Conveyors in a loop use **simultaneous transfers**:
1. All waiting conveyors mark `willTransfer = true` if target also waiting
2. All sources cleared atomically
3. All targets filled atomically
4. Resources move in circle without deadlock

## Manipulator Rules

### Reach
- **Short Manipulator**: 1 tile reach
- **Long Manipulator**: 2 tiles reach

### Direction (based on orientation)
- Source = tile **opposite** to orientation
- Target = tile **in direction** of orientation

Example: `orientation=right` → takes from LEFT, places to RIGHT

### What Can Be Taken/Placed

| Source/Target | Can Take                  | Can Place                 |
|---------------|---------------------------|---------------------------|
| transporter   | any (from any position)   | only if empty             |
| manipulator   | any (when arm at end)     | only if idle              |
| building      | output resources only     | input resources only      |
| mining        | non-deposit resources     | nothing                   |
| storage       | any resource              | any resource              |

## Crafting System

### Building Crafting
1. Accumulate input resources (max 10 each)
2. When enough for recipe, start crafting
3. Input resources consumed immediately
4. After `ticks * (100 / power)` ticks, output appears
5. Max 10 output resources before crafting pauses

### Auto-Start Crafting Triggers

Crafting auto-starts when **any** of these events occur:

| Trigger                     | Description                                      |
|-----------------------------|--------------------------------------------------|
| Game loads                  | All buildings checked via `tryStartAllCrafts()`  |
| Resource received           | Building checked when resource placed to it      |
| Recipe completes            | Building checked immediately after output added  |

This ensures buildings start crafting as soon as possible without polling every tick.

### Mining Auto-Craft
1. Mining drill auto-starts crafting from deposit
2. `deposit → raw` (e.g., Iron Deposit → Iron Ore)
3. Continues until deposit exhausted or output full (10)

## State Classes

### TransporterState
```javascript
{
    entityId: number,
    orientation: 'up' | 'down' | 'left' | 'right',
    power: number,
    status: 'empty' | 'carrying' | 'waiting_transfer',
    resourceId: number | null,
    resourceAmount: number,
    resourcePosition: 0.0 - 1.0,  // 0=start, 1=end
    lateralOffset: -0.5 - 0.5,    // for side entry
    targetEntityId: number | null,
    sourceEntityIds: number[],
    straightSourceId: number | null
}
```

### ManipulatorState
```javascript
{
    entityId: number,
    orientation: 'up' | 'down' | 'left' | 'right',
    power: number,
    reach: 1 | 2,
    status: 'idle' | 'picking' | 'carrying' | 'placing',
    resourceId: number | null,
    resourceAmount: number,
    armPosition: 0.0 - 1.0,  // 0=source, 1=target
    sourceEntityId: number | null,
    targetEntityId: number | null
}
```

### BuildingState
```javascript
{
    entityId: number,
    type: 'building' | 'mining' | 'storage',
    power: number,
    resources: Map<resourceId, amount>,
    recipeIds: number[],
    inputResourceIds: Set<number>,
    outputResourceIds: Set<number>,
    craftingRecipeId: number | null,
    craftingTicksRemaining: number,
    maxSlots: number  // for storage only
}
```

## Game Loop (per tick)

```javascript
tick() {
    this.updateCrafting();           // Complete crafts, add outputs (triggers auto-start)
    this.updateTransporters();       // Move resources along belts
    this.processTransporterTransfers(); // Transfer between belts (triggers auto-start on receive)
    this.updateManipulators();       // Move manipulator arms (triggers auto-start on place)
    this.checkAutoSave();            // Save if interval passed
}
```

**Note:** Crafting auto-starts are event-driven (see "Auto-Start Crafting Triggers"), not polled every tick.

## Database Tables

### entity_crafting
Stores active crafting processes.

| Column           | Type         | Description              |
|------------------|--------------|--------------------------|
| entity_id        | INT UNSIGNED | FK to entity (PK)        |
| recipe_id        | INT UNSIGNED | FK to recipe             |
| ticks_remaining  | INT UNSIGNED | Ticks until complete     |

### entity_transport
Stores conveyor/manipulator states.

| Column         | Type                    | Description              |
|----------------|-------------------------|--------------------------|
| entity_id      | INT UNSIGNED            | FK to entity (PK)        |
| resource_id    | INT UNSIGNED NULL       | FK to resource           |
| amount         | INT UNSIGNED            | Stack size               |
| position       | DECIMAL(5,4)            | 0.0000 - 1.0000          |
| lateral_offset | DECIMAL(5,4)            | -0.5000 - 0.5000         |
| arm_position   | DECIMAL(5,4)            | For manipulators         |
| status         | ENUM(...)               | Current state            |

### resource.max_stack (new column)
Maximum stack size for each resource type.

## Auto-Save

Configured in `static_config.php`:
```php
'params' => [
    'auto_save_interval' => 60,  // seconds
]
```

Saves:
- `entity_resource` - building/storage contents
- `entity_crafting` - active craft processes
- `entity_transport` - conveyor/manipulator states

## Removing Entities

### Mining Drill Removal
When mining drill is removed:
1. Find remaining deposit resource
2. Create new resource entity at same position
3. Transfer deposit amount to new entity

### Other Entities
Resources inside are lost when entity is removed.

## Visualization

### Resource on Conveyor
- 16x16 icon moves along belt
- Position interpolated from `resourcePosition`
- Lateral offset applied if entered from side

### Crafting Progress Bar
- Displayed above building
- Width = entity width
- Green fill = progress percentage
- Output resource icon shown next to bar

## Files

### JavaScript
```
resources/js/modules/resourceTransport/
├── ResourceTransportManager.js  # Main controller
├── TransporterState.js          # Conveyor state
├── ManipulatorState.js          # Manipulator state
├── BuildingState.js             # Building/mining/storage state
├── SpatialIndex.js              # Fast position lookup
└── ResourceRenderer.js          # Visual rendering
```

### PHP
```
src/actions/game/SaveState.php   # Save endpoint
src/models/EntityCrafting.php    # Crafting AR model
src/models/EntityTransport.php   # Transport AR model
```

## API Endpoints

### POST /game/save-state
Save current transport state.

**Request:**
```json
{
    "entityResources": [
        {"entity_id": 1, "resource_id": 2, "amount": 50}
    ],
    "craftingStates": [
        {"entity_id": 1, "recipe_id": 3, "ticks_remaining": 45}
    ],
    "transporterStates": [
        {"entity_id": 5, "resource_id": 2, "amount": 1, "position": 0.5, "status": "carrying"}
    ],
    "manipulatorStates": [
        {"entity_id": 10, "resource_id": 2, "amount": 1, "arm_position": 0.7, "status": "carrying"}
    ]
}
```

### GET /game/config (updated)
Now includes `autoSaveInterval` in config.
