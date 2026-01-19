# Fog of War System Documentation

## Purpose

Visibility system based on Crystal Towers (type='eye'). Only tiles within radius of eye entities are visible, with non-visible areas covered by black fog.

## Location

`resources/js/modules/fogOfWar.js`

## How It Works

- Only tiles within radius of eye entities are visible
- Radius = entity_type.power (in tiles)
- Non-visible areas covered by black fog (alpha 0.95)
- Edge tiles (adjacent to visible) have half fog (alpha 0.5)
- Entities in fog are hidden (not rendered, not interactive)
- Recalculates when eye entities change
- Toggle with F key for debugging

## Visibility Algorithm

### Circular Visibility from Each Tower

```javascript
// Circular visibility from each tower
for (let dy = -power; dy <= power; dy++) {
    for (let dx = -power; dx <= power; dx++) {
        if (dx*dx + dy*dy <= power*power) {  // Euclidean circle
            visibleTiles.add(`${x+dx}_${y+dy}`);
        }
    }
}
```

### Crystal Tower Types

| Type                  | Power | Radius (tiles) |
|-----------------------|-------|----------------|
| Small Crystal Tower   | 7     | ~7 tiles       |
| Medium Crystal Tower  | 15    | ~15 tiles      |
| Large Crystal Tower   | 30    | ~30 tiles      |

## Implementation

### Initialization

```javascript
// In game.js
this.fogOfWar = new FogOfWar(this);
this.fogOfWar.init();
```

### Data Structures

```javascript
class FogOfWar {
    game: ZFactoryGame
    fogContainer: PIXI.Container     // Container for fog sprites
    enabled: boolean                 // Toggle state
    visibleTiles: Set<string>        // Set of "x_y" visible tiles
    edgeTiles: Set<string>           // Set of "x_y" edge tiles
}
```

### Key Methods

- `init()` - Initialize fog container and layer
- `recalculate()` - Recalculate visible tiles from eye entities
- `renderFog()` - Create/update fog sprites for viewport
- `toggle()` - Enable/disable fog (F key)
- `isVisible(tileX, tileY)` - Check if tile is visible

### Recalculation Logic

1. Clear visibleTiles and edgeTiles sets
2. Get all eye entities (filter by `type='eye'`)
3. For each eye entity:
   - Get power (radius) from entity_type
   - Mark all tiles within Euclidean distance as visible
4. Mark tiles adjacent to visible as edge tiles
5. Call renderFog() to update sprites

### Rendering

```javascript
renderFog() {
    // Clear existing fog sprites
    this.fogContainer.removeChildren();

    // Get viewport bounds
    const viewport = this.game.getViewportBounds();

    // For each tile in viewport
    for (let y = viewport.minY; y <= viewport.maxY; y++) {
        for (let x = viewport.minX; x <= viewport.maxX; x++) {
            const key = `${x}_${y}`;

            // Fully visible - no fog
            if (this.visibleTiles.has(key)) {
                continue;
            }

            // Create fog sprite
            const fog = new PIXI.Graphics();
            fog.rect(0, 0, 64, 64);
            fog.fill({ color: 0x000000 });
            fog.x = x * 64;
            fog.y = y * 64;

            // Edge tiles - half fog
            if (this.edgeTiles.has(key)) {
                fog.alpha = 0.5;
            } else {
                fog.alpha = 0.95;  // Full fog
            }

            this.fogContainer.addChild(fog);
        }
    }
}
```

## Entity Visibility

Entities in fog are hidden:

```javascript
// In entityLayerManager.js
createEntitySprite(entity) {
    // ...

    // Check fog visibility
    if (this.game.fogOfWar && this.game.fogOfWar.enabled) {
        const tileX = Math.floor(entity.x / 64);
        const tileY = Math.floor(entity.y / 64);

        if (!this.game.fogOfWar.isVisible(tileX, tileY)) {
            sprite.visible = false;
            sprite.eventMode = 'none';  // No interaction
        }
    }

    // ...
}
```

## Integration Points

### Game Initialization

```javascript
// After entity loading
this.fogOfWar.recalculate();
this.fogOfWar.renderFog();
```

### Entity Changes

```javascript
// When eye entity added/removed
this.fogOfWar.recalculate();
```

### Camera Movement

```javascript
// On viewport change (throttled)
if (this.fogOfWar.enabled) {
    this.fogOfWar.renderFog();
}
```

### Input Manager

```javascript
// Toggle fog with F key
if (key === 'f') {
    this.game.fogOfWar.toggle();
}
```

## Performance Optimizations

1. **Viewport culling**: Only render fog for visible tiles
2. **Set lookup**: O(1) visibility checks using Set
3. **Lazy rendering**: Only update on camera move or recalculation
4. **Graphics pooling**: Reuse fog sprites where possible

## Database Schema

### Eye Entities

```sql
-- Entity type with type='eye'
entity_type_id | type | power | name
---------------|------|-------|-----
400            | eye  | 7     | Small Crystal Tower
401            | eye  | 15    | Medium Crystal Tower
402            | eye  | 30    | Large Crystal Tower
```

## Workflows

### Adding Eye Entity

1. Create entity_type with `type='eye'`
2. Set `power` field to visibility radius in tiles
3. Place entity on map
4. FogOfWar.recalculate() called automatically
5. Fog updates to show new visible area

### Debugging Fog

Press F key to toggle fog on/off:
- With fog: realistic gameplay
- Without fog: see entire map for debugging

## File Locations

- **Manager**: `resources/js/modules/fogOfWar.js`
- **Layer**: fogLayer in worldContainer (Z=9999)
