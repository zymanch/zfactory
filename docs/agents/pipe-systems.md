# Pipes and Fluids System

## Overview

The ZFactory pipe and fluid system implements a simplified pooled fluid transport mechanism. Fluids are extracted by pumps, transported through pipes, and consumed by buildings.

### Key Principles

- **Pooled System**: All connected pipes share a common fluid pool
- **Instant Distribution**: Fluid added anywhere in a system is instantly available everywhere
- **No Mixing**: Each pipe system can only contain one fluid type
- **Capacity-Based**: Each pipe/tank has a capacity (entity_type.power field)
- **4 Fluid Types**: Water (300), Crude Oil (301), Natural Gas (302), Lava (303)

## Database Schema

### Fluid Resources

```sql
-- Fluid resources (consumable liquids)
resource_id | name          | type
------------|---------------|-------
300         | Water         | fluid
301         | Crude Oil     | fluid
302         | Natural Gas   | fluid
303         | Lava          | fluid
```

### Deposit Resources

```sql
-- Deposit resources (what pumps extract from)
resource_id | name          | type
------------|---------------|--------
400         | Water Source  | deposit
401         | Oil Well      | deposit
402         | Gas Vent      | deposit
403         | Lava Pool     | deposit
```

### Pipe System Tables

```sql
-- pipe_system: Represents a connected network of pipes
CREATE TABLE pipe_system (
    pipe_system_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    region_id INT UNSIGNED NOT NULL,
    resource_id INT UNSIGNED NULL,           -- Current fluid type (NULL = empty)
    current_amount INT UNSIGNED DEFAULT 0,    -- Current fluid quantity
    max_capacity INT UNSIGNED DEFAULT 0,      -- Sum of all pipes' capacities
    INDEX idx_region (region_id)
);

-- pipe_system_member: Links pipes to their system
CREATE TABLE pipe_system_member (
    pipe_system_id INT UNSIGNED NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (pipe_system_id, entity_id)
);
```

## Entity Types

### Pipes

| ID  | Type | Name                     | Power (Capacity) | Notes                           |
|-----|------|--------------------------|------------------|---------------------------------|
| 131 | pipe | Pipe (Horizontal)        | 100              | Basic horizontal pipe           |
| 132 | pipe | Pipe (Vertical)          | 100              | Basic vertical pipe             |
| 135 | pipe | Underground Pipe (Input) | 100              | Entrance for underground tunnel |
| 136 | pipe | Underground Pipe (Output)| 100              | Exit for underground tunnel     |
| 140 | pipe | Fluid Tank (Small)       | 5000             | 2x2 storage tank                |
| 141 | pipe | Fluid Tank (Large)       | 25000            | 3x3 storage tank                |

### Pumps (Mining Buildings)

| ID  | Type   | Name         | Power | Requires              | Output      |
|-----|--------|--------------|-------|-----------------------|-------------|
| 145 | mining | Water Pump   | 100   | Water landing (id=4)  | Water (300) |
| 146 | mining | Oil Pump     | 100   | Oil Well deposit (13) | Oil (301)   |
| 147 | mining | Gas Pump     | 100   | Gas Vent deposit (14) | Gas (302)   |
| 148 | mining | Lava Pump    | 100   | Lava landing (id=6)   | Lava (303)  |

### Deposit Types

| ID | Type | Name     | Resource ID | Amount | Notes                      |
|----|------|----------|-------------|--------|----------------------------|
| 13 | ore  | Oil Well | 401         | 10000  | For oil pump placement     |
| 14 | ore  | Gas Vent | 402         | 10000  | For gas pump placement     |

## Recipes

Pumps use the crafting/recipe system to extract fluids:

```sql
-- Water extraction (infinite - no input)
output_resource_id: 300 (Water)
output_amount: 1
input1_resource_id: NULL
input1_amount: NULL
ticks: 30

-- Lava extraction (infinite - no input)
output_resource_id: 303 (Lava)
output_amount: 1
input1_resource_id: NULL
input1_amount: NULL
ticks: 30

-- Oil extraction (from Oil Well deposit)
output_resource_id: 301 (Crude Oil)
output_amount: 1
input1_resource_id: 401 (Oil Well)
input1_amount: 1
ticks: 30

-- Gas extraction (from Gas Vent deposit)
output_resource_id: 302 (Natural Gas)
output_amount: 1
input1_resource_id: 402 (Gas Vent)
input1_amount: 1
ticks: 30
```

## Backend Implementation

### PipeSystemManager.php

Location: `src/bl/pipes/PipeSystemManager.php`

**Key Methods:**

```php
// Recalculate all pipe systems in a region using BFS
public static function recalculateSystems(int $regionId): void

// Add fluid to a system (checks for mixing and capacity)
public static function addFluid(int $pipeEntityId, int $resourceId, int $amount): bool

// Remove fluid from a system
public static function takeFluid(int $pipeEntityId, int $resourceId, int $amount): int

// Get system info for tooltips
public static function getSystemInfo(int $pipeEntityId): ?array
```

**BFS Algorithm:**

1. Load all pipe entities in region
2. For each unprocessed pipe:
   - Run BFS to find all connected pipes (4-directional adjacency)
   - Create new pipe_system with total capacity
   - Link all found pipes as members

**Triggers:**

- After creating a pipe entity → recalculateSystems()
- After deleting a pipe entity → recalculateSystems()

### Pump Entity Types

Location: `src/bl/entity/types/mining/`

All pumps extend `FluidPumpEntityType`:

```php
abstract class FluidPumpEntityType extends AbstractEntityType
{
    abstract public function getOutputResourceId(): int;
    abstract public function canPlaceAt(?int $landingId, ?int $depositTypeId): bool;

    public function getExtractionRate(): int {
        return (int)($this->power / 100);
    }

    public function getTypeCategory(): string {
        return 'mining';
    }
}
```

**Placement Validation:**

- Water Pump: requires `landing_id = 4` (water)
- Lava Pump: requires `landing_id = 6` (lava)
- Oil Pump: requires `deposit_type_id = 13` (oil well)
- Gas Pump: requires `deposit_type_id = 14` (gas vent)

## Frontend Implementation

### PipeSystemManager.js

Location: `resources/js/modules/pipes/PipeSystemManager.js`

```javascript
class PipeSystemManager {
    constructor(game) {
        this.systems = new Map(); // pipe_system_id => system data
        this.entityToSystem = new Map(); // entity_id => pipe_system_id
    }

    loadSystems(systemsData) { /* Load systems from server */ }
    getSystemForEntity(entityId) { /* Get system for pipe */ }
    getSystemInfo(entityId) { /* Get tooltip info */ }
    addFluid(pipeEntityId, resourceId, amount) { /* Client-side validation */ }
    takeFluid(pipeEntityId, resourceId, amount) { /* Client-side validation */ }
}
```

### PipeRenderer.js

Location: `resources/js/modules/rendering/PipeRenderer.js`

**Sprite Structure:**

Each pipe sprite consists of:
- Base pipe sprite (64x64px)
- Fluid visualization (10x10px colored square at center)

**Fluid Colors:**

- Water: `#3498db` (blue)
- Crude Oil: `#2c3e50` (dark gray/black)
- Natural Gas: `#95a5a6` (light gray)
- Lava: `#e74c3c` (red/orange)

### PipeConnectionManager.js

Location: `resources/js/modules/pipes/PipeConnectionManager.js`

**Connection Variants (16 types):**

Uses 4-bit system: right(1), down(2), left(4), up(8)

Examples:
- 0 = isolated pipe
- 1 = connected right only
- 5 = connected right + left (straight horizontal)
- 10 = connected up + down (straight vertical)
- 15 = connected all 4 directions (cross)

**Atlas Files:**

- `pipe_atlas_normal.png` (1024x64px, 16 variants)
- `pipe_atlas_normal_selected.png`
- `pipe_atlas_damaged.png`
- `pipe_atlas_damaged_selected.png`

### Fluid Output Logic

Location: `resources/js/modules/resourceTransport/ResourceTransportManager.js`

When a pump finishes crafting:

```javascript
if (state.craftingTicksRemaining <= 0) {
    const outputResourceId = parseInt(recipe.output_resource_id);
    const isFluid = outputResourceId >= 300 && outputResourceId <= 303;

    if (isFluid) {
        // Find connected pipe at building output
        const pipeEntityId = this.findOutputPipe(state.x, state.y);

        if (pipeEntityId) {
            // Push fluid to pipe system
            this.game.pipeSystemManager.addFluid(
                pipeEntityId,
                outputResourceId,
                outputAmount
            );
        }
    }
}
```

### Deposit Behavior (Client)

Location: `resources/js/modules/depositBehaviors.js`

**Placement Logic:**

```javascript
// Water/Lava Pumps - check landing tiles
if (requiredDepositType === 'landing') {
    const landingId = game.tileDataMap.get(`${tileX},${tileY}`);
    if (landingId !== requiredLandingId) {
        return { allowed: false, error: 'Wrong terrain type' };
    }
}

// Oil/Gas Pumps - check for deposit entities
if (requiredDepositType === 'ore') {
    const deposits = game.depositManager.getDepositsInArea(tileX, tileY, width, height);
    if (!deposits.some(d => allowedDepositTypeIds.includes(d.deposit_type_id))) {
        return { allowed: false, error: 'No valid deposit' };
    }
}
```

## Console Commands

### Generate Pipe Sprites

```bash
# Generate base pipe sprite (horizontal)
php yii pipe/generate-base

# Generate all 16 connection variants
php yii pipe/generate-connection-variants

# Generate all 4 atlases (normal, selected, damaged, damaged_selected)
php yii pipe/generate-all-atlases

# Generate construction sprites (10%-90% opacity)
php yii pipe/generate-construction

# Recalculate pipe systems in region
php yii pipe/recalculate-systems <region_id>
```

## Sprite Specifications

### Pipe Base Sprite (64x64px)

- Pipe width: 20px (x=22 to x=42)
- Fluid window: 10x10px (x=27-37, y=27-37)
- Metal color: #5A5A5A
- Symmetric design (horizontal/vertical rotation compatible)

### Pump Sprites (64x64px SVG)

Each pump has 5 sprite variants:
- `normal.svg` - Operating state
- `damaged.svg` - normal + dark overlay (durability < 50%)
- `blueprint.svg` - Semi-transparent blue (state = 'blueprint')
- `normal_selected.svg` - normal + yellow border (hover)
- `damaged_selected.svg` - damaged + yellow border

**Pump Colors:**

- Water Pump: Blue (#3498DB)
- Oil Pump: Dark gray (#2C3E50)
- Gas Pump: Light gray (#95A5A6)
- Lava Pump: Red/Orange (#E74C3C)

## Testing

### Simple System Test

```javascript
// Build: Pipe A → Pipe B → Pipe C (capacity = 300)
// Water Pump at A produces 100 water
// Chemical Plant at C can immediately consume 100 water
// System shows: 100/300 (33% full)
```

### No Mixing Test

```javascript
// System has 100 water
// Oil Pump tries to add oil → blocked (different resource)
// Destroy all pipes → rebuild
// Oil Pump can now add oil (system was reset)
```

### Tank Capacity Test

```javascript
// Build: Pipe (100) + Small Tank (5000) → total capacity = 5100
// Fill with pump → tooltip shows: "5100 / 5100 Water"
```

## Migration Files

| File | Description |
|------|-------------|
| `m260108_145052_create_pipe_system_tables.php` | Creates pipe_system and pipe_system_member tables |
| `m260108_150000_add_pipe_entity_types.php` | Adds 6 pipe entity types (pipes, underground, tanks) |
| `m260108_152000_add_pump_entity_types.php` | Adds 4 pump entity types |
| `m260108_153000_add_fluid_resources.php` | Adds fluid resources (300-303) |
| `m260108_170903_add_pump_recipes_and_landing_resources.php` | Adds deposit resources (400-403) and pump recipes |
| `m260108_171321_allow_null_input_in_recipe.php` | Allows NULL input for infinite extraction (water/lava) |
| `m260108_172300_add_oil_gas_deposit_types.php` | Adds oil well and gas vent deposit types |

## Future Enhancements

### Phase 3 (Optional)

- Animated fluid flow (moving particles)
- Pump working sounds
- Chemical plant recipes consuming fluids
- Fluid barrel items for belt transport

### Performance Optimizations

- Incremental system updates (merge/split instead of full recalc)
- Client-side system caching
- Spatial indexing for large pipe networks

## Troubleshooting

### Pipes Not Rendering

- Check that all 4 atlases are generated and loaded
- Verify `PipeConnectionManager.loadVariantTextures()` is called
- Check browser console for texture loading errors

### Fluid Not Flowing

- Verify pipes are actually connected (check pipe_system table)
- Ensure pump output is at building bottom (Y + 64px)
- Check pipe system capacity isn't full
- Verify no mixing error (different fluid already in system)

### Pump Won't Place

- Water/Lava: check landing_id matches (4 or 6)
- Oil/Gas: ensure deposit entity exists at location
- Verify deposit_type_id matches (13 or 14)

## References

- Original plan: `ideas/ai-ideas/004-fluids-system.md`
- Database schema: `docs/DATABASE.md`
- Game engine: `docs/GAME_ENGINE.md`
