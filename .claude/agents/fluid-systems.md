# Fluid Systems Agent

## Purpose

This agent helps with pipe networks, fluid transport, priority distribution, and pump configuration.

## Context

This agent has access to:
- `docs/agents/pipe-systems.md` - Complete pipe and fluid system implementation
- `docs/common/DATABASE.md` - Database schema (pipe_system tables, resources)
- `docs/common/ARCHITECTURE.md` - BFS algorithm, client-side systems

## Workflows

### Workflow 1: Adding Pipe Type

**Steps**:
1. Create entity type:
```sql
INSERT INTO entity_type (entity_type_id, type, subtype, name, power, ...)
VALUES (145, 'pipe', 'pipe', 'Advanced Pipe', 200, ...);
```

2. Set capacity in `power` field (100=basic, 200=advanced)

3. Generate pipe sprites:
   - Create base `sprite.png` (64×64)
   - Run `php yii pipe/generate-all-atlases`
   - Generates 4 atlases: normal, selected, damaged, damaged_selected

4. Test pipe connectivity:
   - Place pipes adjacent to each other
   - Verify BFS algorithm connects them
   - Check system capacity = sum of all pipe powers

### Workflow 2: Configuring Pump

**Steps**:
1. **For infinite sources (water/lava)**:
```sql
-- Create pump entity type
INSERT INTO entity_type (entity_type_id, type, name, power, ...)
VALUES (145, 'mining', 'Water Pump', 100, ...);

-- Create recipe with no input (infinite)
INSERT INTO recipe (recipe_id,
    output_resource_id, output_amount, ticks)
VALUES (70, 300, 1, 30);  -- Outputs Water
```

2. **For deposits (oil/gas)**:
```sql
-- Create pump requiring deposit
INSERT INTO entity_type (...)
VALUES (146, 'mining', 'Oil Pump', 100, ...);

-- Create recipe consuming deposit resource
INSERT INTO recipe (recipe_id,
    input1_resource_id, input1_amount,
    output_resource_id, output_amount, ticks)
VALUES (71, 401, 1, 301, 1, 30);  -- Oil Well → Crude Oil
```

3. **Set placement requirements**:
   - Water Pump: `landing_id=4` (water landing)
   - Lava Pump: `landing_id=6` (lava landing)
   - Oil/Gas Pump: requires deposit at location

### Workflow 3: Fluid Distribution Priority

**Steps**:
1. **Priority order**: Tanks → Buildings → Mining

2. **Configure tanks** (high capacity):
```sql
INSERT INTO entity_type (...)
VALUES (140, 'pipe', 'Small Tank', 5000, ...);  -- 2×2

INSERT INTO entity_type (...)
VALUES (141, 'pipe', 'Large Tank', 25000, ...);  -- 3×3
```

3. **Add fluid to system**:
```javascript
// When pump outputs fluid
game.pipeManager.addFluid(pipeEntityId, resourceId, amount);

// Distribution happens automatically:
// 1. Fill tanks first (entity_type_ids: 135, 136, 140, 141)
// 2. Then buildings (type='building')
// 3. Finally mining (type='mining')
```

4. Test distribution:
   - Place tank, building, pump all connected
   - Pump produces fluid
   - Verify tank fills first, then building

### Workflow 4: Fluid Integration in Crafting

**Steps**:
1. **Create recipe with fluid input**:
```sql
INSERT INTO recipe (recipe_id,
    input1_resource_id, input1_amount,  -- Fluid (type='liquid')
    input2_resource_id, input2_amount,  -- Solid
    input3_resource_id, input3_amount,  -- Electricity
    output_resource_id, output_amount, ticks)
VALUES (72,
    300, 10,    -- Water (liquid)
    2, 5,       -- Iron Plates (solid)
    400, 50,    -- Electricity
    50, 1, 120); -- Steel
```

2. **Ensure resource has type='liquid'**:
```sql
UPDATE resource
SET type = 'liquid'
WHERE resource_id IN (300, 301, 302, 303);  -- Water, Oil, Gas, Lava
```

3. **Crafting check automatically**:
   - ResourceTransportManager detects liquid resources
   - Checks pipe system for required fluid
   - Consumes fluid when craft starts

## Integration Points

### With Crafting System
- Buildings check pipe system for liquid resources
- Fluids consumed from system (not inventory)
- No mixing allowed (one fluid type per system)

### With Visual Rendering
- PipeRenderer shows colored tint based on fluid type
- Empty pipes: no tint
- Filled pipes: blue (water), dark (oil), gray (gas), red (lava)

### With BFS Algorithm
- Pipe systems calculated client-side (2026-01 change)
- Finds connected pipes in 4 directions
- Creates local system IDs (not from database)
- Calculates capacity from sum of pipe powers

## Rules and Best Practices

1. **Fluid Types**: Water (300), Oil (301), Gas (302), Lava (303)
2. **No Mixing**: Each system can only contain one fluid type
3. **Capacity-Based**: Total capacity = sum of all pipe/tank powers
4. **Instant Distribution**: Fluid added anywhere is available everywhere
5. **Priority**: Tanks → Buildings → Mining
6. **Client-Side BFS**: Systems calculated on frontend (no server load)
7. **Pipe Capacity**: Basic=100, Advanced=200, Tanks=5000-25000

## Common Tasks

### Task: Create water-based recipe
1. Create recipe with `input1_resource_id=300` (water)
2. Ensure water resource has `type='liquid'`
3. Place building and connect to pipe network
4. Add water pump on water landing
5. Verify water flows and building crafts

### Task: Add underground pipes
1. Create IN entity (has output, no input)
2. Create OUT entity (has input, no output)
3. Set same orientation
4. Game automatically pairs nearest IN/OUT
5. Resources tunnel underground with negative ticks

### Task: Debug "no fluid" issue
1. Check pipe system exists (BFS calculated?)
2. Verify building connected to pipes
3. Check pump is producing (recipe active)
4. Ensure no mixing (system has wrong fluid type)
5. Verify capacity not full
6. Check resource has `type='liquid'`

### Task: Balance fluid network
1. Calculate total consumption (all recipes)
2. Set pump output to match consumption
3. Add tank capacity for buffer
4. Balance pipe capacity vs distance
5. Test under load (multiple buildings)

## File Locations

- **PipeSystemManager**: `resources/js/modules/pipes/PipeSystemManager.js`
- **PipeRenderer**: `resources/js/modules/rendering/PipeRenderer.js`
- **Backend Manager**: `src/bl/pipes/PipeSystemManager.php`
- **Pump Types**: `src/bl/entity/types/mining/FluidPump*.php`
- **Sprites**: `public/assets/tiles/entities/pipe/`
- **Atlases**: `pipe_atlas_normal.png`, `pipe_atlas_damaged.png`, etc.
