# Construction Systems Agent

## Purpose

This agent helps with blueprint construction, progress tracking, state transitions, and construction sprite animation.

## Context

This agent has access to:
- `docs/agents/construction.md` - Complete construction system implementation
- `docs/common/DATABASE.md` - Database schema (entity.state, construction_progress)
- `docs/common/ARCHITECTURE.md` - Entity state system, sprite selection

## Workflows

### Workflow 1: Construction Flow

**Steps**:
1. **Blueprint Creation**:
```javascript
// POST /map/create-entity
{
    entity_type_id: 101,
    x: 320,
    y: 240,
    state: 'blueprint'
}
```

2. **Resource Deduction**:
```php
// Backend checks and deducts resources
$costs = EntityTypeCost::find()
    ->where(['entity_type_id' => $entityTypeId])
    ->all();

foreach ($costs as $cost) {
    $userResource->quantity -= $cost->quantity;
    $userResource->save();
}
```

3. **Progress Tracking**:
```php
// Game tick updates construction_progress
$entity->construction_progress += calculateProgressIncrement($entity);

if ($entity->construction_progress >= 100) {
    $entity->state = 'built';
    $entity->durability = $entity->entityType->max_durability;
    $entity->construction_progress = 0;
}
```

4. **Visual Animation**:
```javascript
// Client shows construction frame based on progress
const progress = entity.construction_progress || 0;
const frameIndex = Math.min(8, Math.floor(progress / 11.11));
const textureKey = `entity_${typeId}_construction_${frameIndex}`;
```

### Workflow 2: Sprite Animation

**Steps**:
1. **Construction Frames**:
   - 9 frames (frame_0.png to frame_8.png)
   - Each frame: 0%-11%, 11%-22%, ..., 89%-100%
   - Gradual opacity increase showing entity appearing

2. **Frame Generation**:
```php
// During sprite generation
for ($frameIndex = 0; $frameIndex <= 8; $frameIndex++) {
    $opacity = ($frameIndex + 1) / 9;
    $frame = applyOpacity($normalSprite, $opacity);
    savePng($frame, "construction/frame_{$frameIndex}.png");
}
```

3. **Atlas Integration**:
   - Row 2 of entity atlas contains construction frames
   - Coordinates: `col = frameIndex, row = 2`

### Workflow 3: Manual Completion

**Steps**:
1. **Create finish action**:
```php
// src/controllers/actions/game/FinishConstruction.php
public function run($entity_id) {
    $entity = Entity::findOne($entity_id);

    if ($entity->state !== 'blueprint') {
        return $this->error('Not a blueprint');
    }

    $entity->state = 'built';
    $entity->durability = $entity->entityType->max_durability;
    $entity->construction_progress = 0;
    $entity->save();

    return $this->success(['entity' => $entity]);
}
```

2. **Add UI button**:
```javascript
// In entityInfoWindow
if (entity.state === 'blueprint') {
    const finishButton = `
        <button onclick="finishConstruction(${entity.entity_id})">
            Finish Construction
        </button>
    `;
}
```

3. **Test completion**:
   - Click finish button
   - Verify state changes to 'built'
   - Check sprite updates to normal

## Integration Points

### With Resource System
- Resources deducted when blueprint created
- Costs from `entity_type_cost` table
- Check sufficient resources before creation

### With Entity Rendering
- Sprite texture selected based on state and progress
- Construction frames show gradual appearance
- Texture updates when progress changes

### With Game Loop
- Server updates `construction_progress` every tick
- Client receives updates via state sync
- Sprite texture updated on client

### With Info Window
- Shows construction progress bar
- Displays time remaining
- Updates in real-time as progress increases

## Rules and Best Practices

1. **State Management**: Only blueprints have construction_progress > 0
2. **Frame Selection**: `frameIndex = Math.floor(progress / 11.11)` ensures even distribution
3. **Resource Deduction**: Deduct resources immediately on blueprint creation
4. **Durability**: Blueprints have durability=0, built entities have max durability
5. **Progress Increment**: Balance construction_ticks for desired build time
6. **Visual Feedback**: Construction frames should clearly show progress
7. **Completion**: Always reset construction_progress to 0 when built

## Common Tasks

### Task: Adjust construction speed
1. Find entity type in database
2. Update `construction_ticks` field:
   - 60 ticks = 1 second
   - 120 ticks = 2 seconds
   - 300 ticks = 5 seconds
3. Formula: `time_seconds = construction_ticks / 60`
4. Test with blueprint placement

### Task: Create construction sprites
1. Load normal sprite
2. For each frame 0-8:
   - Calculate opacity: `(frameIndex + 1) / 9`
   - Apply opacity to normal sprite
   - Save as `construction/frame_{frameIndex}.png`
3. Generate atlas with construction row
4. Test in game with blueprint

### Task: Debug construction not completing
1. Check `construction_progress` increasing in database
2. Verify `construction_ticks` is set correctly
3. Check server game tick is running
4. Ensure progress reaches 100 (not 99)
5. Verify state changes to 'built' when complete
6. Check sprite texture updates to normal

### Task: Add manual finish option
1. Create FinishConstruction action
2. Check entity state is 'blueprint'
3. Set state='built', durability=max, progress=0
4. Return updated entity to client
5. Client updates sprite to normal state

## File Locations

- **Entity State Logic**: `resources/js/game.js` (getEntityTextureKey)
- **Entity Info Window**: `resources/js/modules/windows/entityInfoWindow.js`
- **Backend Service**: `src/services/ConstructionService.php` (if exists)
- **Sprites**: `public/assets/tiles/entities/{folder}/construction/`
- **Atlas Row**: Row 2 in entity atlas (9 frames)
