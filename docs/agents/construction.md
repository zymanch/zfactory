# Construction System Documentation

## Purpose

Manages entity construction process from blueprint state to built state with visual progress animation.

## Entity State System

### Database Fields

- `entity.state`: ENUM('built', 'blueprint')
- `entity.durability`: INT (0 to max_durability)
- `entity.construction_progress`: INT (0-100, construction completion %)
- `entity_type.max_durability`: INT
- `entity_type.description`: VARCHAR (entity description for info window)
- `entity_type.construction_ticks`: INT (ticks to complete construction, 60 = 1 second)

### Sprite States (7 states + 9 construction frames)

```
{entity_folder}/
├── normal.png              # state='built', durability >= 50% (also used as icon)
├── damaged.png             # state='built', durability < 50%
├── blueprint.png           # state='blueprint' (legacy, not used)
├── normal_selected.png     # normal + hover in NORMAL mode
├── damaged_selected.png    # damaged + hover in NORMAL mode
├── deleting.png            # hover in DELETE mode (red outline)
├── crafting.png            # production animation sprite
└── construction/           # Construction animation frames
    ├── frame_0.png         # 0% - 11% progress
    ├── frame_1.png         # 11% - 22% progress
    ├── ...
    └── frame_8.png         # 89% - 100% progress
```

### Selection Logic

```javascript
getEntityTextureKey(entity, gameMode, isSelected) {
    // Blueprint state - show construction progress animation
    if (entity.state === 'blueprint') {
        const progress = entity.construction_progress || 0;
        const frameIndex = Math.min(8, Math.floor(progress / 11.11));
        return `entity_${typeId}_construction_${frameIndex}`;
    }

    // DELETE mode - show red outline on hover
    if (gameMode === GameMode.DELETE && isSelected) {
        return `entity_${typeId}_deleting`;
    }

    // Built state
    const isDamaged = durability < (maxDurability * 0.5);

    if (isDamaged) {
        return isSelected ? `entity_${typeId}_damaged_selected`
                          : `entity_${typeId}_damaged`;
    }
    return isSelected ? `entity_${typeId}_normal_selected`
                      : `entity_${typeId}_normal`;
}
```

### Hover Effect

- Entities have `eventMode = 'static'` for interactivity in NORMAL and DELETE modes
- `eventMode = 'none'` in other modes (BUILD, windows open)
- `pointerover` / `pointerout` events swap texture
- Hover sprite depends on current game mode (selected or deleting)

## ConstructionManager

### Location
`resources/js/modules/constructionManager.js` (if exists, or integrated in ResourceTransportManager)

### Construction Flow

1. **Blueprint Creation**:
   - Entity created with `state='blueprint'`
   - `construction_progress=0`
   - `durability=0`
   - Resources deducted from user inventory

2. **Progress Tracking**:
   - Server updates `construction_progress` every tick
   - Progress increases from 0% to 100%
   - Client receives updates via `/game/save-state` response

3. **Visual Animation**:
   - 9 construction frames (frame_0.png to frame_8.png)
   - Frame selected based on progress: `frameIndex = Math.floor(progress / 11.11)`
   - Frames show entity gradually appearing (0% = transparent, 100% = solid)

4. **Construction Completion**:
   - When `construction_progress >= 100`:
     - `state` changed to 'built'
     - `durability` set to `max_durability`
     - `construction_progress` reset to 0
   - Client updates sprite to normal state

### Backend Logic

```php
// In ConstructionService or game tick handler
foreach ($blueprints as $entity) {
    $entity->construction_progress += calculateProgressIncrement($entity);

    if ($entity->construction_progress >= 100) {
        $entity->state = 'built';
        $entity->durability = $entity->entityType->max_durability;
        $entity->construction_progress = 0;
        $entity->save();
    } else {
        $entity->save();
    }
}
```

### Frontend Logic

```javascript
// In game loop or state update
updateEntitySprite(entity) {
    const sprite = this.loadedEntities.get(entity.entity_id);
    if (!sprite) return;

    const textureKey = this.getEntityTextureKey(entity, this.currentGameMode, sprite.isSelected);
    const texture = this.graphics.getTexture(textureKey);

    if (texture) {
        sprite.texture = texture;
    }
}
```

## Construction Animation

### Frame Generation

Construction frames are generated during sprite generation process:

1. Load normal sprite
2. For each frame (0-8):
   - Calculate opacity: `opacity = (frameIndex + 1) / 9`
   - Create semi-transparent version
   - Save as `construction/frame_{frameIndex}.png`

### Atlas Integration

Construction frames are included in entity atlas:
- Row 2 of atlas contains 9 construction frames
- Coordinates calculated: `col = frameIndex, row = 2`

## Entity Info Window

### Location
`resources/js/modules/windows/entityInfoWindow.js`

### Purpose
Entity information modal window showing construction progress and details.

### Features

- Opens when clicking entity in NORMAL mode
- Shows entity name, description, durability bar
- Displays construction progress for blueprints
- Shows contained resources
- Available recipes for production buildings
- Esc to close and return to NORMAL mode

### Progress Display for Blueprints

```javascript
if (entity.state === 'blueprint') {
    const progress = entity.construction_progress || 0;
    const progressBar = `<div class="progress">
        <div class="progress-bar" style="width: ${progress}%">${progress}%</div>
    </div>`;

    window.innerHTML += `
        <div class="construction-info">
            <p>Under Construction</p>
            ${progressBar}
            <p>Time remaining: ${calculateTimeRemaining(entity)} seconds</p>
        </div>
    `;
}
```

## Integration Points

### Entity Creation

```javascript
// POST /map/create-entity
{
    entity_type_id: 101,
    x: 320,
    y: 240,
    state: 'blueprint'
}

// Response
{
    result: 'ok',
    entity: {
        entity_id: 123,
        state: 'blueprint',
        construction_progress: 0,
        durability: 0
    }
}
```

### Resource Deduction

When creating blueprint, resources are deducted from user inventory:

```php
$costs = EntityTypeCost::find()
    ->where(['entity_type_id' => $entityTypeId])
    ->all();

foreach ($costs as $cost) {
    $userResource = UserResource::findOne([
        'user_id' => $userId,
        'resource_id' => $cost->resource_id
    ]);

    if ($userResource->quantity < $cost->quantity) {
        return ['result' => 'error', 'message' => 'Not enough resources'];
    }

    $userResource->quantity -= $cost->quantity;
    $userResource->save();
}
```

## Workflows

### Creating Blueprint

1. User selects entity type from build panel
2. Enters BUILD mode
3. Places entity on valid location
4. Frontend sends POST to `/map/create-entity` with `state='blueprint'`
5. Backend checks resources and deducts costs
6. Backend creates entity with `construction_progress=0`
7. Frontend adds entity sprite with construction frame 0

### Construction Progress

1. Server game tick increments `construction_progress`
2. Client receives updated progress via state sync
3. Client updates sprite to appropriate construction frame
4. When progress reaches 100%, state changes to 'built'
5. Client updates sprite to normal state

### Manual Completion

Some games allow manual completion with additional resources:

```javascript
// POST /game/finish-construction
{
    entity_id: 123
}

// Backend
if ($entity->state === 'blueprint') {
    $entity->state = 'built';
    $entity->durability = $entity->entityType->max_durability;
    $entity->construction_progress = 0;
    $entity->save();
}
```

## File Locations

- **Entity State Logic**: `resources/js/game.js` (getEntityTextureKey method)
- **Entity Info Window**: `resources/js/modules/windows/entityInfoWindow.js`
- **Backend Service**: `src/services/ConstructionService.php` (if exists)
- **Sprites**: `public/assets/tiles/entities/{folder}/construction/`
