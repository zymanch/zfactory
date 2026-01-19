# Fog of War Systems Agent

## Purpose

This agent helps with fog of war visibility, eye entity configuration, raycasting algorithms, and visibility calculations.

## Context

This agent has access to:
- `docs/agents/fog-of-war.md` - Complete fog of war implementation
- `docs/common/DATABASE.md` - Database schema (entity type='eye')
- `docs/common/ARCHITECTURE.md` - Layer structure, rendering pipeline

## Workflows

### Workflow 1: Adding Eye Entity

**Steps**:
1. Create entity type with `type='eye'`:
```sql
INSERT INTO entity_type (entity_type_id, type, name, power, ...)
VALUES (403, 'eye', 'Giant Crystal Tower', 50, ...);
```

2. Set visibility radius in `power` field (in tiles):
   - Small: 7 tiles
   - Medium: 15 tiles
   - Large: 30 tiles
   - Giant: 50 tiles

3. Generate sprites:
   - Create normal sprite showing eye/tower
   - Use BuildingAtlasProvider (7×2 atlas)
   - Include construction frames

4. Place entity on map:
   - FogOfWar.recalculate() called automatically
   - Fog updates to show new visible area

5. Test visibility:
   - Entities in visible area: `visible=true`, `eventMode='static'`
   - Entities in fog: `visible=false`, `eventMode='none'`

### Workflow 2: Visibility Calculation

**Steps**:
1. **Circular visibility algorithm**:
```javascript
// For each eye entity
for (let dy = -power; dy <= power; dy++) {
    for (let dx = -power; dx <= power; dx++) {
        // Euclidean circle
        if (dx*dx + dy*dy <= power*power) {
            visibleTiles.add(`${x+dx}_${y+dy}`);
        }
    }
}
```

2. **Edge tiles** (adjacent to visible):
   - Have half fog (alpha=0.5)
   - Provide smooth transition

3. **Performance**:
   - O(1) visibility checks using Set
   - Lazy rendering (only on camera move or recalculation)
   - Viewport culling (only visible fog tiles)

### Workflow 3: Debugging Fog

**Steps**:
1. **Toggle fog with F key**:
   - With fog: realistic gameplay
   - Without fog: see entire map for debugging

2. **Check entity visibility**:
```javascript
const tileX = Math.floor(entity.x / 64);
const tileY = Math.floor(entity.y / 64);

if (game.fogOfWar.isVisible(tileX, tileY)) {
    // Entity should be visible
}
```

3. **Verify fog layer**:
   - Layer at Z=9999 (top layer)
   - Black graphics with alpha 0.95 (full fog) or 0.5 (edge)

4. **Common issues**:
   - Eye entity not loaded → recalculate not called
   - Power field not set → visibility radius 0
   - Fog layer not initialized → no fog sprites

## Integration Points

### With Entity System
- Eye entities filtered from entities array (`type='eye'`)
- Entity sprites check visibility before rendering
- Interactivity disabled in fog (`eventMode='none'`)

### With Visual Rendering
- Fog layer at Z=9999 (above all other layers)
- Black graphics with varying alpha
- Viewport culling for performance

### With Game Loop
- Recalculate on entity changes
- Render fog on camera movement (throttled)
- Toggle with F key for debugging

## Rules and Best Practices

1. **Radius Scaling**: Use power values 7, 15, 30, 50 for balanced progression
2. **Performance**: Fog recalculation is expensive, only call when needed
3. **Visual Feedback**: Edge tiles with half fog create smooth transition
4. **Player Strategy**: Force exploration by limiting visibility
5. **Debug Mode**: Always allow F key toggle for testing
6. **Euclidean Distance**: Use `dx*dx + dy*dy <= r*r` for circular visibility

## Common Tasks

### Task: Create new eye entity type
1. Define entity_type with `type='eye'`
2. Set `power` to visibility radius (e.g., 20 tiles)
3. Create sprite showing eye/tower theme
4. Generate atlas with construction frames
5. Test visibility calculation works correctly

### Task: Balance visibility progression
1. Start with small visibility (power=7)
2. Mid-game: medium visibility (power=15)
3. Late-game: large visibility (power=30)
4. End-game: giant visibility (power=50+)
5. Balance costs to match progression

### Task: Debug entities not visible
1. Check eye entity placed and has `state='built'`
2. Verify entity type has `type='eye'` in database
3. Check `power` field is set correctly
4. Ensure FogOfWar.recalculate() was called
5. Verify entity within radius: `distance <= power`
6. Check fog layer is rendering (Z=9999)

### Task: Optimize fog performance
1. Use Set for O(1) visibility checks
2. Lazy render (only on camera move)
3. Viewport culling (only visible tiles)
4. Throttle recalculation (max 1x per second)
5. Avoid recalculating every frame

## File Locations

- **Manager**: `resources/js/modules/fogOfWar.js`
- **Layer**: fogLayer in worldContainer (Z=9999)
- **Entity Types**: Database `entity_type` with `type='eye'`
- **Sprites**: `public/assets/tiles/entities/{eye_entity}/`
