# Ship Building System - Детальный План Реализации

## Общая концепция

**Суть**: Игрок строит корабль рядом с островом. Entity пола корабля при завершении строительства (durability = max) превращается в landing. Данные корабля хранятся отдельно (ship_landing, ship_entity) с привязкой к user_id. Backend объединяет данные острова и корабля, смещая координаты корабля на ship_attach_x/y.

**Ключевые изменения**:
- Sky landing_id: 9 → 11 (последний в списке островных)
- Мост landing_id: 9 (будущая постройка на острове)
- Ship Edge landing_id: 12 (аналог Island Edge для корабля)
- 5 типов entity/landing пола корабля
- Отдельные таблицы для корабля (ship_landing, ship_entity)
- Два типа атласов: island (sky + island) и ship (sky + ship)

---

## Фаза 1: Подготовка констант и конфигов

### 1.1. Вынести LANDING_SKY_ID в конфиги

**Backend (static_config.php)**:
```php
'params' => [
    // ... existing params
    'landing_sky_id' => 11,  // Изменено с 9 на 11
    'landing_bridge_id' => 9,
    'landing_island_edge_id' => 10,
    'landing_ship_edge_id' => 12,
],
```

**Backend использование**:
```php
// Вместо хардкода константы использовать:
Yii::$app->params['landing_sky_id']
```

**Frontend (game/config API)**:
- Добавить в `Config.php` response:
  ```php
  'landingSkyId' => Yii::$app->params['landing_sky_id'],
  'landingBridgeId' => Yii::$app->params['landing_bridge_id'],
  'landingIslandEdgeId' => Yii::$app->params['landing_island_edge_id'],
  'landingShipEdgeId' => Yii::$app->params['landing_ship_edge_id'],
  ```

**Frontend использование**:
```javascript
// В game.js после loadConfig():
this.LANDING_SKY_ID = this.config.landingSkyId;
this.LANDING_BRIDGE_ID = this.config.landingBridgeId;
this.LANDING_ISLAND_EDGE_ID = this.config.landingIslandEdgeId;
this.LANDING_SHIP_EDGE_ID = this.config.landingShipEdgeId;
```

**Файлы для изменения** (заменить хардкод на константу):
- `resources/js/modules/constants.js` - обновить LANDING_SKY_ID
- `resources/js/modules/tileLayerManager.js` - использовать `this.game.LANDING_SKY_ID`
- `src/actions/map/Tiles.php` - использовать `Yii::$app->params['landing_sky_id']`
- Все другие файлы с хардкодом `landing_id === 9`

**Время**: 30 мин

---

## Фаза 2: База данных

### 2.1. Обновить таблицу landing

**Миграция**: `m251230_XXXXXX_update_landing_for_ships.php`

```php
// Добавить колонку type
$this->addColumn('landing', 'type',
    $this->enum(['sky', 'island', 'ship', 'bridge'])->notNull()->defaultValue('island')->after('landing_id')
);

// Обновить существующие записи
$this->update('landing', ['type' => 'sky'], ['landing_id' => 11]); // Sky
$this->update('landing', ['type' => 'island'], ['landing_id' => [1,2,3,4,5,6,7,8,10]]); // Island types + Island Edge
```

**Время**: 15 мин

### 2.2. Создать новые landing записи

**В той же миграции**:

```php
// Bridge landing (landing_id=9)
$this->insert('landing', [
    'landing_id' => 9,
    'type' => 'bridge',
    'name' => 'Мост',
    'folder' => 'bridge',
    'variations_count' => 1,
]);

// Ship Edge landing (landing_id=12)
$this->insert('landing', [
    'landing_id' => 12,
    'type' => 'ship',
    'name' => 'Ship Edge',
    'folder' => 'ship_edge',
    'variations_count' => 1,
]);

// 5 типов пола корабля (landing_id=13-17)
$shipFloorTypes = [
    ['id' => 13, 'name' => 'Деревянный пол корабля', 'folder' => 'ship_floor_wood'],
    ['id' => 14, 'name' => 'Железный пол корабля', 'folder' => 'ship_floor_iron'],
    ['id' => 15, 'name' => 'Стальной пол корабля', 'folder' => 'ship_floor_steel'],
    ['id' => 16, 'name' => 'Титановый пол корабля', 'folder' => 'ship_floor_titanium'],
    ['id' => 17, 'name' => 'Кристаллический пол корабля', 'folder' => 'ship_floor_crystal'],
];

foreach ($shipFloorTypes as $floor) {
    $this->insert('landing', [
        'landing_id' => $floor['id'],
        'type' => 'ship',
        'name' => $floor['name'],
        'folder' => $floor['folder'],
        'variations_count' => 1,
    ]);
}
```

**Время**: 20 мин

### 2.3. Создать entity_type для полов корабля

**Миграция**: `m251230_XXXXXX_create_ship_floor_entity_types.php`

```php
$shipFloorEntities = [
    [
        'name' => 'Деревянный пол корабля',
        'folder' => 'ship_floor_wood',
        'width' => 1,
        'height' => 1,
        'category' => 'ship',
        'max_durability' => 100,
        'converts_to_landing_id' => 13, // NEW FIELD!
    ],
    // ... 4 других типа (id 14-17)
];

foreach ($shipFloorEntities as $entity) {
    $this->insert('entity_type', $entity);
}
```

**Добавить поле в entity_type**:
```php
$this->addColumn('entity_type', 'converts_to_landing_id',
    $this->integer()->unsigned()->null()->after('max_durability')
);
$this->addForeignKey('fk_entity_type_converts_landing', 'entity_type',
    'converts_to_landing_id', 'landing', 'landing_id', 'SET NULL', 'CASCADE'
);
```

**Время**: 30 мин

### 2.4. Создать таблицы ship_landing и ship_entity

**Миграция**: `m251230_XXXXXX_create_ship_tables.php`

```php
// ship_landing (аналог map)
$this->createTable('ship_landing', [
    'ship_landing_id' => $this->primaryKey()->unsigned(),
    'user_id' => $this->integer()->unsigned()->notNull(),
    'landing_id' => $this->integer()->unsigned()->notNull(),
    'x' => $this->integer()->notNull(),
    'y' => $this->integer()->notNull(),
    'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
]);

$this->createIndex('idx_ship_landing_user', 'ship_landing', 'user_id');
$this->createIndex('idx_ship_landing_coords', 'ship_landing', ['user_id', 'x', 'y'], true); // UNIQUE
$this->addForeignKey('fk_ship_landing_user', 'ship_landing', 'user_id', 'user', 'user_id', 'CASCADE');
$this->addForeignKey('fk_ship_landing_landing', 'ship_landing', 'landing_id', 'landing', 'landing_id', 'CASCADE');

// ship_entity (аналог entity)
$this->createTable('ship_entity', [
    'ship_entity_id' => $this->primaryKey()->unsigned(),
    'user_id' => $this->integer()->unsigned()->notNull(),
    'entity_type_id' => $this->integer()->unsigned()->notNull(),
    'x' => $this->integer()->notNull(),
    'y' => $this->integer()->notNull(),
    'state' => $this->enum(['blueprint', 'built'])->notNull()->defaultValue('blueprint'),
    'durability' => $this->integer()->unsigned()->notNull()->defaultValue(0),
    'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
]);

$this->createIndex('idx_ship_entity_user', 'ship_entity', 'user_id');
$this->createIndex('idx_ship_entity_coords', 'ship_entity', ['user_id', 'x', 'y']);
$this->addForeignKey('fk_ship_entity_user', 'ship_entity', 'user_id', 'user', 'user_id', 'CASCADE');
$this->addForeignKey('fk_ship_entity_type', 'ship_entity', 'entity_type_id', 'entity_type', 'entity_type_id', 'CASCADE');
```

**Время**: 25 мин

### 2.5. Добавить поля в region

**Миграция**: `m251230_XXXXXX_add_ship_attach_to_region.php`

```php
$this->addColumn('region', 'ship_attach_x', $this->integer()->notNull()->defaultValue(0)->after('height'));
$this->addColumn('region', 'ship_attach_y', $this->integer()->notNull()->defaultValue(0)->after('ship_attach_x'));

// Для региона 1 (Starting Island) устанавливаем координаты присоединения корабля
// Например, слева от острова
$this->update('region', [
    'ship_attach_x' => -50,  // Слева от острова
    'ship_attach_y' => 35,   // Примерно посередине по высоте
], ['region_id' => 1]);
```

**Время**: 15 мин

**Итого Фаза 2**: ~2 часа

---

## Фаза 3: Модели Backend

### 3.1. Создать модели ShipLanding и ShipEntity

**Файл**: `src/models/ShipLanding.php`

```php
namespace models;

use yii\db\ActiveRecord;

class ShipLanding extends ActiveRecord
{
    public static function tableName()
    {
        return 'ship_landing';
    }

    public function rules()
    {
        return [
            [['user_id', 'landing_id', 'x', 'y'], 'required'],
            [['user_id', 'landing_id'], 'integer'],
            [['x', 'y'], 'integer'],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['user_id' => 'user_id']);
    }

    public function getLanding()
    {
        return $this->hasOne(Landing::class, ['landing_id' => 'landing_id']);
    }
}
```

**Файл**: `src/models/ShipEntity.php`

```php
namespace models;

use yii\db\ActiveRecord;

class ShipEntity extends ActiveRecord
{
    public static function tableName()
    {
        return 'ship_entity';
    }

    public function rules()
    {
        return [
            [['user_id', 'entity_type_id', 'x', 'y'], 'required'],
            [['user_id', 'entity_type_id', 'durability'], 'integer'],
            [['x', 'y'], 'integer'],
            [['state'], 'string'],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['user_id' => 'user_id']);
    }

    public function getEntityType()
    {
        return $this->hasOne(EntityType::class, ['entity_type_id' => 'entity_type_id']);
    }
}
```

**Время**: 30 мин

---

## Фаза 4: Генерация спрайтов

### 4.1. Ship Edge спрайт

**Файл**: `public/assets/tiles/landings/ship_edge/normal.png`

**Описание**:
- Размер: 64x64px
- Полностью прозрачный (alpha=0)
- Сверху на 1/3 высоты (21px): темно-серый прямоугольник `rgba(40, 40, 40, 1.0)` на всю ширину

**Генерация**: Можно использовать PHP GD или ImageMagick:
```php
$img = imagecreatetruecolor(64, 64);
imagesavealpha($img, true);
$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);

// Темно-серый прямоугольник сверху
$darkGray = imagecolorallocate($img, 40, 40, 40);
imagefilledrectangle($img, 0, 0, 63, 21, $darkGray);

imagepng($img, 'public/assets/tiles/landings/ship_edge/normal.png');
```

**Время**: 10 мин

### 4.2. Полы корабля (5 типов)

**Папки**:
- `public/assets/tiles/landings/ship_floor_wood/`
- `public/assets/tiles/landings/ship_floor_iron/`
- `public/assets/tiles/landings/ship_floor_steel/`
- `public/assets/tiles/landings/ship_floor_titanium/`
- `public/assets/tiles/landings/ship_floor_crystal/`

**Спрайты** (normal.png для каждого):
- Деревянный: `rgb(101, 67, 33)` - коричневый
- Железный: `rgb(120, 120, 120)` - светло-серый
- Стальной: `rgb(90, 90, 90)` - серый
- Титановый: `rgb(70, 70, 70)` - темно-серый
- Кристаллический: `rgb(150, 150, 200)` - светло-синеватый

**AI промпт** (для SDXL/Flux):
```
"ship deck floor texture, {material} planks, seamless tileable, top-down view,
realistic, detailed, 64x64 pixels, game asset"
```
Где {material} = wood / iron / steel / titanium / crystal

**Время**: 1 час (с AI генерацией)

### 4.3. Мост спрайт

**Файл**: `public/assets/tiles/landings/bridge/normal.png`

**Описание**: Деревянный мост, вид сверху, 64x64px

**AI промпт**:
```
"wooden bridge planks, top-down view, seamless tileable, rope sides,
rustic fantasy style, 64x64 pixels, game asset"
```

**Время**: 20 мин

**Итого Фаза 4**: ~1.5 часа

---

## Фаза 5: Система атласов для корабля

### 5.1. Обновить LandingController для генерации Ship атласов

**Файл**: `src/commands/LandingController.php`

**Изменения в actionGenerateAtlas()**:

```php
public function actionGenerateAtlas()
{
    // Генерируем 2 атласа: island и ship
    $this->generateIslandAtlas();
    $this->generateShipAtlas();
}

protected function generateIslandAtlas()
{
    // Существующая логика для островных landing
    // landing types: island + sky (id 1-8, 10, 11)
    // Граничные спрайты с косинусом
}

protected function generateShipAtlas()
{
    echo "Generating Ship Atlas...\n";

    // Ship landing types: 12-17 + sky (11)
    $shipLandings = Landing::find()
        ->where(['type' => ['ship', 'sky']])
        ->orderBy('landing_id')
        ->all();

    // Atlas size calculation
    $atlasCols = 18; // max(ship_landing_ids) = 17
    $atlasRows = 18;
    $atlasWidth = $atlasCols * 64;
    $atlasHeight = $atlasRows * 64;

    $atlas = imagecreatetruecolor($atlasWidth, $atlasHeight);
    imagesavealpha($atlas, true);
    $transparent = imagecolorallocatealpha($atlas, 0, 0, 0, 127);
    imagefill($atlas, 0, 0, $transparent);

    // Row 0: variations (только для ship landings, не для sky)
    foreach ($shipLandings as $landing) {
        if ($landing->type === 'sky') continue; // Sky не имеет вариаций в атласе корабля

        $col = $landing->landing_id;
        $baseImg = $this->loadLandingSprite($landing->folder, 'normal.png');
        imagecopy($atlas, $baseImg, $col * 64, 0, 0, 0, 64, 64);
    }

    // Rows 1-17: transition sprites (граничные)
    // Генерируем граничные спрайты с ПРЯМЫМИ линиями (не косинус!)
    for ($topId = 0; $topId <= 17; $topId++) {
        for ($rightId = 0; $rightId <= 17; $rightId++) {
            $row = $topId + 1;
            $col = $rightId;

            // Создаём граничный спрайт
            $transitionSprite = $this->createShipTransitionSprite($topId, $rightId);
            imagecopy($atlas, $transitionSprite, $col * 64, $row * 64, 0, 0, 64, 64);
        }
    }

    // Сохраняем атлас
    imagepng($atlas, 'public/assets/tiles/landings/ship_atlas.png');
    echo "Ship atlas saved!\n";
}

protected function createShipTransitionSprite($topLandingId, $rightLandingId)
{
    // Создаём базовый спрайт (прозрачный)
    $img = imagecreatetruecolor(64, 64);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    // ПРЯМЫЕ линии вместо косинуса
    $lineThickness = 2;

    // Цвет линии (из логики островного генератора)
    $lineColor = $this->calculateBorderColor($topLandingId, $rightLandingId);

    // Рисуем линии по периметру если есть сосед
    if ($topLandingId !== 0 && $topLandingId !== 11) { // Не sky
        // Линия сверху
        imagefilledrectangle($img, 0, 0, 63, $lineThickness - 1, $lineColor);
    }

    if ($rightLandingId !== 0 && $rightLandingId !== 11) { // Не sky
        // Линия справа
        imagefilledrectangle($img, 64 - $lineThickness, 0, 63, 63, $lineColor);
    }

    // Аналогично для left и bottom (если нужно)

    return $img;
}

protected function calculateBorderColor($landingId1, $landingId2)
{
    // Логика из островного генератора
    // Возвращает цвет imagecolorallocate()
    return imagecolorallocate($img, 60, 60, 60); // Темно-серый
}
```

**Время**: 2 часа

### 5.2. Обновить tileLayerManager.js для Ship Atlas

**Файл**: `resources/js/modules/tileLayerManager.js`

**Изменения**:

```javascript
constructor(game) {
    this.game = game;
    this.landingAtlases = {
        island: null,  // Атлас для островных landing
        ship: null,    // Атлас для корабельных landing
    };
}

async loadAtlases() {
    // Load island atlas
    this.landingAtlases.island = await PIXI.Assets.load('/assets/tiles/landings/island_atlas.png');

    // Load ship atlas
    this.landingAtlases.ship = await PIXI.Assets.load('/assets/tiles/landings/ship_atlas.png');
}

createTileWithTransitions(landingId, tileX, tileY) {
    const landing = this.game.gameData.landings[landingId];
    if (!landing) return null;

    // Определяем какой атлас использовать
    const atlasType = landing.type === 'ship' ? 'ship' : 'island';
    const atlas = this.landingAtlases[atlasType];

    if (!atlas) {
        console.warn(`Atlas ${atlasType} not loaded`);
        return null;
    }

    // Get adjacent tiles
    const topLandingId = this.getLandingAt(tileX, tileY - 1);
    const rightLandingId = this.getLandingAt(tileX + 1, tileY);

    // ВАЖНО: Логика определения соседей
    // Если островной landing граничит с корабельным → сосед = sky
    // Если корабельный landing граничит с островным → сосед = sky
    const topLanding = this.game.gameData.landings[topLandingId];
    const rightLanding = this.game.gameData.landings[rightLandingId];

    let effectiveTopId = topLandingId;
    let effectiveRightId = rightLandingId;

    if (landing.type === 'island' && topLanding?.type === 'ship') {
        effectiveTopId = this.game.LANDING_SKY_ID;
    }
    if (landing.type === 'island' && rightLanding?.type === 'ship') {
        effectiveRightId = this.game.LANDING_SKY_ID;
    }
    if (landing.type === 'ship' && topLanding?.type === 'island') {
        effectiveTopId = this.game.LANDING_SKY_ID;
    }
    if (landing.type === 'ship' && rightLanding?.type === 'island') {
        effectiveRightId = this.game.LANDING_SKY_ID;
    }

    // Get atlas coordinates
    const coords = this.getAtlasCoordinates(landingId, {
        top: effectiveTopId,
        right: effectiveRightId
    });

    // ... остальная логика создания спрайта
}
```

**Время**: 1 час

**Итого Фаза 5**: ~3 часа

---

## Фаза 6: Backend API - Объединение данных

### 6.1. Обновить Tiles.php

**Файл**: `src/actions/map/Tiles.php`

```php
public function run()
{
    $userId = $this->getUser()->user_id;
    $regionId = (int)$this->getUser()->current_region_id;

    // Get region info
    $region = Region::findOne($regionId);

    // Get island tiles
    $islandTiles = Map::find()
        ->select(['map_id', 'landing_id', 'x', 'y'])
        ->where(['region_id' => $regionId])
        ->asArray()
        ->all();

    // Get ship tiles
    $shipTilesRaw = ShipLanding::find()
        ->select(['ship_landing_id as map_id', 'landing_id', 'x', 'y'])
        ->where(['user_id' => $userId])
        ->asArray()
        ->all();

    // Смещение координат корабля
    $shipTiles = [];
    foreach ($shipTilesRaw as $tile) {
        $shipTiles[] = [
            'map_id' => 'ship_' . $tile['map_id'], // Префикс для отличия от островных
            'landing_id' => (int)$tile['landing_id'],
            'x' => (int)$tile['x'] + (int)$region->ship_attach_x,
            'y' => (int)$tile['y'] + (int)$region->ship_attach_y,
        ];
    }

    // Объединяем данные
    $allTiles = array_merge($islandTiles, $shipTiles);

    // Cast numeric fields
    $allTiles = $this->castNumericFieldsArray($allTiles, ['map_id', 'landing_id', 'x', 'y']);

    return $this->success(['tiles' => $allTiles]);
}
```

**Время**: 30 мин

### 6.2. Обновить Entities.php

**Файл**: `src/actions/game/Entities.php`

```php
public function run()
{
    $userId = $this->getUser()->user_id;
    $regionId = (int)$this->getUser()->current_region_id;
    $region = Region::findOne($regionId);

    // Get island entities
    $islandEntities = Entity::find()
        ->where(['region_id' => $regionId])
        ->asArray()
        ->all();

    // Get ship entities
    $shipEntitiesRaw = ShipEntity::find()
        ->where(['user_id' => $userId])
        ->asArray()
        ->all();

    // Смещение координат корабля
    $shipEntities = [];
    foreach ($shipEntitiesRaw as $entity) {
        $shipEntities[] = [
            'entity_id' => 'ship_' . $entity['ship_entity_id'],
            'entity_type_id' => (int)$entity['entity_type_id'],
            'x' => (int)$entity['x'] + (int)$region->ship_attach_x,
            'y' => (int)$entity['y'] + (int)$region->ship_attach_y,
            'state' => $entity['state'],
            'durability' => (int)$entity['durability'],
        ];
    }

    // Объединяем данные
    $allEntities = array_merge($islandEntities, $shipEntities);

    return $this->success(['entities' => $allEntities]);
}
```

**Время**: 30 мин

### 6.3. Обновить CreateEntity.php

**Файл**: `src/actions/map/CreateEntity.php`

**Логика определения ship vs island**:

```php
public function run()
{
    $userId = $this->getUser()->user_id;
    $regionId = (int)$this->getUser()->current_region_id;
    $region = Region::findOne($regionId);

    // Get entity type
    $entityType = EntityType::findOne($entityTypeId);

    // Определяем: это корабль или остров?
    // Проверяем: есть ли под entity корабельный landing?
    $relativeX = $x - $region->ship_attach_x;
    $relativeY = $y - $region->ship_attach_y;

    $shipLanding = ShipLanding::findOne([
        'user_id' => $userId,
        'x' => $relativeX,
        'y' => $relativeY,
    ]);

    $isOnShip = ($shipLanding !== null);

    // Создаём entity в нужной таблице
    if ($isOnShip) {
        $entity = new ShipEntity();
        $entity->user_id = $userId;
        $entity->x = $relativeX;
        $entity->y = $relativeY;
        // ... остальные поля
    } else {
        $entity = new Entity();
        $entity->region_id = $regionId;
        $entity->x = $x;
        $entity->y = $y;
        // ... остальные поля
    }

    $entity->save();

    return $this->success(['entity_id' => $entity->getPrimaryKey()]);
}
```

**Время**: 1 час

### 6.4. Обновить DeleteEntity.php

**Аналогично CreateEntity** - определять по координатам ship vs island

**Время**: 30 мин

**Итого Фаза 6**: ~2.5 часа

---

## Фаза 7: Механика превращения Entity → Landing

### 7.1. Обновить FinishConstruction.php

**Файл**: `src/actions/game/FinishConstruction.php`

```php
public function run()
{
    // ... existing logic

    // После увеличения durability
    $entity->durability++;

    // Проверяем: достигнут ли max_durability
    $entityType = $entity->entityType;

    if ($entity->durability >= $entityType->max_durability) {
        // Проверяем: есть ли converts_to_landing_id
        if ($entityType->converts_to_landing_id !== null) {
            // ЭТО ПОЛ КОРАБЛЯ! Превращаем в landing

            // Определяем: ship или island entity
            if ($entity instanceof ShipEntity) {
                // Создаём ship_landing
                $shipLanding = new ShipLanding();
                $shipLanding->user_id = $entity->user_id;
                $shipLanding->landing_id = $entityType->converts_to_landing_id;
                $shipLanding->x = $entity->x;
                $shipLanding->y = $entity->y;
                $shipLanding->save();

                // Удаляем entity
                $entity->delete();

                return $this->success([
                    'converted_to_landing' => true,
                    'ship_landing_id' => $shipLanding->ship_landing_id,
                ]);
            } else {
                // Island entity - не должно быть, но на всякий случай
                // ... логика для island
            }
        }
    }

    $entity->save();

    return $this->success(['durability' => $entity->durability]);
}
```

**Время**: 45 мин

**Итого Фаза 7**: ~45 мин

---

## Фаза 8: Автогенерация Ship Edge

### 8.1. Обновить storeTileData в tileLayerManager.js

**Файл**: `resources/js/modules/tileLayerManager.js`

```javascript
storeTileData(tiles) {
    // First pass: store all tiles
    for (const tile of tiles) {
        const key = tileKey(tile.x, tile.y);
        this.tileDataMap.set(key, tile.landing_id);
    }

    // Second pass: auto-insert ship_edge under ship landings with empty space below
    const shipEdgesToInsert = [];
    for (const tile of tiles) {
        const landing = this.game.gameData.landings[tile.landing_id];

        // Только для корабельных landing (не sky, не island)
        if (landing?.type !== 'ship') continue;
        if (tile.landing_id === this.game.LANDING_SHIP_EDGE_ID) continue; // Ship Edge сам себя не генерирует

        // Check if there's no tile below this one
        const belowKey = tileKey(tile.x, tile.y + 1);
        const belowLandingId = this.tileDataMap.get(belowKey);

        // If below is empty or sky, insert ship_edge
        if (belowLandingId === undefined || belowLandingId === this.game.LANDING_SKY_ID) {
            shipEdgesToInsert.push({ x: tile.x, y: tile.y + 1 });
        }
    }

    // Insert ship_edge tiles
    for (const pos of shipEdgesToInsert) {
        const key = tileKey(pos.x, pos.y);
        this.tileDataMap.set(key, this.game.LANDING_SHIP_EDGE_ID);
    }

    // Existing logic for island_edge
    // ...
}
```

**Время**: 30 мин

**Итого Фаза 8**: ~30 мин

---

## Фаза 9: UI и тестирование

### 9.1. BuildingWindow - секция "Корабль"

**Файл**: `resources/js/modules/windows/buildingWindow.js`

Добавить категорию "ship" в список категорий, отобразить 5 типов пола корабля.

**Время**: 30 мин

### 9.2. Тестирование

- [ ] Проверить константы LANDING_SKY_ID (11) везде
- [ ] Проверить генерацию island atlas
- [ ] Проверить генерацию ship atlas
- [ ] Проверить объединение данных в /map/tiles
- [ ] Построить entity пола корабля
- [ ] Завершить строительство → проверить превращение в landing
- [ ] Проверить автогенерацию Ship Edge
- [ ] Проверить граничные спрайты ship/island
- [ ] Построить entity на корабле
- [ ] Проверить сохранение в ship_entity

**Время**: 2 часа

**Итого Фаза 9**: ~2.5 часа

---

## Общая оценка времени

| Фаза | Описание | Время |
|------|----------|-------|
| 1 | Константы и конфиги | 30 мин |
| 2 | База данных | 2 часа |
| 3 | Модели Backend | 30 мин |
| 4 | Генерация спрайтов | 1.5 часа |
| 5 | Система атласов | 3 часа |
| 6 | Backend API | 2.5 часа |
| 7 | Entity → Landing | 45 мин |
| 8 | Ship Edge автоген | 30 мин |
| 9 | UI и тестирование | 2.5 часа |
| **ИТОГО** | | **~14 часов** |

---

## Порядок выполнения (пошаговый)

1. ✅ Фаза 1 (константы) - обязательно первым!
2. ✅ Фаза 2.1 (обновить landing таблицу)
3. ✅ Фаза 2.2 (создать новые landing записи)
4. ✅ Запустить миграции: `php yii migrate`
5. ✅ Фаза 4 (генерация спрайтов) - параллельно с кодом
6. ✅ Фаза 2.3, 2.4, 2.5 (остальные таблицы)
7. ✅ Фаза 3 (модели)
8. ✅ Запустить: `composer run ar`
9. ✅ Фаза 5 (атласы)
10. ✅ Фаза 6 (Backend API)
11. ✅ Фаза 7 (Entity → Landing)
12. ✅ Фаза 8 (Ship Edge)
13. ✅ Фаза 9 (UI)
14. ✅ Запустить: `npm run assets`
15. ✅ Тестирование

---

## Критические моменты

⚠️ **ВАЖНО**:
1. LANDING_SKY_ID должен быть изменён везде ДО запуска игры
2. Ship atlas должен быть сгенерирован ДО загрузки игры
3. Модели ShipLanding, ShipEntity должны быть созданы ДО использования API

## Что НЕ входит в текущий план

- Система ограничения размера корабля
- Стоимость полов (можно добавить через entity_type_cost)
- UI для управления кораблём
- Анимация волн под кораблём
- Перемещение корабля между регионами

Эти фичи можно добавить позже как отдельные задачи.
