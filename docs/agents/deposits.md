# Deposit System Documentation

## Purpose

Manages rendering of natural resources (trees, rocks, ores) on the map that can be extracted by mining buildings.

## DepositLayerManager

### Location
`resources/js/modules/depositLayerManager.js`

### Purpose
Manages rendering of natural resources (trees, rocks, ores).

### Features

- **Single sprite**: Only `normal.png` (no damaged/selected states)
- **Z-index**: 1.5 (between landing and entity layers)
- **Tile coordinates**: Uses same coordinate system as map/entity
- **Viewport loading**: Loads deposits visible in current camera view
- **Auto-removal**: Deposits removed when extraction buildings placed
- **Simplified tooltip**: Shows resource name, icon, amount

### Key Methods

- `loadDeposits(deposits)` - load initial deposits from config
- `addDeposit(depositData)` - add single deposit sprite
- `removeDeposits(depositIds)` - remove multiple deposits
- `getDepositsInArea(tileX, tileY, width, height)` - find deposits in rectangle

## DepositTooltip

### Location
`resources/js/modules/depositTooltip.js`

### Purpose
Simplified tooltip for deposits on hover.

### Features

- Resource name and deposit type
- Resource icon and amount
- No durability or construction progress

## Database Schema

### Deposit Types Table

```sql
CREATE TABLE deposit_type (
    deposit_type_id INT UNSIGNED PRIMARY KEY,
    type VARCHAR(20),              -- 'tree', 'ore', 'rock'
    name VARCHAR(100),
    image_url VARCHAR(255),        -- Sprite filename
    resource_id INT UNSIGNED,      -- Resource extracted
    resource_amount INT UNSIGNED,  -- Amount per deposit
    INDEX idx_type (type)
);
```

### Deposits Table

```sql
CREATE TABLE deposit (
    deposit_id INT UNSIGNED PRIMARY KEY,
    deposit_type_id INT UNSIGNED,
    region_id INT UNSIGNED,
    x INT,                         -- Tile X coordinate
    y INT,                         -- Tile Y coordinate
    resource_amount INT UNSIGNED,  -- Remaining resource
    INDEX idx_region (region_id),
    INDEX idx_position (x, y)
);
```

### Example Deposit Types

| ID | Type | Name     | Resource ID | Amount | Image |
|----|------|----------|-------------|--------|-------|
| 1  | tree | Pine Tree | 1 (Wood)   | 100    | tree_pine.png |
| 2  | tree | Oak Tree  | 1 (Wood)   | 150    | tree_oak.png |
| 10 | ore  | Iron Ore  | 10 (Iron)  | 200    | ore_iron.png |
| 11 | ore  | Coal      | 11 (Coal)  | 150    | ore_coal.png |
| 13 | ore  | Oil Well  | 401 (Oil)  | 10000  | oil_well.png |
| 14 | ore  | Gas Vent  | 402 (Gas)  | 10000  | gas_vent.png |

## Sprite Structure

### File Location

```
public/assets/tiles/deposits/
├── tree_pine.png      (64×64)
├── tree_oak.png       (64×64)
├── ore_iron.png       (64×64)
├── ore_coal.png       (64×64)
├── oil_well.png       (64×64)
└── gas_vent.png       (64×64)
```

**Important**: Only one sprite per deposit type (no states).

## Integration

### Initialization

```javascript
// In game.js
this.depositLayerManager = new DepositLayerManager(this);

// Load deposits from config
this.depositLayerManager.loadDeposits(configData.deposits);
```

### Placement Validation

When placing mining building:

```javascript
// In buildMode.js
validatePlacement(entityTypeId, tileX, tileY) {
    const entityType = this.game.entityTypes[entityTypeId];

    // Check if mining type requires deposit
    if (entityType.type === 'mining') {
        const deposits = this.game.depositLayerManager.getDepositsInArea(
            tileX, tileY,
            entityType.width, entityType.height
        );

        if (deposits.length === 0) {
            return { allowed: false, error: 'No deposit found' };
        }

        // Check deposit type matches requirement
        const requiredDepositTypes = entityType.required_deposit_types || [];
        const hasMatchingDeposit = deposits.some(d =>
            requiredDepositTypes.includes(d.deposit_type_id)
        );

        if (!hasMatchingDeposit) {
            return { allowed: false, error: 'Wrong deposit type' };
        }
    }

    return { allowed: true };
}
```

### Auto-Removal

When mining building placed on deposit:

```javascript
// Backend: CreateEntity action
if ($entityType->type === 'mining') {
    // Find deposits under building footprint
    $deposits = Deposit::find()
        ->where(['region_id' => $regionId])
        ->andWhere(['between', 'x', $x, $x + $width - 1])
        ->andWhere(['between', 'y', $y, $y + $height - 1])
        ->all();

    // Remove deposits
    $depositIds = [];
    foreach ($deposits as $deposit) {
        $depositIds[] = $deposit->deposit_id;
        $deposit->delete();
    }

    // Return removed deposit IDs to client
    return [
        'result' => 'ok',
        'entity' => $entityData,
        'removedDeposits' => $depositIds
    ];
}
```

```javascript
// Frontend: After entity created
if (response.removedDeposits && response.removedDeposits.length > 0) {
    this.game.depositLayerManager.removeDeposits(response.removedDeposits);
}
```

## Rendering

### Sprite Creation

```javascript
addDeposit(depositData) {
    const depositType = this.game.depositTypes[depositData.deposit_type_id];
    const textureKey = `deposit_${depositData.deposit_type_id}`;

    const sprite = this.game.graphics.createSprite(textureKey);
    sprite.x = depositData.x * 64;
    sprite.y = depositData.y * 64;
    sprite.zIndex = 1.5;

    // Store base position for shake animation
    sprite.baseX = depositData.x * 64;
    sprite.baseY = depositData.y * 64;

    // Enable hover for tooltip
    sprite.eventMode = 'static';
    sprite.on('pointerover', () => this.showTooltip(depositData));
    sprite.on('pointerout', () => this.hideTooltip());

    this.depositLayer.addChild(sprite);
    this.depositSprites.set(depositData.deposit_id, sprite);
}
```

### Tooltip Display

```javascript
showTooltip(depositData) {
    const depositType = this.game.depositTypes[depositData.deposit_type_id];
    const resource = this.game.resources[depositType.resource_id];

    this.tooltip.show({
        title: depositType.name,
        resource: {
            id: resource.resource_id,
            name: resource.name,
            amount: depositData.resource_amount
        }
    });
}
```

## Workflows

### Adding New Deposit Type

1. Create sprite (64×64 PNG) in `public/assets/tiles/deposits/`
2. Add to database:
```sql
INSERT INTO deposit_type (deposit_type_id, type, name, image_url, resource_id, resource_amount)
VALUES (20, 'ore', 'Copper Ore', 'ore_copper.png', 20, 200);
```
3. Place deposits on map:
```sql
INSERT INTO deposit (deposit_type_id, region_id, x, y, resource_amount)
VALUES (20, 1, 50, 30, 200);
```
4. Add to asset manifest in backend
5. Reload game

### Configuring Mining Building

Set required deposit types in entity_type:

```sql
UPDATE entity_type
SET required_deposit_types = '[13, 14]'  -- JSON array of deposit_type_ids
WHERE entity_type_id = 146;  -- Oil Pump
```

## Performance

- **Viewport culling**: Only load deposits in visible area
- **Lazy loading**: Deposits loaded on demand during map load
- **Sprite pooling**: Reuse sprites when removing/adding
- **Simple rendering**: Only one sprite state per deposit

## File Locations

- **Manager**: `resources/js/modules/depositLayerManager.js`
- **Tooltip**: `resources/js/modules/depositTooltip.js`
- **Sprites**: `public/assets/tiles/deposits/`
- **Backend**: `models/Deposit.php`, `models/DepositType.php`
