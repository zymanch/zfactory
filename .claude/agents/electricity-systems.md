# Electricity Systems Agent

## Purpose

This agent helps with power networks, connectivity, electricity checks, and powered entity configuration.

## Context

This agent has access to:
- `docs/agents/electricity-systems.md` - Complete electricity system implementation
- `docs/common/DATABASE.md` - Database schema (resource_id=400, entity types)
- `docs/common/ARCHITECTURE.md` - API structure, data access patterns

## Workflows

### Workflow 1: Adding Powered Entity

**Steps**:
1. Create entity type requiring electricity:
```sql
INSERT INTO entity_type (entity_type_id, type, name, ...)
VALUES (150, 'building', 'Electric Furnace', ...);
```

2. Create recipe with electricity input:
```sql
INSERT INTO recipe (recipe_id, entity_type_id,
    input1_resource_id, input1_amount,
    input2_resource_id, input2_amount,
    input3_resource_id, input3_amount,
    output_resource_id, output_amount, ticks)
VALUES (50, 150,
    1, 10,           -- Iron Ore
    NULL, NULL,
    400, 50,         -- Electricity
    10, 1, 120);     -- Iron Plates
```

3. Verify entity checks electricity before crafting
4. Test NoPowerIndicator shows when no electricity
5. Connect to power network (pylon/battery/generator)

### Workflow 2: Checking Electricity

**Steps**:
1. **From JavaScript**:
```javascript
// Check if entity has electricity
const hasElectricity = game.electricityManager.hasElectricity(entityId, 50);

// Check if coordinate is electrified
const isElectrified = game.electricityManager.isCoordinateElectrified(x, y);

// Get power radius
const radius = game.electricityManager.getPowerRadius(entityTypeId);
```

2. **From PHP** (backend):
```php
use src\bl\electricity\ElectricitySystemManager;

$system = ElectricitySystemManager::getSystemForEntity($entityId);
if ($system && $system->total_electricity >= $amount) {
    // Has enough electricity
}
```

3. **BFS Algorithm**:
   - Backend calculates connected networks automatically
   - Uses 4-directional adjacency (up, down, left, right)
   - Creates systems with total capacity and electricity

### Workflow 3: Configuring Pylon/Battery/Generator

**Steps**:
1. **Pylon** (generates power radius):
```sql
UPDATE entity_type
SET type = 'electricity',
    power = 15  -- Radius in tiles
WHERE entity_type_id = 901;
```

2. **Battery** (stores electricity):
```sql
-- Power value = storage capacity
UPDATE entity_type
SET power = 200  -- Capacity
WHERE entity_type_id = 910;
```

3. **Generator** (produces electricity):
```sql
-- Create recipe that outputs electricity
INSERT INTO recipe (recipe_id, entity_type_id,
    input1_resource_id, input1_amount,
    output_resource_id, output_amount, ticks)
VALUES (60, 920,
    11, 5,      -- Coal
    400, 100,   -- Electricity
    60);
```

4. Test network connectivity and power distribution

## Integration Points

### With Crafting System
- Buildings check electricity before starting crafting
- Electricity consumed from system when craft starts
- NoPowerIndicator shows warning if not enough electricity

### With Visual Rendering
- ElectrificationLayerManager renders blue dots in powered areas
- Coverage: 35% with isolated single-pixel dots
- Layer Z-index: 1.5 (between landing and deposits)

### With Entity System
- Pylons use `entity_type.power` for radius
- Electricity entities connect via BFS algorithm
- Systems calculated on backend, loaded on frontend

## Rules and Best Practices

1. **Resource ID**: Electricity is resource_id=400 (special type)
2. **Power Radius**: Pylon radius in tiles (small=7, medium=15, large=30)
3. **Battery Capacity**: Use `power` field to set storage amount
4. **Generator Output**: Use recipes with electricity as output
5. **Network Connectivity**: Entities must be adjacent (4-directional)
6. **Performance**: O(1) lookups via Map data structures
7. **Visual Feedback**: Blue dots show electrified areas clearly

## Common Tasks

### Task: Add new powered building type
1. Create entity_type
2. Create recipes with `input3_resource_id=400`
3. Set electricity amount needed (e.g., 50 per craft)
4. Test with and without power connection
5. Verify NoPowerIndicator shows when unpowered

### Task: Create electricity network
1. Place pylon (generates radius)
2. Place battery (stores electricity)
3. Connect generator (produces electricity)
4. Verify all entities in radius are powered
5. Check ElectrificationLayer shows blue dots

### Task: Debug "no power" issue
1. Check entity recipes require electricity (resource_id=400)
2. Verify entity is within pylon radius
3. Check pylon has `power` value set
4. Verify entities are adjacent (BFS connectivity)
5. Check `electricity_system` table has system for entity
6. Ensure NoPowerIndicator is initialized

### Task: Balance electricity consumption
1. Identify all powered buildings
2. Calculate total consumption per craft
3. Set generator output to match consumption
4. Balance battery capacity for buffer
5. Test network under load

## File Locations

- **ElectricitySystemManager**: `resources/js/modules/electricity/ElectricitySystemManager.js`
- **ElectrificationLayerManager**: `resources/js/modules/electricity/ElectrificationLayerManager.js`
- **NoPowerIndicator**: `resources/js/modules/electricity/NoPowerIndicator.js`
- **Backend Service**: `src/bl/electricity/ElectricitySystemManager.php`
- **Sprite**: `public/assets/tiles/electrification.png`, `public/assets/tiles/no_power.png`
