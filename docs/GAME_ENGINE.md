# Game Engine Documentation

## Technology: PixiJS 8.x

### Why PixiJS?
1. **Performance**: WebGL rendering handles thousands of tiles smoothly
2. **Flexibility**: Full control over game logic
3. **Tile support**: Easy to implement tile layers
4. **Lightweight**: ~100KB minified
5. **Industry proven**: Used in many browser games

## Architecture

### Layers
```
Stage
└── worldContainer (scaled & positioned by camera)
    ├── landingLayer (terrain tiles)
    ├── entityLayer (buildings, trees, etc.)
    └── fogLayer (fog of war overlay, zIndex: 9999)
```

### Rendering Pipeline
1. Camera position updated based on input
2. Viewport calculated (visible tile range)
3. AJAX requests to server for tiles/entities in viewport
4. Sprites created/updated/removed based on response
5. Entity layer sorted by Y for correct overlap

## Floating Islands System

The game world consists of floating islands in the sky. Islands have irregular edges and holes, creating a dramatic visual effect.

### Tile Layers (by zIndex)
```
zIndex: 0   - Sky tiles (background, auto-generated for empty spaces)
zIndex: 0.5 - Island edge tiles (auto-generated under real tiles)
zIndex: 1   - Real terrain tiles (from database)
```

### Island Edge Auto-Generation
The `island_edge` landing type (ID=10) is **never stored in the database**. Instead, it's automatically added by the game engine in `storeTileData()`:

```javascript
// Auto-insert island_edge under landings with empty space below
for (const tile of tiles) {
    if (tile.landing_id === LANDING_SKY_ID || tile.landing_id === LANDING_ISLAND_EDGE_ID) {
        continue;
    }

    const belowLandingId = this.tileDataMap.get(tileKey(tile.x, tile.y + 1));

    if (belowLandingId === undefined || belowLandingId === LANDING_SKY_ID) {
        this.tileDataMap.set(tileKey(tile.x, tile.y + 1), LANDING_ISLAND_EDGE_ID);
    }
}
```

**Logic:**
- For each non-sky, non-island_edge tile
- Check if there's empty space or sky below
- Auto-insert `island_edge` at (x, y+1)

### Sky Auto-Generation
The `sky` landing type (ID=9) is automatically added to the left of all non-sky tiles:

```javascript
// Auto-insert sky to the left of landings with empty space on the left
for (const tile of tiles) {
    if (tile.landing_id === LANDING_SKY_ID) {
        continue;
    }

    const leftLandingId = this.tileDataMap.get(tileKey(tile.x - 1, tile.y));

    if (leftLandingId === undefined) {
        this.tileDataMap.set(tileKey(tile.x - 1, tile.y), LANDING_SKY_ID);
    }
}
```

**Logic:**
- For each non-sky tile
- Check if there's empty space to the left
- Auto-insert `sky` at (x-1, y)

### Island Edge Sprite
```
public/assets/tiles/landing/island_edge.svg
┌────────────────────────────┐
│  Earth (brown #8b6914)     │  ← Top half
│  ~~~~~~~~~~~~~~~~~~~~~~    │  ← Wavy border
│  Sky (blue #87CEEB)        │  ← Bottom half
│  ☁️ clouds                  │
└────────────────────────────┘
```

## Landing Transition System

Smooth wavy borders between adjacent terrain types create natural-looking terrain transitions.

### Adjacency Table
The `landing_adjacency` table defines which terrain types can have smooth transitions:

| Landing | Can Border With |
|---------|-----------------|
| Grass (1) | Dirt, Sand, Snow, Swamp |
| Dirt (2) | Grass, Sand, Stone |
| Sand (3) | Grass, Dirt, Water |
| Water (4) | Sand, Lava, Swamp |
| Stone (5) | Dirt, Lava, Snow |
| Lava (6) | Water, Stone |
| Snow (7) | Grass, Stone |
| Swamp (8) | Grass, Water |

**Excluded**: Sky (9), Island Edge (10)

### Transition Variants
For each adjacency pair, 3 transition sprites are generated:
- `{base}_{adjacent}_r.jpg` - Right edge transition (wavy line on right)
- `{base}_{adjacent}_t.jpg` - Top edge transition (wavy line on top)
- `{base}_{adjacent}_rt.jpg` - Corner transition (diagonal wavy line)

### Rendering Logic
```javascript
// In TileLayerManager.js
createTileWithTransitions(landingId, tileX, tileY) {
    const topLandingId = this.getLandingAt(tileX, tileY - 1);
    const rightLandingId = this.getLandingAt(tileX + 1, tileY);

    const needsTop = topLandingId !== landingId && hasAdjacency(landingId, topLandingId);
    const needsRight = rightLandingId !== landingId && hasAdjacency(landingId, rightLandingId);

    if (needsTop && needsRight) {
        textureKey = `transition_${landingId}_${topLandingId}_rt`;
    } else if (needsTop) {
        textureKey = `transition_${landingId}_${topLandingId}_t`;
    } else if (needsRight) {
        textureKey = `transition_${landingId}_${rightLandingId}_r`;
    } else {
        textureKey = `landing_${landingId}`;
    }
}
```

### Generation Command
```bash
# Generate all transition sprites (66 files)
php yii landing/generate-transitions

# List defined adjacencies
php yii landing/list-adjacencies
```

### Transition Sprite Location
```
public/assets/tiles/landing/transitions/
├── grass_dirt_r.jpg      # Grass base, dirt on right
├── grass_dirt_t.jpg      # Grass base, dirt on top
├── grass_dirt_rt.jpg     # Grass base, dirt on both
├── dirt_grass_r.jpg      # Dirt base, grass on right
├── ...
```

### Map Structure
- **Shape**: Irregular floating island with wavy edges
- **Holes**: Several gaps inside the island
- **Size**: ~6251 tiles (was 7500 before shaping)

## Camera System

### Position
- Camera starts at (0, 0) - top-left of map
- World container offset = negative camera position
```javascript
worldContainer.x = -camera.x * zoom;
worldContainer.y = -camera.y * zoom;
```

### Movement
- WASD / Arrow keys
- Speed adjusted by zoom level: `speed / zoom`
- Supports RU keyboard layout (ФЫВА)

### Zoom
- Mouse wheel: 1x to 3x range (cannot zoom out below default)
- **Zooms relative to screen center**
- Algorithm:
  1. Calculate world position at screen center (before zoom)
  2. Apply new zoom
  3. Adjust camera so center stays at same world position

```javascript
// Before zoom
const centerX = camera.x + (screenWidth / 2) / oldZoom;
const centerY = camera.y + (screenHeight / 2) / oldZoom;

// After zoom
camera.x = centerX - (screenWidth / 2) / newZoom;
camera.y = centerY - (screenHeight / 2) / newZoom;
```

## Entity State System

### Database Fields
- `entity.state`: ENUM('built', 'blueprint')
- `entity.durability`: INT (0 to max_durability)
- `entity_type.max_durability`: INT

### Sprite States (5 per entity type)
```
{entity_folder}/
├── normal.svg           # state='built', durability >= 50%
├── damaged.svg          # state='built', durability < 50%
├── blueprint.svg        # state='blueprint'
├── normal_selected.svg  # normal + mouse hover
└── damaged_selected.svg # damaged + mouse hover
```

### Selection Logic
```javascript
getEntityTextureKey(entity, isSelected) {
    if (entity.state === 'blueprint') {
        return `entity_${typeId}_blueprint`;
    }

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
- Entities have `eventMode = 'static'` for interactivity
- `pointerover` / `pointerout` events swap texture
- Blueprint entities do not respond to hover

## Asset Loading

### Asset Versioning
All assets use version query string for cache busting:
```javascript
// In game.js
assetUrl(path) {
    const v = this.config.assetVersion || 1;
    return `${path}?v=${v}`;
}
```

Configured in `static_config.php`:
```php
'params' => [
    'asset_version' => 1, // Increment to bust cache
]
```

### Terrain Textures
```javascript
// Single file per terrain type
const url = this.assetUrl(this.config.tilesPath + 'landing/' + landing.folder + '.png');
textures['landing_1'] = await PIXI.Assets.load(url);
```

### Entity Textures
```javascript
// 5 files per entity type (folder-based)
const states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];
for (const state of states) {
    const url = this.assetUrl(`${this.config.tilesPath}entities/${folder}/${state}.${ext}`);
    textures[`entity_${typeId}_${state}`] = await PIXI.Assets.load(url);
}
```

## API Endpoints

### GET /game/config
Load game configuration with all reference data. Called once on init.

**Response:**
```json
{
    "result": "ok",
    "landing": {
        "1": {"landing_id": 1, "name": "Grass", "folder": "grass", ...}
    },
    "entityTypes": {
        "100": {"entity_type_id": 100, "name": "Conveyor", "extension": "svg", "max_durability": 100, "width": 1, "height": 1, "icon_url": "conveyor/icon.svg", ...}
    },
    "buildPanel": [101, null, 102, 103, null, null, null, null, null, 105],
    "config": {
        "mapUrl": "http://zfactory.local/map/tiles",
        "entitiesUrl": "http://zfactory.local/game/entities",
        "createEntityUrl": "http://zfactory.local/map/create-entity",
        "saveBuildPanelUrl": "http://zfactory.local/user/save-build-panel",
        "tilesPath": "/assets/tiles/",
        "tileWidth": 32,
        "tileHeight": 24,
        "assetVersion": 1,
        "cameraSpeed": 8
    }
}
```

### GET /map/tiles
Load terrain tiles for viewport.

**Parameters:**
- `x`, `y` - starting tile coordinates
- `width`, `height` - viewport size in tiles

**Response:**
```json
{
    "result": "ok",
    "tiles": [
        {"map_id": 1, "landing_id": 1, "x": 0, "y": 0}
    ]
}
```

### GET /game/entities
Load entities for viewport.

**Parameters:**
- `x`, `y` - starting pixel coordinates
- `width`, `height` - viewport size in pixels

**Response:**
```json
{
    "result": "ok",
    "entities": [
        {"entity_id": 1, "entity_type_id": 100, "state": "built", "durability": 100, "x": 320, "y": 240}
    ]
}
```

### POST /map/create-entity
Create new entity (building placement).

**Body (JSON):**
```json
{
    "entity_type_id": 101,
    "x": 320,
    "y": 240,
    "state": "blueprint"
}
```

**Response:**
```json
{
    "result": "ok",
    "entity": {
        "entity_id": 123,
        "entity_type_id": 101,
        "x": 320,
        "y": 240,
        "state": "blueprint",
        "durability": 0
    }
}
```

**Errors:**
- `entity_type_id required` - missing entity type
- `Invalid entity_type_id` - unknown entity type
- `Position occupied` - collision with existing entity

### POST /user/save-build-panel
Save user's build panel configuration.

**Body (JSON):**
```json
{
    "slots": [101, null, 102, 103, null, null, null, null, null, 105]
}
```

**Response:**
```json
{
    "result": "ok"
}
```

## Performance Optimizations

1. **Viewport culling**: Only load/render visible tiles + buffer
2. **Throttled updates**: Viewport reload max every 200ms
3. **Sprite pooling**: Reuse sprites when possible
4. **Z-index sorting**: `entityLayer.sortableChildren = true`
5. **Texture caching**: All textures loaded once at startup

## Tile Dimensions

```javascript
const TILE_WIDTH = 32;
const TILE_HEIGHT = 24;

// Convert pixel coords to tile coords
const tileX = Math.floor(pixelX / TILE_WIDTH);
const tileY = Math.floor(pixelY / TILE_HEIGHT);

// Convert tile coords to pixel coords
const pixelX = tileX * TILE_WIDTH;
const pixelY = tileY * TILE_HEIGHT;
```

## Debug Info

On-screen debug panel shows:
- Camera position (pixels)
- Loaded tiles count
- Loaded entities count
- FPS

## File Structure

```
resources/js/
├── game.js                    # Main game class
└── modules/
    ├── camera.js              # Camera movement and zoom
    ├── inputManager.js        # Keyboard and mouse handling
    ├── buildPanel.js          # 10-slot hotbar for buildings
    ├── buildingWindow.js      # Modal window with building list
    ├── buildMode.js           # Building placement logic
    └── fogOfWar.js            # Fog of war visibility system

public/js/game.js              # Compiled (webpack)
public/js/*.js                 # Code-split chunks
```

### EntityTooltip (`entityTooltip.js`)
Displays entity information on hover:
- Entity name and durability bar
- Contained resources (fetched from server)
- Available recipes for production buildings

**Recipe Display:**
- Shows inputs → output with resource icons
- Time adjusted by entity power

**Time Formula:**
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

## Module Descriptions

### Camera (`camera.js`)
Handles camera movement and zoom:
- WASD/Arrow movement with RU layout support
- Mouse wheel zoom (1x-3x)
- Zoom relative to screen center
- Viewport bounds calculation

### InputManager (`inputManager.js`)
Centralized input handling:
- Keyboard state tracking
- Mouse position tracking
- Screen-to-world/tile coordinate conversion
- Key bindings:
  - **B** - open buildings window
  - **1-0** - select build panel slot
  - **R/К** - rotate building (in build mode)
  - **Esc** - cancel build mode
  - **F** - toggle fog of war (debug)

### BuildPanel (`buildPanel.js`)
10-slot hotbar at bottom center:
- Drag & drop from BuildingWindow
- Number keys 1-0 activation
- Server persistence via AJAX (user.build_panel)
- Right-click to clear slot
- Debounced save (500ms)

### BuildingWindow (`buildingWindow.js`)
Modal window for building selection:
- Opens with B key
- Shows entities grouped by type (tabs)
- **Filters out orientation variants** (entities with `parent_entity_type_id`)
- Drag items to BuildPanel
- Click to add to first empty slot

### BuildMode (`buildMode.js`)
Building placement on map:
- Preview sprite follows mouse
- Green/red tint for valid/invalid placement
- Collision detection with existing entities
- Multi-tile entity support (width/height)
- AJAX POST to create entity
- **Rotation support**: Press **R** (or **К** on Russian layout) to rotate
  - Works for entities with orientation variants (conveyors, manipulators)
  - Cycles through: right → down → left → up
  - Groups variants by `parent_entity_type_id`

### FogOfWar (`fogOfWar.js`)
Visibility system based on Crystal Towers (type='eye'):
- Only tiles within radius of eye entities are visible
- Radius = entity_type.power (in tiles)
- Non-visible areas covered by black fog (alpha 0.95)
- Edge tiles (adjacent to visible) have half fog (alpha 0.5)
- Entities in fog are hidden (not rendered, not interactive)
- Recalculates when eye entities change
- Toggle with F key for debugging

**Visibility Algorithm:**
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

**Crystal Tower Types:**
| Type                  | Power | Radius (tiles) |
|-----------------------|-------|----------------|
| Small Crystal Tower   | 7     | ~7 tiles       |
| Medium Crystal Tower  | 15    | ~15 tiles      |
| Large Crystal Tower   | 30    | ~30 tiles      |

## Authentication

### Login Flow
1. User visits landing page (`/`)
2. Clicks "ВОЙТИ!" button
3. Auto-login as user_id=1 (demo mode)
4. Redirect to game (`/game`)

### User Model
Implements Yii2 `IdentityInterface`:
- `findIdentity($id)` - find by ID
- `findIdentityByAccessToken($token)` - find by auth_key
- `getId()`, `getAuthKey()`, `validateAuthKey()`
- `getBuildPanelArray()` - get 10-slot array from JSON
- `setBuildPanelArray($array)` - save array as JSON

## Routes

| Route                   | Action Class                  | Description              |
|-------------------------|-------------------------------|--------------------------|
| `/`                     | `actions\site\Index`          | Landing page             |
| `/site/login`           | `actions\site\Login`          | Auto-login (demo)        |
| `/site/logout`          | `actions\site\Logout`         | Logout                   |
| `/game`                 | `actions\game\Index`          | Game page                |
| `/game/config`          | `actions\game\Config`         | Game configuration       |
| `/game/entities`        | `actions\game\Entities`       | Load entities            |
| `/map/tiles`            | `actions\map\Tiles`           | Load terrain tiles       |
| `/map/create-entity`    | `actions\map\CreateEntity`    | Place building           |
| `/user/save-build-panel`| `actions\user\SaveBuildPanel` | Save build panel slots   |

## Texture Atlases (Landing Transitions)

The terrain transition system uses texture atlases for optimal rendering performance.

### Atlas Structure

Each landing type has its own atlas: `{name}_atlas.png`

**Dimensions:**
- Width: `11 × 32px = 352px` (all possible landing types 0-10)
- Height: `12 × 24px = 288px` (row 0 for variations + rows 1-11 for transitions)

**Structure:**
```
Row 0: Вариации базового тайла (первые 5 колонок заполнены)
Row 1: Переходы когда сверху тот же лендинг (самоссылка)
Row 2-9: Переходы с разными лендингами сверху (landing_id 1-8)
Row 10: Переходы со sky сверху (landing_id 9)
Row 11: Переходы с island_edge сверху (landing_id 10)

Column 0: Самоссылка справа (right = self)
Column 1-8: Разные лендинги справа (landing_id 1-8)
Column 9: Sky справа (landing_id 9)
Column 10: Island edge справа (landing_id 10)
```

### Atlas Coordinate Formula

**Simple system using `landing_id` directly:**

```javascript
// Special case: Both neighbors match current landing - use variations
if (top === landingId && right === landingId) {
    return {
        row: 0,
        col: Math.floor(Math.random() * variationsCount)  // 0-4
    };
}

// Row calculation (neighbor above)
if (top === null) {
    row = LANDING_SKY_ID + 1;  // 10 (sky is landing_id=9)
} else {
    row = top + 1;  // Neighbor landing_id + 1 (row 0 is variations)
}

// Column calculation (neighbor to the right)
if (right === null) {
    col = LANDING_SKY_ID;  // 9
} else {
    col = right;  // Neighbor landing_id
}
```

**No database lookup needed** - coordinates computed directly from neighbor `landing_id`.

### Special Transition Rules for Island Edge

Island Edge has special rendering rules applied during atlas generation (in `LandingTransitionGenerator.php`):

```php
// 1. For Sky atlas: if top is Island Edge, treat as Sky
if ($landingId == 9 && $topId == 10) {
    $topImage = $landingImages[9];
}

// 2. For Island Edge atlas: if right is Sky, treat as Island Edge
if ($landingId == 10 && $rightId == 9) {
    $rightImage = $landingImages[10];
}
```

**Purpose:**
- Creates seamless transitions between Island Edge and Sky
- Prevents visual discontinuities at floating island boundaries
- Applied during atlas generation, not during runtime rendering

### PIXI.Rectangle for Sub-textures

```javascript
const inset = 0.5;  // Prevent texture bleeding
const rect = new PIXI.Rectangle(
    col * 32 + inset,
    row * 24 + inset,
    32 - inset * 2,
    24 - inset * 2
);

const texture = new PIXI.Texture({
    source: atlas.source,
    frame: rect
});
```

### Performance Benefits

- **Sprite Batching**: All tiles of same type batched into single draw call
- **Fewer Texture Switches**: Reduced from ~170 to 10 texture atlases
- **Performance Gain**: 2-3x FPS improvement through reduced WebGL state changes
- **Memory Efficient**: Single 352×288px texture per landing type
- **Simple Coordinates**: Direct `landing_id` mapping without database lookups

### Wavy Transition Algorithm

Transitions between different terrain types use cosine-based wavy lines for natural-looking borders:

**Parameters:**
- `waveAmplitude = 1` - Wave displacement in pixels
- `waveFrequency = 2.0` - Number of waves across the tile
- `outlineWidth = 1` - Width of darkened border line

**Formula (right edge example):**
```php
for ($y = 0; $y < $tileHeight; $y++) {
    $t = $y / ($tileHeight - 1);  // Normalize to 0-1
    $wave = cos($t * 2 * M_PI * $waveFrequency) * $waveAmplitude;
    $wavyX[$y] = (int)round($tileWidth - 1 - $waveAmplitude + $wave);
}
```

**Transition Types:**
- `generateRightTransition()` - Vertical wavy line on right edge
- `generateTopTransition()` - Horizontal wavy line on top edge
- `generateCornerTransition()` - L-shaped wavy line for both edges

### Landing Variations

Each landing type has 5 pre-generated variations stored in folders:

**File Structure:**
```
public/assets/tiles/landing/
├── grass/
│   ├── grass_0.png  (64x64 px)
│   ├── grass_1.png
│   ├── grass_2.png
│   ├── grass_3.png
│   └── grass_4.png
├── dirt/
│   ├── dirt_0.png
│   └── ...
```

**Initial Generation:**
Variations were created once using `VariationGenerator.php` with:
- **Color shifts**: ±10 hue, ±5 saturation, ±5 brightness
- **Noise**: 5% of pixels get ±3 RGB variation

**Replacement:**
You can replace these PNG files with custom high-quality textures. After replacement, regenerate atlases:

```bash
php yii landing/generate
npm run assets
```

### Generation Command

```bash
# Generate all texture atlases (reads from variation folders)
php yii landing/generate
```

This generates all texture atlases in `public/assets/tiles/landing/atlases/`.

## Build

```bash
# Development (watch mode)
npm run assets-watch

# Production
npm run assets
```
