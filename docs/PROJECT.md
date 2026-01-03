# ZFactory - Browser Automation Game

## Overview
ZFactory is a browser-based automation game inspired by Factorio. The game features 2D isometric-style graphics with a tile-based map system.

**Visual Style**: Floating islands in the sky. The map has irregular wavy edges and holes, with visible "island bottom" effect where terrain meets the sky below.

## Tech Stack
- **Backend**: PHP 7.2+, Yii2 Framework (v2.0.42)
- **Database**: MySQL/MariaDB
- **Frontend**: Bootstrap 5, jQuery 3.7
- **Build**: Webpack, Laravel Mix, SASS
- **Game Engine**: PixiJS 8.x

## Tile System

### Dimensions
- **Base tile size**: 64px x 64px
- All game elements must be multiples of this base size
- Entities can span multiple tiles (e.g., 128x128 = 2x2 tiles)

### Three-Layer Architecture
1. **Background Layer (landing)** - terrain tiles (grass, water, stone, etc.)
   - Always 64x64 px
   - Stored in `landing` table (types) and `map` table (instances)
   - zIndex: 1

2. **Deposit Layer (deposit)** - natural resources (trees, rocks, ores)
   - Simplified rendering (only normal.png sprite)
   - Stored in `deposit_type` table (types) and `deposit` table (instances)
   - Removed when extraction buildings are placed
   - zIndex: 1.5

3. **Entity Layer (entity)** - buildings, conveyors, extraction facilities, etc.
   - Can be larger: width = N*64px, height = M*64px
   - Stored in `entity_type` table (types) and `entity` table (instances)
   - zIndex: 2

### Map Size
- **Max bounds**: 50x28 tiles (3200x1792 pixels)
- **Actual tiles**: ~6251 (floating island shape)
- **Style**: Irregular edges, internal holes, sky background

### Landing Variations & Texture Atlases
Terrain uses texture atlases with 5 procedural variations per landing type:
- **Texture Atlases**: 704×768px (11 columns × 12 rows) containing all transition combinations
- **Variations**: Each landing type has 5 variations generated via AI (img2img)
- **Location**: `public/assets/tiles/landing/atlases/`
- **Naming**: `{name}_atlas.png` (e.g., `grass_atlas.png`)

### AI Sprite Generation (FLUX.1 Dev via ComfyUI)

**Current System**: FLUX.1 Dev model via ComfyUI API (local)
- **Model**: FLUX.1 Dev (12GB VRAM recommended)
- **API**: ComfyUI running on http://localhost:8188
- **Features**: High-quality photorealistic sprites, automatic background removal, state generation
- **Optimal Parameters**: CFG=1.5, steps=30

**Tile Size Configuration**:
All sprite dimensions use centralized config from `static_config.php`:
```php
'params' => [
    'tile_width' => 64,   // pixels per tile width
    'tile_height' => 64,  // pixels per tile height
]
```

#### Landing Sprite Workflow
```bash
# 1. Start ComfyUI (separate terminal)
cd ai
start_comfyui.bat

# 2. Generate AI sprites with variations
php yii landing/generate-ai all        # All landings
php yii landing/generate-ai grass      # Single landing

# 3. Scale to 64×64 and create variations
php yii landing/scale-original

# 4. Generate texture atlases
php yii landing/generate

# 5. Compile assets
npm run assets
```

#### Entity Sprite Workflow
```bash
# 1. Start ComfyUI (if not running)
cd ai
start_comfyui.bat

# 2. Generate entity sprites (all 5 states)
php yii entity/generate-ai-flux all              # All entities
php yii entity/generate-ai-flux furnace          # Single entity
php yii entity/generate-ai-flux furnace 1        # Test mode (only normal.png)
php yii entity/generate-ai-flux furnace 0 1      # States only (regenerate states from existing normal.png)

# 3. Generate construction/blueprint frames
php yii entity/generate-states                   # Generates blueprint.png and construction_*.png for all entities

# 4. Generate texture atlases
php yii entity/generate                          # Creates texture atlases from sprite folders

# 5. Compile assets
npm run assets
```

#### Deposit Sprite Workflow
```bash
# 1. Generate deposit sprites (only normal.png)
php yii deposit/generate-ai-flux all             # All deposits
php yii deposit/generate-ai-flux ore_iron        # Single deposit

# 2. Compile assets
npm run assets
```

**Stopping ComfyUI**:
- Ctrl+C in terminal window, or:
- `netstat -ano | findstr ":8188"` to find PID, then `taskkill //PID <pid> //F`

## Deposit System

Natural resources (trees, rocks, ores) are managed separately from entities using a simplified system.

### Deposit Types
Deposits are categorized into three types:
- **Trees** (deposit_type_id 1-8): Pine, Oak, Dead, Birch, Spruce, Maple, Willow, Ash
- **Rocks** (deposit_type_id 10-12): Small, Medium, Large
- **Ores** (deposit_type_id 300-305): Iron, Copper, Aluminum, Titanium, Silver, Gold

### Key Differences from Entities
- **Single sprite**: Only `normal.png` (no damaged, selected, blueprint states)
- **Always 1x1 tiles**: For collision/placement calculations (visual sprite may be larger)
- **Non-interactive**: Cannot be selected or directly destroyed
- **Auto-removed**: Deleted when extraction buildings placed on them

### Extraction Buildings
Different building types required for different deposit types:

| Building Type | Deposit Type | Examples |
|---------------|--------------|----------|
| Sawmill | Trees | Small/Medium/Large Sawmill (1x1, 3x3, 5x5) |
| Stone Quarry | Rocks | Small/Medium/Large Stone Quarry (1x1, 3x3, 5x5) |
| Ore Drill | Iron/Copper Ores | Small/Medium/Large Drill (1x1, 2x2, 3x3) |
| Mine | Silver/Gold Ores | Small/Medium/Large Mine (1x1, 2x2, 3x3) |
| Quarry | Aluminum/Titanium Ores | Small/Medium/Large Quarry (1x1, 2x2, 3x3) |

### Placement Logic
When placing extraction building:
1. **Behavior checks** deposit type requirements (via DepositEntityBehavior)
2. **All deposits** in building footprint are removed
3. **Resources transferred** from deposits to building's storage
4. Building begins construction as blueprint

### Sprite Location
```
public/assets/tiles/deposits/
├── tree_pine/normal.png
├── rock_small/normal.png
├── ore_iron/normal.png
└── ...
```

## Project Structure
```
zfactory.local/
├── docs/                   # Documentation
│   └── sql/               # SQL scripts for data
├── public/                 # Web root
│   ├── assets/tiles/      # Game sprites
│   │   ├── landing/       # Terrain tiles (texture atlases)
│   │   └── entities/      # Entity sprite folders
│   │       ├── conveyor/  # 5 state PNG files each
│   │       ├── drill/
│   │       └── ...
│   ├── css/               # Compiled CSS
│   ├── js/                # Compiled JS
│   └── index.php          # Entry point
├── resources/              # Source assets
│   ├── css/               # SASS source
│   │   └── game.scss      # Game styles
│   └── js/                # JS source
│       ├── game.js        # Main game class
│       └── modules/       # Game modules
│           ├── modes/                  # Game mode management
│           │   ├── gameModeManager.js
│           │   ├── buildMode.js
│           │   └── landingEditMode.js
│           ├── windows/                # UI windows
│           │   ├── buildingWindow.js
│           │   ├── landingWindow.js
│           │   └── entityInfoWindow.js
│           ├── ui/                     # UI components
│           │   ├── CameraInfo.js
│           │   ├── ControlsHint.js
│           │   └── BuildPanel.js
│           ├── camera.js
│           ├── inputManager.js
│           ├── depositLayerManager.js  # Deposit rendering
│           ├── depositTooltip.js       # Deposit hover info
│           ├── depositBehaviors.js     # Deposit placement logic
│           ├── entityTooltip.js
│           ├── fogOfWar.js
│           └── ...
├── src/                    # PHP source code
│   ├── actions/           # Standalone action classes
│   │   ├── Base.php              # Base action class (web)
│   │   ├── JsonAction.php        # Base for JSON API actions
│   │   ├── ConsoleAction.php     # Base for console actions
│   │   ├── game/                 # GameController actions
│   │   │   ├── Index.php
│   │   │   ├── Entities.php
│   │   │   └── Config.php
│   │   ├── landing/              # LandingController actions
│   │   │   ├── Generate.php      # Generate texture atlases
│   │   │   ├── GenerateAi.php    # Generate via Stable Diffusion API
│   │   │   └── ScaleOriginal.php # Scale & create variations
│   │   ├── map/                  # MapController actions
│   │   │   ├── Tiles.php
│   │   │   └── CreateEntity.php
│   │   ├── site/                 # SiteController actions
│   │   │   ├── Index.php
│   │   │   ├── Login.php
│   │   │   └── Logout.php
│   │   └── user/                 # UserController actions
│   │       └── SaveBuildPanel.php
│   ├── app/               # Application services
│   │   └── client/               # AI client abstractions
│   │       ├── ImageGeneratorInterface.php
│   │       ├── ImageResult.php
│   │       ├── ComfyUIClient.php     # FLUX.1 Dev via ComfyUI
│   │       └── StableDiffusionClient.php
│   ├── bl/                # Business logic
│   │   ├── entity/               # Entity business logic
│   │   │   ├── types/            # EntityType class hierarchy
│   │   │   │   ├── AbstractEntityType.php
│   │   │   │   ├── BuildingEntityType.php
│   │   │   │   ├── TreeEntityType.php
│   │   │   │   ├── TransporterEntityType.php
│   │   │   │   ├── ManipulatorEntityType.php
│   │   │   │   ├── ReliefEntityType.php
│   │   │   │   ├── EyeEntityType.php
│   │   │   │   ├── EntityTypeFactory.php
│   │   │   │   ├── building/     # Individual building classes
│   │   │   │   ├── tree/         # Individual tree classes
│   │   │   │   ├── transporter/  # Conveyor etc.
│   │   │   │   ├── manipulator/  # Manipulator classes
│   │   │   │   ├── relief/       # Rock classes
│   │   │   │   └── eye/          # Crystal tower classes
│   │   │   └── generators/       # AI sprite generators
│   │   │       ├── base/
│   │   │       ├── EntityGeneratorFactory.php
│   │   │       ├── building/
│   │   │       ├── tree/
│   │   │       └── ...
│   │   └── landing/              # Landing business logic
│   │       ├── AbstractLanding.php
│   │       ├── LandingFactory.php
│   │       ├── GrassLanding.php
│   │       ├── DirtLanding.php
│   │       └── ... (10 landing types)
│   ├── commands/          # Console commands
│   ├── controllers/       # Web controllers (thin, use actions())
│   │   ├── GameController.php
│   │   ├── MapController.php
│   │   ├── SiteController.php
│   │   └── UserController.php
│   ├── migrations/        # Database migrations
│   ├── models/            # ActiveRecord models (with polymorphic instantiate())
│   │   ├── User.php              # IdentityInterface
│   │   ├── Entity.php
│   │   ├── EntityType.php        # Uses EntityTypeFactory::instantiate()
│   │   ├── Landing.php           # Uses LandingFactory::instantiate()
│   │   └── Map.php
│   └── views/             # View templates
│       ├── game/index.php        # Game page
│       └── site/index.php        # Landing page
├── composer.json           # PHP dependencies
├── package.json            # NPM dependencies
└── webpack.mix.js          # Asset compilation config
```

## Business Logic Classes

### Polymorphic Models

EntityType and Landing models use Yii2's `instantiate()` method to return specific subclasses based on database IDs. This enables polymorphism and eliminates if-statements.

```php
// Returns specific class (e.g., FurnaceEntityType, GrassLanding)
$entity = EntityType::findOne(FurnaceEntityType::ENTITY_TYPE_ID);
$landing = Landing::findOne(GrassLanding::LANDING_ID);

// Use constants instead of magic numbers
use bl\entity\types\building\FurnaceEntityType;
use bl\landing\GrassLanding;

EntityType::findOne(FurnaceEntityType::ENTITY_TYPE_ID);  // Instead of findOne(101)
Landing::findOne(GrassLanding::LANDING_ID);               // Instead of findOne(1)
```

### EntityType Class Hierarchy

```
bl\entity\types\
├── AbstractEntityType          # Base class with getGenerator(), getSpriteDir()
├── BuildingEntityType          # Buildings (furnace, drill, etc.)
├── TreeEntityType              # Trees
├── TransporterEntityType       # Conveyors
├── ManipulatorEntityType       # Robotic arms
├── ReliefEntityType            # Rocks
├── EyeEntityType               # Crystal towers
├── EntityTypeFactory           # Maps entity_type_id to class
├── building/
│   ├── FurnaceEntityType       # ENTITY_TYPE_ID = 101
│   ├── DrillEntityType         # ENTITY_TYPE_ID = 102
│   ├── AssemblerEntityType     # ENTITY_TYPE_ID = 103
│   └── ... (22 building types)
├── tree/
│   ├── PineTreeEntityType      # ENTITY_TYPE_ID = 1
│   └── ... (8 tree types)
├── transporter/
│   └── ConveyorEntityType      # ENTITY_TYPE_ID = 100
├── manipulator/
│   ├── ShortManipulatorEntityType  # ENTITY_TYPE_ID = 200
│   └── LongManipulatorEntityType   # ENTITY_TYPE_ID = 201
├── relief/
│   ├── SmallRockEntityType     # ENTITY_TYPE_ID = 10
│   └── ... (3 rock sizes)
└── eye/
    ├── SmallCrystalTowerEntityType  # ENTITY_TYPE_ID = 400
    └── ... (3 crystal towers)
```

### Landing Class Hierarchy

```
bl\landing\
├── AbstractLanding             # Base class with getSpriteDir(), isBuildable()
├── LandingFactory              # Maps landing_id to class
├── GrassLanding                # LANDING_ID = 1
├── DirtLanding                 # LANDING_ID = 2
├── SandLanding                 # LANDING_ID = 3
├── WaterLanding                # LANDING_ID = 4
├── StoneLanding                # LANDING_ID = 5
├── LavaLanding                 # LANDING_ID = 6
├── SnowLanding                 # LANDING_ID = 7
├── SwampLanding                # LANDING_ID = 8
├── SkyLanding                  # LANDING_ID = 9
└── IslandEdgeLanding           # LANDING_ID = 10
```

### Entity Generators

Each EntityType class can return its corresponding AI sprite generator:

```php
$entityType = EntityType::findOne(FurnaceEntityType::ENTITY_TYPE_ID);
$generator = $entityType->getGenerator();  // Returns FurnaceGenerator
$generator->generate($entityType);         // Generates sprites via FLUX.1 Dev
```

Generator hierarchy in `bl\entity\generators\`:
- `base\AbstractEntityGenerator` - Base with generate(), generateStates()
- `base\ImageProcessor` - Static image processing utilities
- `EntityGeneratorFactory` - Maps image_url to generator class
- Individual generators: `building\FurnaceGenerator`, `tree\PineTreeGenerator`, etc.

## Standalone Action Classes

All controller actions are implemented as standalone classes extending `yii\base\Action`. This pattern:
- Separates action logic from controller
- Makes actions reusable and testable
- Keeps controllers thin

### Base Classes

| Class | Description |
|-------|-------------|
| `actions\Base` | Base for view-rendering actions |
| `actions\JsonAction` | Base for JSON API endpoints |

### Creating New Action

1. Create action class in `src/actions/{controller}/`:
```php
<?php
namespace actions\game;

use actions\JsonAction;

class MyAction extends JsonAction
{
    public function run()
    {
        // Action logic
        return $this->success(['data' => $value]);
    }
}
```

2. Register in controller's `actions()` method:
```php
public function actions()
{
    return [
        'my-action' => \actions\game\MyAction::class,
    ];
}
```

## Entity Sprite System

Each entity type has a folder with **7 sprite states** + **9 construction frames**:

```
public/assets/tiles/entities/{entity_name}/
├── normal.png              # Default built state (also used as icon)
├── damaged.png             # Durability < 50% max
├── blueprint.png           # Construction outline
├── normal_selected.png     # Normal + hover highlight
├── damaged_selected.png    # Damaged + hover highlight
├── deleting.png            # Delete mode hover (red outline)
├── crafting.png            # Crafting/production animation
└── construction/           # Construction animation frames
    ├── frame_0.png         # 0% - 11% progress
    ├── frame_1.png         # 11% - 22% progress
    ├── ...
    └── frame_8.png         # 89% - 100% progress
```

**Note**: The `normal.png` file serves dual purpose - both as the default sprite and as the 64×64 icon for UI panels.

### Sprite Selection Logic

| state     | durability   | mode   | hover | Sprite Used              |
|-----------|--------------|--------|-------|--------------------------|
| blueprint | —            | —      | —     | construction/frame_X.png |
| built     | ≥ 50% max    | NORMAL | no    | normal.png               |
| built     | ≥ 50% max    | NORMAL | yes   | normal_selected.png      |
| built     | < 50% max    | NORMAL | no    | damaged.png              |
| built     | < 50% max    | NORMAL | yes   | damaged_selected.png     |
| built     | —            | DELETE | yes   | deleting.png             |

**Construction Progress**: Blueprint entities show animated construction frames based on `construction_progress` percentage (0-100%).

## Models

### Landing (terrain types)
- `landing_id` - primary key
- `is_buildable` - enum('yes','no') - can buildings be placed here?
- `folder` - folder name (e.g., 'grass', 'island_edge')

### LandingAdjacency (terrain transitions)
- `adjacency_id` - primary key
- `landing_id_1` - first terrain type
- `landing_id_2` - second terrain type
- Defines which terrains can have smooth transitions

### Map (terrain instances)
- `map_id` - primary key
- `landing_id` - foreign key to landing
- `x`, `y` - tile coordinates

### EntityType (entity definitions)
- `entity_type_id` - primary key
- `type` - enum('building','transporter','manipulator','tree','relief','resource','eye','mining')
- `name` - display name
- `description` - detailed description shown in entity info window
- `image_url` - **folder name** for sprite states
- `extension` - 'png' (file extension for sprites)
- `max_durability` - maximum health points
- `width` - entity width in tiles (default 1)
- `height` - entity height in tiles (default 1)
- `icon_url` - path to icon (typically '{folder}/normal.png')
- `power` - visibility radius for eye type entities
- `construction_ticks` - ticks required to complete construction (60 ticks = 1 second)

### Entity (entity instances)
- `entity_id` - primary key
- `entity_type_id` - foreign key to entity_type
- `state` - enum('built', 'blueprint')
- `durability` - current health (0-max_durability)
- `construction_progress` - construction completion (0-100%), only for blueprints
- `x`, `y` - pixel coordinates

### User (user accounts)
- `user_id` - primary key
- `username` - unique username
- `auth_key` - for "remember me" functionality
- `build_panel` - JSON array of 10 entity_type_id slots

## Camera & Controls

### Movement
- **WASD** or **Arrow keys** - move camera
- Supports both EN and RU keyboard layouts

### Zoom
- **Mouse wheel** - zoom in/out (1x - 3x)
- Zoom is relative to **screen center**

### Camera Origin
- Map starts at **top-left corner** of the screen
- Camera position (0, 0) shows tile (0, 0) at top-left

## Game Modes

The game uses **GameModeManager** to ensure only one mode is active at a time:

| Mode                         | Description                           | Activate                  |
|------------------------------|---------------------------------------|---------------------------|
| NORMAL                       | Default gameplay                      | Esc / close windows       |
| BUILD                        | Building placement                    | 1-0 keys or B window      |
| DELETE                       | Entity deletion                       | Delete key                |
| ENTITY_INFO                  | Entity information window             | Click entity in NORMAL    |
| ENTITY_SELECTION_WINDOW      | Building selection window             | B key                     |
| LANDING_SELECTION_WINDOW     | Landing selection window              | L key                     |
| LANDING_EDIT                 | Landing editing                       | Edit mode activation      |

### Mode-Specific Controls

**NORMAL Mode:**
- **B** - open buildings window
- **L** - open landing window
- **1-9, 0** - activate build panel slot
- **Delete** - enter delete mode
- **Click entity** - open entity info window

**BUILD Mode:**
- **Click** - place building
- **R** - rotate building (if rotatable)
- **Esc** - cancel and return to NORMAL
- Green preview = valid placement
- Red preview = collision detected

**DELETE Mode:**
- **Click entity** - delete entity
- **Delete** or **Esc** - exit delete mode
- Red outline on hover indicates target

**ENTITY_INFO Mode:**
- **Esc** - close window and return to NORMAL

## Build Panel

### Features
- 10-slot hotbar at bottom center
- Drag & drop from building window
- Number keys **1-0** activation
- **Right-click slot** - clear slot
- Auto-saved to database (debounced)

## Asset Versioning

Assets use version query string to bust browser cache:
```javascript
// In game.js
assetUrl(path) {
    const v = this.config.assetVersion || 1;
    return `${path}?v=${v}`;
}
```

Configure in `static_config.php`:
```php
'params' => [
    'asset_version' => 1, // Increment to bust cache
]
```

## Development Commands
```bash
# Install PHP dependencies
composer install

# Install NPM dependencies
npm install

# Compile assets (production)
npm run assets

# Watch assets (development)
npm run assets-watch

# Run migrations
php yii migrate

# Generate AR models
composer run ar

# === Landing sprite generation ===
# Generate AI sprites (requires WebUI running)
php yii landing/generate-ai all      # All landings
php yii landing/generate-ai grass    # Single landing

# Scale original files to 64×64
php yii landing/scale-original

# Generate texture atlases
php yii landing/generate

# Legacy: Generate transitions (deprecated)
php yii landing/generate-transitions
```

## Database Setup
```bash
# Run migrations
php yii migrate

# Load demo data (in order)
mysql zfactory < docs/sql/insert_landing.sql
mysql zfactory < docs/sql/insert_entity_type.sql
mysql zfactory < docs/sql/insert_map_demo.sql
mysql zfactory < docs/sql/insert_entity_demo.sql
mysql zfactory < docs/sql/insert_map_extended.sql
mysql zfactory < docs/sql/insert_entity_extended.sql
mysql zfactory < docs/sql/update_entity_type_folders.sql
```

---

## Documentation

- **[ADMIN.md](ADMIN.md)** - Admin Panel: управление регионами, пользователями, редактор карты
- **[DATABASE.md](DATABASE.md)** - Database Schema: таблицы, поля, связи
- **[GAME_ENGINE.md](GAME_ENGINE.md)** - Game Engine: PixiJS, камера, рендеринг, API
