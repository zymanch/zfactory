# Tile Rendering System Documentation

## Purpose

Manages terrain tile rendering with texture atlases, wavy transitions between terrain types, and landing variations for natural-looking landscapes.

## TileLayerManager

### Location
`resources/js/modules/tileLayerManager.js`

### Purpose
Renders terrain tiles using texture atlases with automatic transitions.

### Key Features

- Texture atlas rendering (sprite batching)
- Wavy transitions between terrain types
- Random landing variations
- Viewport culling
- Island edge auto-generation

## Texture Atlas System

### Atlas Structure

Each landing type has its own atlas: `{name}_atlas.png`

**Dimensions**:
- Width: `11 × 32px = 352px` (all possible landing types 0-10)
- Height: `12 × 24px = 288px` (row 0 for variations + rows 1-11 for transitions)

**Structure**:
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

**Purpose**:
- Creates seamless transitions between Island Edge and Sky
- Prevents visual discontinuities at floating island boundaries
- Applied during atlas generation, not during runtime rendering

### PIXI.Rectangle for Sub-textures

```javascript
const inset = 0.5;  // Prevent texture bleeding
const rect = new PIXI.Rectangle(
    col * 64 + inset,
    row * 64 + inset,
    64 - inset * 2,
    64 - inset * 2
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

## Wavy Transition Algorithm

Transitions between different terrain types use cosine-based wavy lines for natural-looking borders.

### Parameters

- `waveAmplitude = 1` - Wave displacement in pixels
- `waveFrequency = 2.0` - Number of waves across the tile
- `outlineWidth = 1` - Width of darkened border line

### Formula (right edge example)

```php
for ($y = 0; $y < $tileHeight; $y++) {
    $t = $y / ($tileHeight - 1);  // Normalize to 0-1
    $wave = cos($t * 2 * M_PI * $waveFrequency) * $waveAmplitude;
    $wavyX[$y] = (int)round($tileWidth - 1 - $waveAmplitude + $wave);
}
```

### Transition Types

- `generateRightTransition()` - Vertical wavy line on right edge
- `generateTopTransition()` - Horizontal wavy line on top edge
- `generateCornerTransition()` - L-shaped wavy line for both edges

## Landing Variations

Each landing type has 5 pre-generated variations stored in folders for natural-looking terrain.

### File Structure

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

### Initial Generation

Variations were created once using `VariationGenerator.php` with:
- **Color shifts**: ±10 hue, ±5 saturation, ±5 brightness
- **Noise**: 5% of pixels get ±3 RGB variation

### Replacement

You can replace these PNG files with custom high-quality textures. After replacement, regenerate atlases:

```bash
php yii landing/generate
npm run assets
```

### Variation Selection

```javascript
// When rendering tile with no transitions (same neighbors)
const variationIndex = Math.floor(Math.random() * 5);  // 0-4
const textureKey = `landing_atlas_${landingType}`;
const rect = new PIXI.Rectangle(variationIndex * 64, 0, 64, 64);
```

## Rendering Logic

### createTileWithTransitions

```javascript
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

## Generation Commands

### Generate Transitions

```bash
# Generate all transition sprites (66 files)
php yii landing/generate-transitions

# List defined adjacencies
php yii landing/list-adjacencies
```

### Generate Atlases

```bash
# Generate all texture atlases (reads from variation folders)
php yii landing/generate
```

This generates all texture atlases in `public/assets/tiles/landing/atlases/`.

## Transition Sprite Location

```
public/assets/tiles/landing/transitions/
├── grass_dirt_r.jpg      # Grass base, dirt on right
├── grass_dirt_t.jpg      # Grass base, dirt on top
├── grass_dirt_rt.jpg     # Grass base, dirt on both
├── dirt_grass_r.jpg      # Dirt base, grass on right
├── ...
```

## Adjacency Table

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

## Creating High-Quality Sprites

### Recommended Tools

#### AI Generators (Best for Quick Results)

**Midjourney / DALL-E / Stable Diffusion:**
```
Prompt template:
"seamless tileable texture, 64x64 pixel art, [texture type],
top-down view, isometric perspective, game asset,
pixel perfect, no borders, repeatable pattern"

Examples:
- "seamless tileable texture, 64x64 pixel art, grass field,
   top-down view, bright green, game asset"
- "seamless tileable texture, 64x64 pixel art, dirt ground,
   brown earth, top-down view, game tile"
```

**Important**:
- Specify "seamless" and "tileable" for seamless tiling
- "pixel art" gives pixelated style
- "64x64" - exact size (but AI may not comply, requires resize)

#### Pixelart Editors (Manual Work)

**Aseprite** (recommended):
- Professional pixelart editor
- Onion skinning for animations
- Tile mode to check repeatability
- Export to PNG
- Price: $19.99 or free (compile from source)

**Piskel** (free):
- Online editor: https://www.piskelapp.com
- Simple interface
- Tile preview mode
- Export to PNG

### Creation Guidelines

#### General Rules

1. **Size**: Strictly 64×64 pixels (no exceptions!)

2. **Seamless Tiling**:
   - Left edge must match right edge
   - Top edge must match bottom edge
   - Test: copy texture 3×3 - no visible seams

3. **Color Palette**:
   - Use 8-16 colors per texture
   - Avoid gradients (use dithering instead)
   - Contrasting colors for readability

4. **Detail Level**:
   - At 64×64 pixels not much detail fits
   - Use large pixel blocks (2×2, 3×3)
   - Fewer details = better readability

5. **Variations**:
   - Create 5 variations for each type
   - Small differences: shifted grass blades, different rock pattern
   - Same palette for all variations

#### Workflow for AI Generation

1. **Generate in large resolution** (256×192 or larger)
2. **Downscale with nearest neighbor** to 64×64
3. **Check seamlessness**:
   - GIMP: Filters → Map → Tile (creates preview)
   - Photoshop: Filter → Other → Offset (shift 50%)
4. **Fix seams manually** in pixelart editor
5. **Create 5 variations**:
   - Rotate hue (±5-10°)
   - Add noise (+3-5%)
   - Shift pattern (offset 1-2 pixels)

### Testing

```bash
# 1. Replace files in folder
cp my_grass_*.png public/assets/tiles/landing/grass/

# 2. Regenerate atlases
php yii landing/generate

# 3. Compile JS
npm run assets

# 4. Check in browser (Ctrl+F5 to clear cache)
```

## File Locations

- **Manager**: `resources/js/modules/tileLayerManager.js`
- **Atlases**: `public/assets/tiles/landing/atlases/`
- **Variations**: `public/assets/tiles/landing/{name}/`
- **Transitions**: `public/assets/tiles/landing/transitions/`
- **Backend Generator**: `src/commands/LandingController.php`
- **Transition Generator**: `src/bl/landing/LandingTransitionGenerator.php`
