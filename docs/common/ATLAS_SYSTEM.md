# Atlas Generation System

## Overview

The atlas generation system creates texture atlases for entity sprites using the **Provider Pattern** to eliminate hardcoded entity_type_id dependencies and provide a unified workflow.

## Quick Start

### For New Entity Types

#### 1. Prepare Sprites

```bash
# Create folder for entity
mkdir public/assets/tiles/entities/{type}/{folder}

# Place base sprite:
# - Buildings: sprite.png (W×H tiles, 64px per tile)
# - Conveyors: animation.png (8 frames side-by-side, 64×64 each)
# - Pipes: sprite.png (64×64)
```

#### 2. Add to Database

```sql
INSERT INTO entity_type (
    entity_type_id, type, subtype, name, image_url,
    extension, max_durability, width, height, ...
)
VALUES (
    150, 'conveyor', 'splitter', 'Y-Splitter', 'splitter',
    'png', 100, 1, 1, ...
);
```

#### 3. Register Provider (if new subtype)

If this is a new subtype, create a provider in `src/bl/entity/atlas_providers/{type}/{Subtype}AtlasProvider.php` and register it in `AtlasProviderRegistry::init()`.

See "Atlas Providers" section below for details.

#### 4. Generate Atlases

```bash
# Generate atlases for specific entity
php yii atlas/generate --entity_type_id=150

# Or generate all atlases
php yii atlas/generate-all
```

### For Existing Entity Types

```bash
# One-time migration: copy normal.png → sprite.png
php yii entity/migrate-sprites

# Generate all atlases
php yii atlas/generate-all
```

## Architecture

### Key Components

```
sprite_generators/     - Generate base sprites (sprite.png, animation.png)
atlas_providers/       - Define which atlases are needed for each type
atlas_generators/      - Generate atlases from sprites
```

### 1. Sprite Generators

**Location**: `src/bl/entity/sprite_generators/`

**Purpose**: Create base sprites (sprite.png, animation.png) from source files.

**Interface**:
```php
interface SpriteGeneratorInterface {
    public function generate(EntityType $entityType, bool $testMode = false): bool;
    public function generateStates(EntityType $entityType): bool;
    public function supports(EntityType $entityType): bool;
}
```

**Examples**:
- `ConveyorSpriteGenerator` - creates animation.png for conveyors
- `PipeSpriteGenerator` - creates sprite.png for pipes
- `FurnaceSpriteGenerator` - creates sprite.png for furnaces

**Important**: Sprite generators work ONLY with files (load/save). All processing is delegated to ImageProcessor.

### 2. Image Processor

**Location**: `src/bl/entity/sprite_generators/base/ImageProcessor.php`

**Purpose**: Image transformations (damaged, blueprint, selected, rotation).

**Principle**: All methods work with **GD resources**, NOT files.

**Key Methods**:
```php
public static function removeBackground($img, int $brightnessThreshold = 200): resource;
public static function scaleImage($img, int $targetWidth, int $targetHeight): resource;
public static function createDamaged($src): resource;
public static function createBlueprint($src, ?string $orientation = null): resource;
public static function createSelected($src): resource;
public static function rotateImage($src, int $angle): resource;
```

**Caller is responsible for**:
- Loading files (`imagecreatefrompng()`)
- Saving results (`imagepng()`)
- Freeing memory (`imagedestroy()`)

### 3. Atlas Providers

**Location**: `src/bl/entity/atlas_providers/`

**Purpose**: Define which atlases are needed for specific entity_type.

**Interface**:
```php
interface AtlasProviderInterface {
    public function getAtlasGenerators(EntityType $entityType): array;
    public function getSourceSprite(EntityType $entityType);
}
```

**Registration**: `AtlasProviderRegistry`
```php
AtlasProviderRegistry::init();
AtlasProviderRegistry::register('building', null, BuildingAtlasProvider::class);
AtlasProviderRegistry::register('conveyor', 'conveyor', ConveyorAtlasProvider::class);
AtlasProviderRegistry::register('pipe', 'pipe', PipeAtlasProvider::class);

$provider = AtlasProviderRegistry::getProvider($entityType);
```

**Built-in Providers**:

| Type | Subtype | Provider | Atlases |
|------|---------|----------|---------|
| building | none | BuildingAtlasProvider | atlas.png (7×2) |
| conveyor | conveyor | ConveyorAtlasProvider | 5× atlas (16×8 each) |
| pipe | pipe | PipeAtlasProvider | 4× atlas (16×1 each) |
| manipulator | short/long | BuildingAtlasProvider | atlas.png (7×2) |
| mining | none | BuildingAtlasProvider | atlas.png (7×2) |
| storage | none | BuildingAtlasProvider | atlas.png (7×2) |
| eye | none | BuildingAtlasProvider | atlas.png (7×2) |

**Default**: If provider not found, uses `BuildingAtlasProvider`.

#### BuildingAtlasProvider
One atlas with 2 rows:
- Row 1: 7 states (normal, damaged, blueprint, normal_selected, damaged_selected, deleting, crafting)
- Row 2: 9 construction frames (10%, 20%, ..., 90%)

#### ConveyorAtlasProvider
5 atlases for different states (each 16×8):
- normal_atlas.png
- damaged_atlas.png
- blueprint_atlas.png
- normal_selected_atlas.png
- damaged_selected_atlas.png

#### PipeAtlasProvider
4 atlases for different states (each 16×1):
- pipe_atlas_normal.png
- pipe_atlas_normal_selected.png
- pipe_atlas_damaged.png
- pipe_atlas_damaged_selected.png

### 4. Atlas Generators

**Location**: `src/bl/entity/atlas_generators/`

**Purpose**: Create atlas from GD resource sprite.

**Interface**:
```php
interface AtlasGeneratorInterface {
    public function generate(); // Returns GD resource
    public function getDimensions(): array;
}
```

**Important**: Atlas generators work ONLY with **GD resources** (NOT files).

**Examples**:

#### EntityAtlasGenerator
Creates 2-row atlas for buildings:
- Input: `sprite.png` (GD resource)
- Output: atlas (GD resource)
- Size: `(max(7, 9) * width) × (2 * height)`

#### ConveyorAtlasGenerator
Creates atlas with connection variants (16) and animation frames (8):
- Input: `animation.png` (GD resource, 8 frames side-by-side)
- Output: atlas (GD resource)
- Size: `(16 * 64) × (8 * 64)` = 1024×512

#### PipeAtlasGenerator
Creates atlas with connection variants (16):
- Input: `sprite.png` (GD resource)
- Output: atlas (GD resource)
- Size: `(16 * 64) × 64` = 1024×64

## Entity Type Subtype Column

To eliminate hardcoded entity_type_id, a `subtype` column was added:

```sql
ALTER TABLE entity_type
ADD COLUMN subtype VARCHAR(64) NULL DEFAULT NULL AFTER type;
```

### Type Mapping

| type | subtype | Примеры ID | Описание |
|------|---------|------------|----------|
| conveyor | conveyor | 100, 120-122 | Обычный конвейер |
| conveyor | underground_in | - | Вход подземного конвейера |
| conveyor | underground_out | - | Выход подземного конвейера |
| conveyor | splitter | - | Y-разветвитель |
| pipe | pipe | 131-132 | Обычная труба |
| pipe | underground_pipe | - | Подземная труба |
| electricity | pylon | 900-902 | Электрические столбы |
| electricity | battery | 910-912 | Батареи |
| electricity | generator | 920-922 | Генераторы |
| manipulator | short | 200, 210-212 | Короткий манипулятор |
| manipulator | long | 201, 213-215 | Длинный манипулятор |
| building | none | 101, 103-107 | Здания |
| mining | none | 102, 108, 500-512 | Добывающие |
| storage | none | 104 | Хранилища |
| eye | none | 400-402 | Башни обзора |

**Benefits**:
- No hardcoded entity_type_id in code
- Providers registered by `type:subtype`
- Easy to add new subtypes

## Commands

### Sprite Migration

Copy `normal.png` → `sprite.png` for all entity types:

```bash
php yii entity/migrate-sprites
```

**Logic**:
- Skips rotational variants (parent_entity_type_id != NULL)
- Skips if sprite.png already exists
- Copies only base entities

### Atlas Generation

Generate all atlases for all entity types:

```bash
php yii atlas/generate-all
```

Generate atlases for specific entity:

```bash
php yii atlas/generate --entity_type_id=100
```

**Logic**:
1. Initialize AtlasProviderRegistry
2. For each entity_type:
   - Get provider through registry
   - Get generator list from provider
   - Generate each atlas
   - Save to entity folder

## File Structure

### New Structure (after migration)

```
public/assets/tiles/entities/
├── conveyor/
│   ├── sprite.png              (base sprite, 64×64)
│   ├── animation.png           (8 frames, 512×64)
│   ├── normal_atlas.png        (generated, 1024×512)
│   ├── damaged_atlas.png       (generated, 1024×512)
│   ├── blueprint_atlas.png     (generated, 1024×512)
│   ├── normal_selected_atlas.png
│   └── damaged_selected_atlas.png
├── building/furnace/
│   ├── sprite.png              (base sprite, 128×128)
│   └── atlas.png               (generated, 7×128 × 2×128)
└── pipe/
    ├── sprite.png              (base sprite, 64×64)
    ├── pipe_atlas_normal.png   (generated, 1024×64)
    ├── pipe_atlas_damaged.png
    ├── pipe_atlas_normal_selected.png
    └── pipe_atlas_damaged_selected.png
```

### Old Structure (deprecated)

```
public/assets/tiles/entities/
├── conveyor/
│   ├── normal.png          → DEPRECATED (use sprite.png)
│   ├── damaged.png         → DEPRECATED (generated via atlas)
│   ├── blueprint.png       → DEPRECATED (generated via atlas)
│   └── ...
```

## Workflow: Adding New Entity Type

### 1. Create Sprite

```bash
# Create folder
mkdir public/assets/tiles/entities/{type}/{folder}

# Place base sprite
# - For buildings: sprite.png
# - For conveyors: sprite.png + animation.png (8 frames)
# - For pipes: sprite.png
```

### 2. Add to Database

```sql
INSERT INTO entity_type (entity_type_id, type, subtype, name, image_url, ...)
VALUES (150, 'conveyor', 'underground_in', 'Underground Belt In', 'underground_in', ...);
```

### 3. Create Provider (if new subtype)

```php
// src/bl/entity/atlas_providers/conveyor/UndergroundAtlasProvider.php
class UndergroundAtlasProvider extends AbstractAtlasProvider
{
    public function getAtlasGenerators(EntityType $entityType): array
    {
        // ... define which atlases are needed
    }
}

// Register in AtlasProviderRegistry::init()
self::register('conveyor', 'underground_in', UndergroundAtlasProvider::class);
```

### 4. Generate Atlases

```bash
php yii atlas/generate --entity_type_id=150
```

## Testing

### Check Syntax

```bash
php -l src/bl/entity/atlas_providers/AtlasProviderRegistry.php
php -l src/bl/entity/atlas_generators/EntityAtlasGenerator.php
php -l src/commands/AtlasController.php
```

### Check Generation

```bash
# Generate all atlases
php yii atlas/generate-all

# Verify results
find public/assets/tiles/entities -name "*atlas*.png"
```

### Check Provider

```php
use bl\entity\atlas_providers\AtlasProviderRegistry;
use models\EntityType;

AtlasProviderRegistry::init();

$entityType = EntityType::findOne(100); // Conveyor
$provider = AtlasProviderRegistry::getProvider($entityType);
$generators = $provider->getAtlasGenerators($entityType);

foreach ($generators as $name => $generator) {
    echo "$name: " . json_encode($generator->getDimensions()) . "\n";
}
```

## Common Issues

### "No atlas provider registered for {type}:{subtype}"

**Solution**: Register provider in `AtlasProviderRegistry::init()`

### "Sprite not found for entity_type_id=X"

**Solution**:
1. Check that `sprite.png` or `animation.png` exists in entity folder
2. For rotational variants check that parent entity has sprite
3. Run `php yii entity/migrate-sprites` if using old structure

### Atlas not generating

**Solution**:
1. Check provider syntax: `php -l ...`
2. Check that provider is registered
3. Check write permissions in `public/assets/tiles/entities/`
4. Check logs: `runtime/logs/app.log`

## FAQ

### Q: Why doesn't ImageProcessor work with files?

**A**: Separation of concerns. ImageProcessor is a low-level utility for GD resource transformations. File I/O is delegated to caller (provider/generator), which allows:
- Working in memory without intermediate files
- Easy testing (mock GD resources)
- Reuse in different contexts

### Q: Why are Provider and Generator separate?

**A**: Different abstraction levels:
- **Provider**: "Which atlases are needed for this type?" (high-level logic)
- **Generator**: "How to create specific atlas from sprite?" (low-level algorithm)

Provider can create multiple generators for different states. Generator is a reusable algorithm.

### Q: How to add new state (e.g., 'frozen')?

**A**:
1. Add method in ImageProcessor:
   ```php
   public static function createFrozen($src): resource {
       // ... apply blue tint
   }
   ```

2. Update EntityAtlasGenerator::generateStateSprite():
   ```php
   case 'frozen':
       return ImageProcessor::createFrozen($this->spriteGd);
   ```

3. Add 'frozen' to $states array

4. Regenerate atlases

### Q: Why does ConveyorAtlasGenerator create 16 variants?

**A**: 16 variants = 4-bit connection mask (up, down, left, right):
- Variant 0 (0000): no connections
- Variant 1 (0001): right
- Variant 2 (0010): down
- Variant 3 (0011): right + down
- ...
- Variant 15 (1111): all 4 sides

Each variant is a visual combination of corners/rotations.

## Changelog

### v2.0 (Jan 2026) - Provider Pattern
- ✅ Added `subtype` column to entity_type
- ✅ Created provider/generator architecture
- ✅ Refactored ImageProcessor (GD resources)
- ✅ Renamed `generators` → `sprite_generators`
- ✅ Created migration and generation commands

### v1.0 (Legacy) - Hardcoded IDs
- ❌ entity_type_id hardcoded in controllers
- ❌ Generation logic in controllers (ConveyorController, PipeController)
- ❌ ImageProcessor worked with files
- ❌ No unified interface
