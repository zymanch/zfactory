# Game Architecture Documentation

## Technology: PixiJS 8.x

### Why PixiJS?
1. **Performance**: WebGL rendering handles thousands of tiles smoothly
2. **Flexibility**: Full control over game logic
3. **Tile support**: Easy to implement tile layers
4. **Lightweight**: ~100KB minified
5. **Industry proven**: Used in many browser games

### Graphics Abstraction (2026-01)

**Problem**: Direct PixiJS imports in 15+ files made testing difficult and created tight coupling to rendering engine.

**Solution**: GraphicsEngine abstraction layer - single point of interaction with PixiJS.

**Benefits**:
- ✅ Zero direct PixiJS imports in business logic
- ✅ Testable with FakeGraphicsEngine (no graphics dependencies)
- ✅ Easy to swap rendering engines
- ✅ Centralized texture loading

**Architecture**:
```
Application Code (game.js, managers)
         ↓
   GraphicsEngine API
         ↓
      PixiJS 8.x
```

**GraphicsEngine** (`resources/js/core/GraphicsEngine.js`):
- Single class handling ALL PixiJS interactions
- Manages texture loading from manifest
- Creates containers, sprites, graphics primitives
- Provides unified API for game modules

**FakeGraphicsEngine** (`tests/helpers/FakeGraphicsEngine.js`):
- Test double implementing GraphicsEngine API
- Returns minimal mocks (objects with vi.fn() methods)
- Enables testing without PixiJS initialization
- Used in all unit and integration tests

## Asset Loading System

### Принцип централизации путей

**КРИТИЧНО**: Frontend НИКОГДА не должен содержать хардкод путей к ассетам (`/assets/...`).

Все пути к картинкам, спрайтам и атласам поступают из backend через API `/game/config`.

### Структура путей в API

**1. Entity atlases** - массив `entityTypes[id].atlases`:
```javascript
// Обычная entity (например, furnace)
{
  "entity_type_id": 1,
  "atlases": {
    "default": "/assets/tiles/entities/building/furnace/atlas.png"
  }
}

// Конвейер (множественные атласы для разных состояний)
{
  "entity_type_id": 100,
  "atlases": {
    "normal": "/assets/tiles/entities/conveyor/conveyor/normal_atlas.png",
    "damaged": "/assets/tiles/entities/conveyor/conveyor/damaged_atlas.png",
    "blueprint": "/assets/tiles/entities/conveyor/conveyor/blueprint_atlas.png",
    "normal_selected": "/assets/tiles/entities/conveyor/conveyor/normal_selected_atlas.png",
    "damaged_selected": "/assets/tiles/entities/conveyor/conveyor/damaged_selected_atlas.png"
  }
}
```

**2. Resource icons** - полный путь в `resources[id].icon_url`:
```javascript
{
  "resource_id": 1,
  "name": "Iron Ore",
  "icon_url": "/assets/tiles/resources/iron_ore.png"  // Полный путь
}
```

**3. Asset Manifest** - `assetManifest` содержит ВСЕ пути с короткими ключами:
```javascript
{
  // Landing atlases
  "landing_atlas_grass": "/assets/tiles/landing/atlases/grass_atlas.png?v=123",
  "landing_atlas_water": "/assets/tiles/landing/atlases/water_atlas.png?v=123",

  // Entity atlases
  "entity_atlas_1": "/assets/tiles/entities/building/furnace/atlas.png?v=123",

  // Resources
  "resource_1": "/assets/tiles/resources/iron_ore.png?v=123",

  // Deposits
  "deposit_1": "/assets/tiles/deposits/iron_deposit.png?v=123",

  // Conveyor atlases (состояние + ориентация)
  "conveyor_normal_right": "/assets/tiles/entities/conveyor/conveyor/normal_atlas.png?v=123",

  // Regions
  "region_1": "/assets/images/regions/island_1.png?v=123",

  // Technologies
  "technology_1": "/assets/tiles/technologies/automation.png?v=123",

  // Special textures
  "pipe_inlet_atlas": "/assets/tiles/pipe_inlets/inlet_atlas.png?v=123",
  "clouds_atlas": "/assets/clouds/clouds_atlas.png?v=123",
  "electrification": "/assets/tiles/electrification.png?v=123",
  "no_power": "/assets/tiles/no_power.png?v=123"
}
```

### Использование в коде

**❌ НЕПРАВИЛЬНО** - хардкод путей:
```javascript
img.src = '/assets/tiles/resources/iron.png';
const texture = await PIXI.Assets.load('/assets/tiles/entities/furnace/atlas.png');
```

**✅ ПРАВИЛЬНО** - использовать данные из API:
```javascript
// Для ресурсов - icon_url уже содержит полный путь
const resource = game.config.resources[resourceId];
img.src = resource.icon_url;

// Для текстур - использовать GraphicsEngine + assetManifest
const textureKey = `resource_${resourceId}`;
const texture = game.graphics.getTexture(textureKey);

// Для технологий - использовать assetManifest
const iconKey = `technology_${tech.id}`;
const iconUrl = game.config.assetManifest?.[iconKey] || `/assets/tiles/technologies/${tech.icon}`;
```

### Backend Implementation

**EntityType.php** - метод `getAtlases()`:
```php
public function getAtlases(): array
{
    $multiAtlasTypes = ['conveyor', 'underground_belt', 'splitter'];

    if (in_array($this->type, $multiAtlasTypes)) {
        // 5 атласов для каждого состояния
        $states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];
        $atlases = [];
        foreach ($states as $state) {
            $atlases[$state] = "/assets/tiles/entities/{$this->type}/{$this->folder}/{$state}_atlas.png";
        }
        return $atlases;
    }

    // Обычная entity - один атлас
    return ['default' => "/assets/tiles/entities/{$this->type}/{$this->folder}/atlas.png"];
}
```

**Config.php** - добавление путей:
```php
// Entity types
$data['atlases'] = $entityType->getAtlases();
$data['icon_url'] = $entityType->getIconUrl();

// Resources
$data['icon_url'] = "/assets/tiles/resources/{$resource->icon_url}";

// Asset manifest
$assets["region_{$region['region_id']}"] = "/assets/images/regions/{$region['image_url']}?v={$v}";
$assets["technology_{$tech['technology_id']}"] = "/assets/tiles/technologies/{$tech['icon']}?v={$v}";
$assets['pipe_inlet_atlas'] = "/assets/tiles/pipe_inlets/inlet_atlas.png?v={$v}";
```

### Обработка отсутствующих ассетов

GraphicsEngine автоматически коллекционирует отсутствующие ассеты при загрузке:

```javascript
// GraphicsEngine.loadAllTextures()
this.missingAssets = [];

try {
    const texture = await PIXI.Assets.load(url);
    this.textures.set(key, texture);
} catch (error) {
    this.missingAssets.push({
        key: key,
        url: url,
        error: error.message
    });
}

// После загрузки
const missingAssets = graphics.getMissingAssets();
if (missingAssets.length > 0) {
    ErrorModal.show('Отсутствующие ассеты', ...);
    // Игра продолжает работу с доступными ассетами
}
```

**Важно**:
- Загрузка НЕ прерывается при ошибках
- ErrorModal показывает список проблемных файлов
- Игра продолжает работать с доступными ассетами

### Добавление новых ассетов

1. **Добавить файл** в `public/assets/tiles/...`
2. **Backend**: добавить путь в `Config::getAssetManifest()`
3. **Frontend**: использовать ключ из manifest

**Пример** - добавление нового региона:
```php
// Backend: Config.php
$assets["region_{$region['region_id']}"] = "/assets/images/regions/{$region['image_url']}?v={$v}";
```

```javascript
// Frontend: regions.js
const assetKey = `region_${region.region_id}`;
img.src = window.gameConfig.assetManifest?.[assetKey] || `/assets/images/regions/${region.image_url}`;
```

### Migration Notes

**Старый подход** (до рефакторинга):
- Frontend содержал хардкод пути `/assets/tiles/resources/...`
- Прямые вызовы `PIXI.Assets.load()` в модулях
- Entity использовал `atlas_url` (строка)

**Новый подход** (после рефакторинга):
- Frontend получает все пути из API
- Централизованная загрузка через GraphicsEngine
- Entity использует `atlases` (массив)
- Полная поддержка версионирования (?v=123)

## Layer System

### Rendering Layers
```
Stage
└── worldContainer (scaled & positioned by camera)
    ├── landingLayer (terrain tiles, zIndex: 1)
    ├── electrificationLayer (powered areas, zIndex: 1.5)
    ├── depositLayer (natural resources, zIndex: 1.6)
    ├── entityLayer (buildings, zIndex: 2)
    └── fogLayer (fog of war overlay, zIndex: 9999)
```

**Layer Purpose:**
- **landingLayer**: Background terrain (grass, water, stone, etc.)
- **electrificationLayer**: Blue glowing dots showing powered areas
- **depositLayer**: Natural resources (trees, rocks, ores) - simplified rendering
- **entityLayer**: Player-built structures and machines
- **fogLayer**: Visibility mask based on Crystal Tower positions

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

### Map Structure
- **Shape**: Irregular floating island with wavy edges
- **Holes**: Several gaps inside the island
- **Size**: ~6251 tiles (was 7500 before shaping)

## Landing Transition System

Smooth wavy borders between adjacent terrain types create natural-looking terrain transitions.

### Transition Overview
For each adjacency pair, transition sprites are automatically generated with wavy edges using cosine-based algorithm. This creates natural, organic-looking transitions between different terrain types.

**Details**: See `docs/agents/tile-rendering.md` for TileLayerManager implementation and texture atlas system.

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

## Asset Loading

### Asset Manifest System (2026-01)

**Centralized Asset Management**: All asset URLs generated on backend and sent in single manifest.

**Benefits**:
- Single source of truth for all 600+ asset URLs
- Automatic cache busting with `?v=` version parameter
- No hardcoded paths in JavaScript
- Progress tracking during loading
- Batch loading optimization

**Backend** (`src/commands/actions/game/Config.php`):

Method `getAssetManifest()` generates manifest with short keys:

```php
protected function getAssetManifest() {
    $assets = [];
    $v = Yii::$app->params['asset_version'];

    // Landing textures (10 types)
    foreach ($this->getLandingTypes() as $id => $landing) {
        $folder = $landing['folder'];
        $assets["landing_{$id}"] = "/assets/tiles/landing/{$folder}/{$folder}_0.png?v={$v}";
        $assets["landing_atlas_{$folder}"] = "/assets/tiles/landing/atlases/{$folder}_atlas.png?v={$v}";
    }

    // Entity atlases (300+)
    foreach ($this->getEntityTypes() as $id => $entityType) {
        $assets["entity_atlas_{$id}"] = $entityType['atlas_url'] . "?v={$v}";
    }

    // Deposit sprites (22)
    foreach ($this->getDepositTypes() as $id => $depositType) {
        $assets["deposit_{$id}"] = $depositType['sprite_url'] . "?v={$v}";
    }

    // Resource icons (112)
    foreach ($this->getResources() as $id => $resource) {
        $assets["resource_{$id}"] = "/assets/tiles/resources/{$resource['icon_url']}?v={$v}";
    }

    // Special textures
    $assets['clouds_atlas'] = "/assets/clouds/clouds_atlas.png?v={$v}";
    $assets['electrification'] = "/assets/tiles/electrification.png?v={$v}";
    $assets['no_power'] = "/assets/tiles/no_power.png?v={$v}";

    return $assets;
}
```

**Frontend** (`resources/js/core/GraphicsEngine.js`):

```javascript
async loadAllTextures() {
    const keys = Object.keys(this.manifest);
    let loaded = 0;

    for (const key of keys) {
        const url = this.manifest[key];
        const texture = await PIXI.Assets.load(url);
        this.textures.set(key, texture);

        loaded++;
        this.emitProgress({ loaded, total: keys.length, percent: Math.round((loaded / keys.length) * 100), currentKey: key });
    }
}

getTexture(key) {
    const texture = this.textures.get(key);
    if (!texture) {
        console.warn(`[GraphicsEngine] Texture not found: ${key}`);
    }
    return texture;
}
```

### Bootstrap Flow (2026-01)

**New Entry Point**: `resources/js/bootstrap.js` orchestrates game initialization.

**Old Flow** (before 2026-01):
```
game.js → loadConfig() → loadTextures() → initPixi() → init()
```

**New Flow** (after 2026-01):
```
bootstrap.js:
  1. GameLoader.loadAll()         // Load data (config, entities, tiles)
  2. GraphicsEngine.init()         // Initialize PixiJS
  3. GraphicsEngine.loadTextures() // Load all assets with progress
  4. ZFactoryGame.init()           // Initialize game logic
```

**Bootstrap Code** (`resources/js/bootstrap.js`):
```javascript
import { GameLoader } from './core/GameLoader.js';
import { GraphicsEngine } from './core/GraphicsEngine.js';
import ZFactoryGame from './game.js';

document.addEventListener('DOMContentLoaded', async () => {
    try {
        // Phase 1: Load data
        showLoading('Loading game data...');
        const loader = new GameLoader(window.gameConfig.configUrl);
        const { config, entities, tiles } = await loader.loadAll();

        // Phase 2: Initialize graphics engine
        showLoading('Loading assets...');
        const graphics = new GraphicsEngine(config.assetManifest, {
            width: window.innerWidth,
            height: window.innerHeight,
            backgroundColor: 0x87CEEB
        });

        await graphics.initApplication(document.getElementById('game-container'));

        // Track asset loading progress
        graphics.onProgress((progress) => {
            updateProgress(progress.percent);
            showLoading(`Loading assets... ${progress.loaded}/${progress.total}`);
        });

        await graphics.loadAllTextures();

        // Phase 3: Initialize game
        showLoading('Initializing game...');
        const game = new ZFactoryGame(config, entities, tiles, graphics);
        await game.init();

        document.getElementById('loading').style.display = 'none';
        window.game = game;

    } catch (error) {
        console.error('[Bootstrap] Failed to initialize game:', error);

        // NEW (2026-01-15): User-friendly error messages with suggestions
        let errorMessage = 'Error loading game: ' + (error.message || 'Unknown error');
        if (error.message && error.message.includes('You may need to log in')) {
            errorMessage += '\n\nPlease refresh the page and log in.';
        }
        showLoading(errorMessage);
    }
});
```

**GameLoader** (`resources/js/core/GameLoader.js`):
- Separates data loading from game logic
- Handles AJAX requests to `/game/config`, `/game/entities`, `/map/tiles`
- **NEW (2026-01-15)**: Comprehensive validation of API responses:
  - Checks HTTP status codes (redirects = authentication required)
  - Validates Content-Type (detects HTML responses indicating login redirect)
  - Validates response structure (checks for required fields)
  - Shows user-friendly error messages (e.g., "You may need to log in")
- Emits events for progress tracking
- Parallel loading of entities and tiles

**ZFactoryGame Constructor Changes**:
```javascript
// OLD (before 2026-01)
constructor() {
    // ... empty initialization
}
async init() {
    await this.loadConfig();    // Ajax
    await this.loadTextures();  // PixiJS
    this.initPixi();
    // ... rest of init
}

// NEW (after 2026-01)
constructor(configData, entitiesData, tilesData, graphics) {
    this.graphics = graphics;  // Injected GraphicsEngine
    this.entityTypes = configData.entityTypes;
    this.resources = configData.resources;
    // ... all data pre-loaded
}
async init() {
    // No Ajax, no texture loading - just game logic initialization
    this.initModules();
    this.initLayers();
    this.loadMapTiles();
    this.loadEntities();
}
```

### Asset Versioning

Configured in `static_config.php`:
```php
'params' => [
    'asset_version' => 1, // Increment to bust cache
]
```

All assets automatically get `?v=1` query string via backend manifest generation.

## Data Access Patterns

### Entity Type Data (Reference Data)
All entity type related data is embedded in the `entityTypes` object:

```javascript
// Get entity type costs
const costs = game.entityTypes[entityTypeId].costs;
// Example: {"2": 10, "5": 5} - resource_id => quantity

// Get entity type recipes
const recipes = game.entityTypes[entityTypeId].recipes;
// Example: [3, 4, 5] - array of recipe_ids

// Get entity type behavior
const behavior = game.entityTypes[entityTypeId].behavior;
// Example: {behaviorClass: "MiningEntityBehavior", checksFog: true, ...}

// Check if entity type is mining (requires target deposit)
const isMining = game.entityTypes[entityTypeId].type === 'mining';
```

### Entity Instance Data (Dynamic Data)
Entity instance data remains in separate arrays for optimal payload size:

```javascript
// Get resources for specific entity
const resources = game.entityResources.filter(er => er.entity_id === entityId);
// Example: [{entity_id: 10, resource_id: 2, amount: 5}, ...]

// Get crafting state for entity
const crafting = game.craftingStates.find(cs => cs.entity_id === entityId);
// Example: {entity_id: 10, recipe_id: 3, ticks_remaining: 30}

// Get transport states for entity
const transports = game.transportStates.filter(ts => ts.entity_id === entityId);
```

## API Architecture

### GET /game/config
Load game configuration with all reference data. Called once on init.

**Response includes**:
- `landing` - terrain types
- `depositTypes` - natural resources
- `entityTypes` - building types with embedded costs, recipes, behavior
- `resources` - resource definitions
- `recipes` - crafting recipes
- `assetManifest` - all asset URLs with cache busting
- `config` - URLs and game constants

**Structure Changes (2026-01)**:
- `entityTypes` now includes embedded `costs`, `recipes`, and `behavior` (previously separate objects)
- `eyeEntities` removed (filter entities by `type='eye'` on client)
- **`pipeSystems` removed** - calculated client-side using BFS (no longer in API)
- `entityTypeCosts`, `entityTypeRecipes`, `buildingRules` removed (merged into entityTypes)

### GET /map/tiles
Load terrain tiles for viewport.

**Parameters**:
- `x`, `y` - starting tile coordinates
- `width`, `height` - viewport size in tiles

### GET /game/entities
Load entities for current region.

**Notes**:
- **NEW (2026-01):** `pipeSystems` removed - calculated client-side using BFS
- Entities include both island entities (`entity_id` as integer) and ship entities (`entity_id` as string with `ship_` prefix)
- Ship entity coordinates are converted to world coordinates (ship coords + region's ship_attach offset)
- **NEW (2026-01):** Entity state properties embedded in entities (resources, craftingState, transportState)
- Eye entities (type='eye') are filtered on client side from entities array for fog of war calculations

## Performance Optimizations

1. **Viewport culling**: Only load/render visible tiles + buffer
2. **Throttled updates**: Viewport reload max every 200ms
3. **Sprite pooling**: Reuse sprites when possible
4. **Z-index sorting**: `entityLayer.sortableChildren = true`
5. **Texture caching**: All textures loaded once at startup
6. **Texture atlases**: Reduce from ~170 to 10 texture atlases for terrain (sprite batching)

## Tile Dimensions

```javascript
const TILE_WIDTH = 64;
const TILE_HEIGHT = 64;

// Convert pixel coords to tile coords
const tileX = Math.floor(pixelX / TILE_WIDTH);
const tileY = Math.floor(pixelY / TILE_HEIGHT);

// Convert tile coords to pixel coords
const pixelX = tileX * TILE_WIDTH;
const pixelY = tileY * TILE_HEIGHT;
```

## File Structure

```
resources/js/
├── bootstrap.js                   # NEW (2026-01): Entry point, orchestrates initialization
├── game.js                        # Main game class (refactored 2026-01: no Ajax, no texture loading)
├── core/                          # NEW (2026-01): Core infrastructure
│   ├── GraphicsEngine.js          # PixiJS abstraction layer
│   └── GameLoader.js              # Ajax data loading
└── modules/
    ├── modes/                     # Game mode management
    │   ├── gameModeManager.js     # Centralized mode controller
    │   ├── gameModeBase.js        # Base class for modes (lifecycle, events)
    │   ├── buildMode.js           # Building placement mode
    │   └── landingEditMode.js     # Landing editing mode
    ├── windows/                   # UI windows
    │   ├── buildingWindow.js      # Building selection window
    │   ├── landingWindow.js       # Landing selection window
    │   └── entityInfoWindow.js    # Entity information window
    ├── ui/                        # UI components
    │   ├── CameraInfo.js          # Top-left debug info panel
    │   ├── ControlsHint.js        # Bottom-left keyboard hints
    │   └── BuildPanel.js          # Bottom-center 10-slot hotbar
    ├── camera.js                  # Camera movement and zoom
    ├── inputManager.js            # Keyboard and mouse handling
    ├── entityTooltip.js           # Entity hover tooltip
    ├── fogOfWar.js                # Fog of war visibility system
    ├── tileLayerManager.js        # Terrain rendering
    ├── entityLayerManager.js      # Entity rendering
    ├── resourceTransport/         # Resource transport system
    │   ├── ResourceTransportManager.js  # Main controller (fluid integration 2026-01)
    │   ├── BuildingState.js       # Building inventory/crafting
    │   └── TransporterState.js    # Conveyor belt state
    ├── pipes/                     # Pipe system
    │   ├── PipeSystemManager.js   # Fluid network manager (fluid integration 2026-01)
    │   ├── PipeRenderer.js        # Fluid visualization
    │   └── PipeConnectionManager.js  # Pipe connection logic
    ├── electricity/               # Electricity system
    │   ├── ElectricitySystemManager.js   # Power network manager
    │   ├── ElectrificationLayerManager.js  # Blue dots rendering
    │   └── NoPowerIndicator.js    # Warning icon
    └── ...

tests/
├── integration/
│   └── gameSimulation.test.js    # Integration tests (12/14 passing, fluid test enabled 2026-01)
└── helpers/
    ├── FakeGraphicsEngine.js      # NEW (2026-01): Test double for GraphicsEngine
    ├── mockGame.js                # Mock game instance
    ├── GameSimulator.js           # Game state simulator
    ├── MapBuilder.js              # ASCII map → game state
    └── fixtures.js                # Test data (resources with type='liquid' added 2026-01)

public/js/game.js                  # Compiled (webpack)
public/js/*.js                     # Code-split chunks
```

## Build

```bash
# Development (watch mode)
npm run assets-watch

# Production
npm run assets
```

## Debug Info

On-screen debug panel (top-left corner) shows:
- Current game mode (NORMAL, BUILD, DELETE, etc.)
- Camera position (pixels) and zoom level
- Loaded tiles count
- Loaded entities count
- FPS (smoothed calculation)
