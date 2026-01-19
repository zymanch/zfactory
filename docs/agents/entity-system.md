# Entity System Documentation

## Purpose

Manages entity loading, rendering, tooltips, and information display for all in-game structures and machines.

## EntityLayerManager

### Location
`resources/js/modules/entityLayerManager.js`

### Purpose
Manages entity sprite creation, updates, and removal.

### Key Features

- Entity sprite creation from atlas textures
- Y-sorting for correct overlap (isometric perspective)
- Hover effects (selected state)
- Integration with fog of war
- Multi-tile entity support (width × height)
- Orientation variants (conveyors, manipulators)

### Entity Loading

```javascript
loadEntities(entitiesData) {
    for (const entity of entitiesData) {
        this.createEntitySprite(entity);
    }

    // Sort by Y for correct overlap
    this.entityLayer.sortChildren();
}
```

### Sprite Creation

```javascript
createEntitySprite(entity) {
    const entityType = this.game.entityTypes[entity.entity_type_id];
    const textureKey = this.game.getEntityTextureKey(entity, this.game.currentGameMode, false);

    const sprite = this.game.graphics.createSprite(textureKey);

    // Position
    const pixelX = entity.x * 64;
    const pixelY = entity.y * 64;
    sprite.x = pixelX;
    sprite.y = pixelY;

    // Store base position for shake animation
    sprite.baseX = pixelX;
    sprite.baseY = pixelY;

    // Z-index for sorting
    sprite.zIndex = pixelY;

    // Fog of war visibility
    if (this.game.fogOfWar && this.game.fogOfWar.enabled) {
        const tileX = Math.floor(entity.x / 64);
        const tileY = Math.floor(entity.y / 64);

        if (!this.game.fogOfWar.isVisible(tileX, tileY)) {
            sprite.visible = false;
            sprite.eventMode = 'none';
        }
    }

    // Hover events
    sprite.eventMode = 'static';
    sprite.on('pointerover', () => this.onEntityHover(entity, sprite));
    sprite.on('pointerout', () => this.onEntityHoverEnd(entity, sprite));
    sprite.on('click', () => this.onEntityClick(entity, sprite));

    this.entityLayer.addChild(sprite);
    this.game.loadedEntities.set(entity.entity_id, sprite);
}
```

### Y-Sorting

Entities sorted by Y coordinate for correct overlap:

```javascript
this.entityLayer.sortableChildren = true;
sprite.zIndex = pixelY;

// After adding all entities
this.entityLayer.sortChildren();
```

## EntityTooltip

### Location
`resources/js/modules/entityTooltip.js`

### Purpose
Displays entity information on hover.

### Features

- Entity name and durability bar
- Contained resources (fetched from server)
- Available recipes for production buildings

### Recipe Display

Shows inputs → output with resource icons:
- Time adjusted by entity power

**Time Formula**:
```javascript
time_seconds = (ticks / 60) * (100 / power)
```

- 60 ticks = 1 second at power=100
- power=200 executes 2x faster (halves time)
- Whole numbers display without decimals (1, not 1.0)

| Ticks | Power | Time Display |
|-------|-------|--------------|
| 60    | 100   | 1            |
| 30    | 100   | 0.5          |
| 120   | 200   | 1            |
| 120   | 400   | 0.5          |

### Tooltip Content

```javascript
showTooltip(entity) {
    const entityType = this.game.entityTypes[entity.entity_type_id];

    let html = `
        <div class="entity-tooltip">
            <h3>${entityType.name}</h3>
            <div class="durability-bar">
                <div class="durability-fill" style="width: ${durabilityPercent}%"></div>
            </div>
    `;

    // Show resources
    if (entity.resources && entity.resources.length > 0) {
        html += '<div class="resources">';
        for (const res of entity.resources) {
            const resource = this.game.resources[res.resource_id];
            html += `
                <div class="resource-item">
                    <img src="${resource.icon_url}" />
                    <span>${res.amount}</span>
                </div>
            `;
        }
        html += '</div>';
    }

    // Show recipes
    if (entityType.recipes && entityType.recipes.length > 0) {
        html += '<div class="recipes">';
        for (const recipeId of entityType.recipes) {
            const recipe = this.game.recipes[recipeId];
            const time = calculateTime(recipe.ticks, entityType.power);

            html += `
                <div class="recipe">
                    ${formatRecipe(recipe)} → ${time}s
                </div>
            `;
        }
        html += '</div>';
    }

    html += '</div>';

    this.tooltipElement.innerHTML = html;
    this.tooltipElement.style.display = 'block';
}
```

## Entity Info Window

### Location
`resources/js/modules/windows/entityInfoWindow.js`

### Purpose
Entity information modal window.

### Features

- Opens when clicking entity in NORMAL mode
- Shows entity name, description, durability bar
- Displays construction progress for blueprints
- Shows contained resources
- Available recipes for production buildings
- Esc to close and return to NORMAL mode

### Window Content

```javascript
openEntityInfo(entity) {
    const entityType = this.game.entityTypes[entity.entity_type_id];

    let content = `
        <h2>${entityType.name}</h2>
        <p>${entityType.description}</p>
    `;

    // Durability
    const durabilityPercent = (entity.durability / entityType.max_durability) * 100;
    content += `
        <div class="durability">
            <span>Durability: ${entity.durability}/${entityType.max_durability}</span>
            <div class="progress-bar">
                <div style="width: ${durabilityPercent}%"></div>
            </div>
        </div>
    `;

    // Construction progress
    if (entity.state === 'blueprint') {
        const progress = entity.construction_progress || 0;
        content += `
            <div class="construction">
                <span>Construction: ${progress}%</span>
                <div class="progress-bar">
                    <div style="width: ${progress}%"></div>
                </div>
            </div>
        `;
    }

    // Resources
    if (entity.resources && entity.resources.length > 0) {
        content += '<h3>Resources</h3><ul>';
        for (const res of entity.resources) {
            const resource = this.game.resources[res.resource_id];
            content += `<li>${resource.name}: ${res.amount}</li>`;
        }
        content += '</ul>';
    }

    // Recipes
    if (entityType.recipes && entityType.recipes.length > 0) {
        content += '<h3>Recipes</h3><ul>';
        for (const recipeId of entityType.recipes) {
            const recipe = this.game.recipes[recipeId];
            content += `<li>${formatRecipe(recipe)}</li>`;
        }
        content += '</ul>';
    }

    this.window.innerHTML = content;
    this.window.style.display = 'block';

    this.game.gameModeManager.switchMode(GameMode.ENTITY_INFO, { entity });
}
```

## Multi-Tile Entities

Entities can occupy multiple tiles (width × height):

```javascript
// 2×2 building
{
    entity_type_id: 140,
    width: 2,
    height: 2,
    x: 10,  // Top-left corner
    y: 5
}
```

### Collision Detection

```javascript
checkCollision(x, y, width, height) {
    for (const [entityId, entity] of this.game.entityData) {
        const entityType = this.game.entityTypes[entity.entity_type_id];

        // Check rectangle overlap
        if (x < entity.x + entityType.width &&
            x + width > entity.x &&
            y < entity.y + entityType.height &&
            y + height > entity.y) {
            return true;  // Collision detected
        }
    }

    return false;  // No collision
}
```

## Orientation Variants

Some entities have multiple orientations (conveyors, manipulators):

```javascript
// Conveyor variants
{
    entity_type_id: 100,  // Parent (right orientation)
    name: "Conveyor (Right)",
    orientation: "right"
}

{
    entity_type_id: 120,  // Variant
    name: "Conveyor (Down)",
    orientation: "down",
    parent_entity_type_id: 100
}
```

### Rotation in Build Mode

Press R (or К on Russian layout) to rotate:
- Cycles through: right → down → left → up
- Groups variants by `parent_entity_type_id`

## Integration Points

### Game Initialization

```javascript
// Load entities from server
const entitiesData = await loader.loadEntities();

// Create entity layer manager
this.entityLayerManager = new EntityLayerManager(this);
this.entityLayerManager.loadEntities(entitiesData);
```

### Entity Click Handling

```javascript
onEntityClick(entity, sprite) {
    const mode = this.game.currentGameMode;

    if (mode === GameMode.NORMAL) {
        // Open entity info window
        this.game.entityInfoWindow.openEntityInfo(entity);
    } else if (mode === GameMode.DELETE) {
        // Delete entity
        this.game.deleteEntity(entity.entity_id);
    }
}
```

### Entity Updates

```javascript
// Update entity state (e.g., durability change)
updateEntity(entityId, updates) {
    const entity = this.game.entityData.get(entityId);
    Object.assign(entity, updates);

    // Update sprite
    this.entityLayerManager.updateEntitySprite(entity);
}
```

## File Locations

- **EntityLayerManager**: `resources/js/modules/entityLayerManager.js`
- **EntityTooltip**: `resources/js/modules/entityTooltip.js`
- **EntityInfoWindow**: `resources/js/modules/windows/entityInfoWindow.js`
