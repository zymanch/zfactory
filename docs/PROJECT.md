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

### AI Sprite Generation
Sprites can be generated using local Stable Diffusion WebUI:
- **Setup**: See `ai/README.md` for installation instructions
- **Model**: Realistic Vision v6.0 (4.59 GB) recommended
- **Features**: Seamless tiling, img2img variations, automatic transparency for island edges

Workflow:
```bash
# 1. Start WebUI (separate terminal)
cd ai
start.bat

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
│   ├── commands/          # Console commands
│   ├── controllers/       # Web controllers (thin, use actions())
│   │   ├── GameController.php
│   │   ├── MapController.php
│   │   ├── SiteController.php
│   │   └── UserController.php
│   ├── migrations/        # Database migrations
│   ├── models/            # ActiveRecord models
│   │   ├── User.php              # IdentityInterface
│   │   ├── Entity.php
│   │   ├── EntityType.php
│   │   ├── Landing.php
│   │   └── Map.php
│   └── views/             # View templates
│       ├── game/index.php        # Game page
│       └── site/index.php        # Landing page
├── composer.json           # PHP dependencies
├── package.json            # NPM dependencies
└── webpack.mix.js          # Asset compilation config
```

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

# Scale original files to 32×24
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
