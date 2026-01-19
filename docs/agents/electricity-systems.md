# Electricity System Documentation

## Purpose

The electricity system provides power distribution using **pylons**, **batteries**, and **generators**. It consists of three client-side managers working together.

## ElectricitySystemManager

### Location
`resources/js/modules/electricity/ElectricitySystemManager.js`

### Purpose
Manages electricity network data and provides API for checking power availability.

### Initialization

```javascript
this.electricityManager = new ElectricitySystemManager(this);
this.electricityManager.loadSystems(this.initialElectricitySystems);
```

### Key Methods

- `loadSystems(systemsData)` - Load system data from server config
- `getSystemForEntity(entityId)` - Get system for specific entity (O(1) lookup via Map)
- `hasElectricity(entityId, amount)` - Check if entity has enough electricity
- `isCoordinateElectrified(x, y)` - Check if world coordinate is powered
- `getPowerRadius(entityTypeId)` - Get pylon radius in pixels (tiles × 64)

### Data Structures

```javascript
systems = new Map();           // systemId → {totalCapacity, totalElectricity, members}
entityToSystem = new Map();    // entityId → systemId (for O(1) lookup)
```

## ElectrificationLayerManager

### Location
`resources/js/modules/electricity/ElectrificationLayerManager.js`

### Purpose
Renders blue glowing dots on tiles within pylon power radius.

### Layer Configuration
- **zIndex**: 1.5 (between landing and deposits)
- **Sprite**: `public/assets/tiles/electrification.png` (64×64)
- **Coverage**: 35% with isolated single-pixel dots
- **Colors**: Dark blue (RGB: 40-60, 100-140, 180-220)

### Initialization

```javascript
await this.electrificationLayer.init();  // Loads texture
this.electrificationLayer.render();      // Initial render
```

### Rendering Logic

1. Find all `state='built'` electricity entities with power radius > 0
2. Convert entity coordinates from tiles to pixels (× 64)
3. For each tile in viewport:
   - Check Euclidean distance to all power sources
   - Create sprite if distance ≤ radius
4. Sprites pooled for performance

### Coordinates

- Entity coords stored in **tiles** (`entity.x`, `entity.y`)
- Rendering uses **pixels** (`entity.x * 64`, `entity.y * 64`)
- Viewport bounds calculated with 128px padding

### Update Triggers

- Called manually via `render()` after entities load
- Can be called when electricity entities change

## NoPowerIndicator

### Location
`resources/js/modules/electricity/NoPowerIndicator.js`

### Purpose
Shows warning icon on buildings that need electricity but don't have it.

### Sprite
`public/assets/tiles/no_power.png` (64×64)
- Yellow lightning bolt with red X overlay

### Initialization

```javascript
await this.noPowerIndicator.init();  // Loads texture
```

### Detection Logic

```javascript
checkEntityNeedsElectricity(entity) {
    const entityType = this.game.entityTypes[entity.entity_type_id];
    const recipeIds = entityType.recipes || [];
    for (const recipeId of recipeIds) {
        const recipe = this.game.recipes[recipeId];
        // Check if electricity (resource_id 400) is required
        if (recipe.input1_resource_id === 400 ||
            recipe.input2_resource_id === 400 ||
            recipe.input3_resource_id === 400) {
            return true;
        }
    }
    return false;
}
```

### Update Cycle

- Called periodically (every 60 frames)
- Shows indicator if: entity needs electricity AND doesn't have it
- Hides indicator if: entity has electricity OR doesn't need it
- Sprite added as child to entity container

### Visual Position

- Positioned at `(0, -32)` relative to entity center
- Appears slightly above center of entity sprite

## Database Schema

### Electricity Resources

```sql
resource_id | name          | type
------------|---------------|-------
400         | Electricity   | special
```

### Entity Types

**Pylons** (generate power radius):
- Small Pylon: power=7 (7-tile radius)
- Medium Pylon: power=15 (15-tile radius)
- Large Pylon: power=30 (30-tile radius)

**Batteries** (store electricity):
- Power value determines storage capacity

**Generators** (produce electricity):
- Consume fuel resources
- Output electricity via recipes

## System Structure

```json
{
    "electricity_system_id": 1,
    "total_capacity": 300,
    "total_electricity": 150,
    "members": [
        {"entity_id": 10, "entity_type_id": 900},
        {"entity_id": 15, "entity_type_id": 101}
    ]
}
```

## Integration with Crafting

Buildings check electricity before starting crafting:

```javascript
// In ResourceTransportManager.tryStartBuildingCraft()
if (recipe.input3_resource_id === 400) {
    const hasElectricity = this.game.electricityManager.hasElectricity(
        entityId,
        recipe.input3_amount
    );
    if (!hasElectricity) {
        continue; // Can't craft without electricity
    }
}
```

## Performance Notes

- ElectrificationLayerManager uses sprite pooling to reuse sprites
- Viewport culling ensures only visible tiles are rendered
- O(1) lookups via Map data structures
- NoPowerIndicator updates throttled to every 60 frames

## Workflows

### Adding Powered Entity

1. Create entity type with `type='electricity'` or has recipes requiring electricity
2. If pylon: set `power` field to radius in tiles
3. Backend calculates electricity systems via BFS
4. Frontend loads systems from `/game/config`
5. ElectrificationLayerManager renders blue dots in radius
6. NoPowerIndicator checks and displays warnings

### Checking Electricity

```javascript
// Check if entity has electricity
const hasElectricity = game.electricityManager.hasElectricity(entityId, 50);

// Check if coordinate is electrified
const isElectrified = game.electricityManager.isCoordinateElectrified(x, y);
```

### BFS Network Algorithm

Backend uses Breadth-First Search to find connected electricity networks:

1. Load all electricity entities in region
2. For each unprocessed entity:
   - Run BFS to find connected entities (adjacency check)
   - Create new system with total capacity
   - Link all found entities as members

## File Locations

- **ElectricitySystemManager**: `resources/js/modules/electricity/ElectricitySystemManager.js`
- **ElectrificationLayerManager**: `resources/js/modules/electricity/ElectrificationLayerManager.js`
- **NoPowerIndicator**: `resources/js/modules/electricity/NoPowerIndicator.js`
- **Backend Service**: `src/bl/electricity/ElectricitySystemManager.php`
