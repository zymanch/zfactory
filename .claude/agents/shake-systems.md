# Shake Systems Agent

## Purpose

This agent helps with landscape shake zones, visual effects, damage mechanics, and stabilizer configuration.

## Context

This agent has access to:
- `docs/agents/shake-manager.md` - Complete shake system implementation
- `docs/common/DATABASE.md` - Database schema (map.shake_intensity, stabilizer entity types)
- `docs/common/ARCHITECTURE.md` - Layer structure, asset loading

## Workflows

### Workflow 1: Adding Shake Zone

**Steps**:
1. Identify zone area and intensity
   - Center zones: 12.0 intensity (high damage, ~6 HP/sec)
   - Medium zones: 7.5 intensity (medium damage, ~3.75 HP/sec)
   - Edge zones: 4.5 intensity (low damage, ~2.25 HP/sec)

2. Update database:
```sql
UPDATE map
SET shake_intensity = 12.0
WHERE x BETWEEN 25 AND 27 AND y BETWEEN 25 AND 27;
```

3. Verify shake layer renders:
   - Shake sprites created at Z=1.5
   - Tiles shake with sin/cos wave animation
   - Max offset capped at 3px

4. Test damage calculation:
   - Buildings lose HP every 180 ticks (3 seconds)
   - Multi-tile buildings accumulate damage from all tiles

### Workflow 2: Configuring Stabilizers

**Steps**:
1. Choose stabilizer type:
   - Small (1×1, 5-tile radius, uses Stability resource)
   - Medium (2×2, 10-tile radius, uses Electricity)
   - Large (3×3, 20-tile radius, uses Stability resource)

2. Create entity types (if not exists):
```sql
INSERT INTO entity_type (entity_type_id, type, name, width, height, power, ...)
VALUES (950, 'building', 'Small Stabilizer', 1, 1, 5, ...);
```

3. Configure power source:
   - Small/Large: Create recipe consuming Stability resource (resource_id=450)
   - Medium: Connect to electricity network

4. Test protection:
   - Place stabilizer in shake zone
   - Verify buildings within radius are protected
   - Check power consumption

### Workflow 3: Balancing Damage

**Steps**:
1. Identify target destruction time:
   - Quick: 10-20 seconds (intensity ~10-15)
   - Medium: 30-40 seconds (intensity ~5-8)
   - Slow: 60+ seconds (intensity ~2-4)

2. Adjust damage formula in ShakeManager.js:
```javascript
// Current: damage = shakeForce × 0.5
// Gentler: damage = shakeForce × 0.25
// Harsher: damage = shakeForce × 1.0
```

3. Test with various building sizes:
   - 1×1 buildings: single tile intensity
   - 2×2 buildings: sum of 4 tiles
   - 3×3 buildings: sum of 9 tiles

4. Balance stabilizer costs and radius to match gameplay

## Integration Points

### With Entity System
- Stabilizers are regular buildings with `type='building'`
- Protection radius from `entity_type.power` field
- Check electricity or resource availability for activation

### With Resource System
- Stability resource (ID=450) consumed by stabilizers
- Recipe: 2 Crystal + 1 Steel Plate → 10 Stability (120 ticks)

### With Visual Rendering
- Shake sprites duplicate landing layer at Z=1.5
- Entities and deposits shake using sprite.baseX/baseY
- Animation synchronized across all affected sprites

### With Durability System
- Damage applied to `entity.durability` field
- When durability=0, sprite changes to blueprint state
- Rebuild requires resources from entity_type_cost

## Rules and Best Practices

1. **Intensity Scaling**: Keep intensities between 0-20 for balanced gameplay
2. **Multi-Tile Accumulation**: Account for building size when setting zone intensity
3. **Protection Radius**: Balance stabilizer radius vs cost (small=5, medium=10, large=20)
4. **Performance**: Avoid creating too many shake zones (impacts rendering)
5. **Visual Feedback**: Use shake animation to indicate danger zones
6. **Player Strategy**: Force players to choose between expansion and protection

## Common Tasks

### Task: Create earthquake zone around volcano
1. Identify volcano coordinates (e.g., x=50-60, y=40-50)
2. Set center to high intensity (12.0)
3. Create gradient: medium zone around center (7.5), edge zone outside (4.5)
4. Test with buildings of different sizes
5. Balance stabilizer placement options

### Task: Add stabilizer building
1. Create 3 entity types (small, medium, large)
2. Generate sprites with distinct visual style
3. Configure power source (electricity or stability resource)
4. Set protection radius in `power` field
5. Create recipes for stability resource if needed
6. Test protection mechanics

### Task: Debug shake not appearing
1. Check `map.shake_intensity` is set in database
2. Verify ShakeManager initialized with zones
3. Check shake layer (Z=1.5) exists and is visible
4. Ensure sprites have baseX/baseY properties
5. Check game loop calls `shakeManager.update(deltaTime)`

## File Locations

- **Manager**: `resources/js/modules/shake/ShakeManager.js`
- **Backend Service**: `src/services/ShakeDamageService.php`
- **Migrations**: `src/migrations/m260118_100000_add_shake_to_map.php`
- **Entity Types**: `src/bl/entity/types/building/Stabilizer*EntityType.php`
- **Rebuild Action**: `src/controllers/actions/game/RebuildEntity.php`
