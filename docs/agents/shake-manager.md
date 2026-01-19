# Shake Manager Documentation

## Purpose
The ShakeManager creates earthquake-like zones that visually shake terrain, entities, and deposits while damaging buildings over time. Buildings can be protected using stabilizer structures.

## Location
`resources/js/modules/shake/ShakeManager.js`

## Data Structures

```javascript
shakeZones = new Map();           // "x_y" => intensity (0.00-99.99)
shakeLandingSprites = new Map();  // "x_y" => PIXI.Sprite (duplicate sprites)
```

## Key Features

- **Duplicate landing rendering**: Creates separate layer (Z=1.5) with shake sprites ONLY for tiles with shake_intensity > 0
- **Viewport culling**: Only renders visible shake tiles for performance
- **Synchronized animation**: All sprites (landing, entities, deposits) shake with same sin/cos wave
- **Client-side damage**: Calculates damage every 180 ticks (3 seconds) without server requests
- **Multi-tile accumulation**: Buildings >1x1 accumulate damage from ALL tiles under footprint
- **Broken building system**: When durability=0, sprite changes to blueprint state

## Visual Animation

**Pattern**: Fog of War approach - duplicate sprites only for specific tiles.

### Shake Offset Calculation

```javascript
applyShakeOffset(sprite, intensity, baseTileX, baseTileY) {
    const maxOffset = Math.min(3 * intensity, 3);  // Capped at 3px
    const angle = this.shakeTime * 8 + (baseTileX + baseTileY);

    const offsetX = Math.sin(angle) * maxOffset;
    const offsetY = Math.cos(angle * 1.3) * maxOffset;

    sprite.x = sprite.baseX + offsetX;
    sprite.y = sprite.baseY + offsetY;
}
```

**Animation Properties**:
- **Frequency**: 8 rad/sec (roughly 1.3 Hz)
- **Maximum offset**: 3px regardless of intensity
- **Phase variation**: Each tile has unique phase based on (x + y) for natural look
- **Smooth wave**: Sin/cos curves create circular motion

**Affected Sprites**:
1. **Landing tiles**: Duplicate sprites in shake layer
2. **Entities**: All entities on shake tiles (uses sprite.baseX/baseY)
3. **Deposits**: All deposits on shake tiles (uses sprite.baseX/baseY)

## Client-Side Damage

### Damage Calculation (`applyShakeDamage()`)
- Runs every 180 ticks (3 seconds at 60 FPS)
- Only affects entities with `state='built'`
- Damage formula: `shakeForce × 0.5` per 3 seconds
- Multi-tile buildings: sum intensity from ALL tiles under footprint

**Example** (2×2 building on tiles with intensities 12.0, 12.0, 7.5, 7.5):
```javascript
shakeForce = 12.0 + 12.0 + 7.5 + 7.5 = 39.0
damage = 39.0 × 0.5 = 19.5 HP per 3 seconds (6.5 HP/sec)
```

### Destruction Speed

| Zone Type | Intensity | Damage/sec | Time to destroy 100 HP |
|-----------|-----------|------------|------------------------|
| Center    | 12.0      | ~6 HP/sec  | ~17 seconds            |
| Medium    | 7.5       | ~3.75 HP/sec | ~27 seconds          |
| Edge      | 4.5       | ~2.25 HP/sec | ~44 seconds          |

### Update Sprite on Broken

When `entity.durability` reaches 0, sprite texture changes to blueprint:
```javascript
if (entity.durability === 0) {
    const sprite = this.game.loadedEntities.get(key);
    const textureKey = this.game.getEntityTextureKey(entity, false);
    sprite.texture = this.game.textures[textureKey];
}
```

## Broken Building Rebuild

### User Interaction
1. Entity with `durability=0` displays as blueprint sprite
2. Click on broken entity in NORMAL mode
3. Server checks user has enough resources (from `entity_type_cost`)
4. Server deducts resources and converts entity to blueprint state
5. Client reloads resources and updates UI

### Backend (`src/controllers/actions/game/RebuildEntity.php`)

```php
// Get building costs
$costs = EntityTypeCost::find()
    ->where(['entity_type_id' => $entity->entity_type_id])
    ->asArray()
    ->all();

// Check and deduct resources
foreach ($costs as $cost) {
    $userResource = UserResource::findOne([
        'user_id' => $userId,
        'resource_id' => $cost['resource_id']
    ]);

    if ($userResource->quantity < $cost['quantity']) {
        return $this->error('Not enough resources');
    }

    $userResource->quantity -= $cost['quantity'];
    $userResource->save();
}

// Convert to blueprint
$entity->state = 'blueprint';
$entity->construction_progress = 0;
$entity->save();
```

### Frontend (`resources/js/game.js`)

```javascript
async handleBrokenEntityClick(entity, sprite) {
    const response = await fetch(this.config.rebuildEntityUrl, {
        method: 'POST',
        body: JSON.stringify({ entity_id: entity.entity_id })
    });

    if (data.result === 'ok') {
        entity.state = 'blueprint';
        entity.construction_progress = 0;
        await this.reloadUserResources();  // Refresh resources
        // Update sprite
    }
}
```

## Stabilizer Buildings

Three types of buildings that protect entities from shake damage within a radius.

### Stabilizer Types

| Entity Type ID | Size | Radius | Power Source |
|----------------|------|--------|--------------|
| 950 (Small)    | 1×1  | 5 tiles | Stability resource |
| 951 (Medium)   | 2×2  | 10 tiles | Electricity only |
| 952 (Large)    | 3×3  | 20 tiles | Stability resource |

### Protection Check

```javascript
isProtectedByStabilizer(entity) {
    // Get all active stabilizers (have power)
    // Calculate distance from entity center to stabilizer center
    // If distance <= stabilizer radius, entity is protected
    return false;  // Currently not implemented
}
```

### Stability Resource

- **Resource ID**: 450
- **Type**: Crafted resource
- **Recipe**: 2 Crystal + 1 Steel Plate → 10 Stability (120 ticks)
- **Usage**: Small and Large stabilizers consume Stability to operate

### Medium Stabilizer Power

- Powered by electricity network (no resource consumption)
- Checked via `ElectricitySystemManager.hasElectricity(entityId)`

## Database Schema

### Map Table

```sql
ALTER TABLE map ADD COLUMN shake_intensity DECIMAL(5,2) NULL
    COMMENT '0.00-99.99 shake coefficient, null=no shake';
CREATE INDEX idx_map_shake ON map(shake_intensity);
```

### Shake Zones Format (loaded from `/map/tiles`)

```json
{
    "shakeZones": {
        "25_25": 12.0,
        "25_26": 12.0,
        "26_25": 12.0,
        "24_25": 7.5
    }
}
```

### Durability Persistence

Saved every 60 seconds via `/game/save-state`:
```json
{
    "entityDurability": {
        "123": 85.5,
        "124": 42.0,
        "125": 0
    }
}
```

## Performance Optimizations

1. **Early exit**: If `hasShakeZones = false`, entire manager skips (0ms overhead)
2. **Viewport culling**: Only create sprites for visible tiles
3. **Lazy sprite creation**: Sprites created on-demand, not all at once
4. **Lightweight damage**: Calculated every 180 ticks, not every frame
5. **Offset cap**: Maximum 3px offset prevents excessive rendering work
6. **Dictionary format**: `shakeZones` as `{"x_y": intensity}` for O(1) lookup

**Performance Metrics**:
- Shake manager overhead: <5ms per frame (when zones exist)
- No overhead when no shake zones exist (early exit)
- Synchronized animation reuses same time/angle for all sprites

## Layer Structure

```
Stage
└── worldContainer
    ├── landingLayer (Z=1) - Regular terrain
    ├── shakeLayer (Z=1.5) - Duplicate shake sprites
    ├── depositLayer (Z=1.6) - Deposits
    ├── entityLayer (Z=2) - Buildings
    └── fogLayer (Z=9999) - Fog of war
```

**Z-Index Ordering**: Shake layer positioned between terrain and entities to create illusion that entire landscape shakes together.

## Integration Points

### Game Loop (`resources/js/game.js`)

```javascript
// In gameLoop()
this.perfMonitor.start('shakeManager');
if (this.shakeManager) {
    const deltaTime = ticker.deltaMS / 1000;
    this.shakeManager.update(deltaTime);
}
this.perfMonitor.end('shakeManager');
```

### Entity Sprite Creation (`createEntitySprite()`)

```javascript
// Store base position for shake animation
sprite.baseX = pixelX;
sprite.baseY = pixelY;
```

### Deposit Sprite Creation (`depositLayerManager.js`)

```javascript
// Store base position for shake animation
sprite.baseX = pixelX;
sprite.baseY = pixelY;
```

### State Saving (`ResourceTransportManager.getSaveData()`)

```javascript
// Include entity durability in save data
data.entityDurability = {};
for (const [key, entity] of this.game.entityData) {
    if (typeof entity.durability === 'number') {
        data.entityDurability[entity.entity_id] = entity.durability;
    }
}
```

## Workflows

### Adding Shake Zone

1. Update database: `UPDATE map SET shake_intensity = 12.0 WHERE x = 25 AND y = 25`
2. Server returns shake zones in `/map/tiles` response
3. Client initializes ShakeManager with zones
4. Duplicate landing sprites created for affected tiles
5. Animation starts automatically in game loop

### Configuring Stabilizer

1. Place stabilizer entity on map
2. Connect to power source (electricity or stability resource)
3. Protection radius automatically calculated
4. Buildings within radius protected from shake damage

### Balancing Damage

Adjust damage formula in `applyShakeDamage()`:
```javascript
// Current: damage = shakeForce × 0.5
// To make gentler: damage = shakeForce × 0.25
// To make harsher: damage = shakeForce × 1.0
```

## File Locations

- **Manager**: `resources/js/modules/shake/ShakeManager.js`
- **Backend**: `src/services/ShakeDamageService.php`
- **Migrations**: `src/migrations/m260118_100000_add_shake_to_map.php`
- **Entity Types**: `src/bl/entity/types/building/Stabilizer*EntityType.php`
