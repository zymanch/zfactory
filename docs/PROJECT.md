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
- **Base tile size**: 32px (width) x 24px (height)
- All game elements must be multiples of this base size
- Entities can span multiple tiles (e.g., 64x48 = 2x2 tiles)

### Two-Layer Architecture
1. **Background Layer (landing)** - terrain tiles (grass, water, stone, etc.)
   - Always 32x24 px
   - Stored in `landing` table (types) and `map` table (instances)

2. **Entity Layer (entity)** - buildings, conveyors, trees, etc.
   - Can be larger: width = N*32px, height = M*24px
   - Stored in `entity_type` table (types) and `entity` table (instances)

### Map Size
- **Max bounds**: 100x75 tiles (3200x1800 pixels)
- **Actual tiles**: ~6251 (floating island shape)
- **Style**: Irregular edges, internal holes, sky background

### Landing Variations & Texture Atlases
Terrain uses texture atlases with 5 procedural variations per landing type:
- **Texture Atlases**: 352×288px (11 columns × 12 rows) containing all transition combinations
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

# 3. Scale to 32×24 and create variations
php yii landing/scale-original

# 4. Generate texture atlases
php yii landing/generate

# 5. Compile assets
npm run assets
```

## Project Structure
```
zfactory.local/
├── docs/                   # Documentation
│   └── sql/               # SQL scripts for data
├── public/                 # Web root
│   ├── assets/tiles/      # Game sprites
│   │   ├── landing/       # Terrain tiles (*.svg)
│   │   └── entities/      # Entity sprite folders
│   │       ├── conveyor/  # 5 state files each
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
│           ├── camera.js
│           ├── inputManager.js
│           ├── buildPanel.js
│           ├── buildingWindow.js
│           └── buildMode.js
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

Each entity type has a folder with 5 sprite states + 1 icon:

```
public/assets/tiles/entities/{entity_name}/
├── normal.svg           # Default built state
├── damaged.svg          # Durability < 50% max
├── blueprint.svg        # Construction outline
├── normal_selected.svg  # Normal + hover highlight
├── damaged_selected.svg # Damaged + hover highlight
└── icon.svg             # 32x24 icon for UI panels
```

### Sprite Selection Logic

| state     | durability   | hover | Sprite Used         |
|-----------|--------------|-------|---------------------|
| blueprint | —            | —     | blueprint.svg       |
| built     | ≥ 50% max    | no    | normal.svg          |
| built     | ≥ 50% max    | yes   | normal_selected.svg |
| built     | < 50% max    | no    | damaged.svg         |
| built     | < 50% max    | yes   | damaged_selected.svg|

**Note:** Blueprint state does not respond to hover.

## Models

### Landing (terrain types)
- `landing_id` - primary key
- `is_buildable` - enum('yes','no') - can buildings be placed here?
- `image_url` - path to tile image

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
- `image_url` - **folder name** for sprite states
- `max_durability` - maximum health points
- `width` - entity width in tiles (default 1)
- `height` - entity height in tiles (default 1)
- `icon_url` - 32x24 icon for UI panels
- `power` - visibility radius for eye type entities

### Entity (entity instances)
- `entity_id` - primary key
- `entity_type_id` - foreign key to entity_type
- `state` - enum('built', 'blueprint')
- `durability` - current health (0-max_durability)
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

## Build Panel & Building Mode

### Controls
- **B** - open building window
- **1-9, 0** - activate build panel slot
- **Esc** - cancel building mode
- **Right-click slot** - clear slot

### Building Placement
- Click on map to place building
- Green preview = valid placement
- Red preview = collision detected
- Multi-tile entities check all occupied tiles

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
