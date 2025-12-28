# Database Schema

## Database: zfactory

### Tables Overview

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│     landing     │     │   entity_type   │     │      user       │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ landing_id (PK) │     │ entity_type_id  │     │ user_id (PK)    │
│ is_walk         │     │ type            │     │ username        │
│ image_url       │     │ name            │     │ auth_key        │
└────────┬────────┘     │ image_url       │     │ build_panel     │
         │              │ max_durability  │     │ camera_x        │
         │              │ width           │     │ camera_y        │
         │              │ height          │     │ zoom            │
         │              │ icon_url        │     └─────────────────┘
         │              │ power           │
         │              └────────┬────────┘
         │                       │
         ▼                       ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│       map       │     │     entity      │◄────│ entity_resource │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ map_id (PK)     │     │ entity_id (PK)  │     │ entity_id (FK)  │
│ landing_id (FK) │     │ entity_type_id  │     │ resource_id (FK)│
│ x               │     │ state           │     │ amount          │
│ y               │     │ durability      │     └────────┬────────┘
└─────────────────┘     │ x               │              │
                        │ y               │              │
                        └─────────────────┘              │
                                                         ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│  deposit_type   │     │    resource     │────►│ entity_resource │
├─────────────────┤     ├─────────────────┤     └─────────────────┘
│deposit_type_id  │     │ resource_id (PK)│
│ type (enum)     │     │ name            │
│ name            │     │ icon_url        │
│ image_url       │     │ type (enum)     │
│ resource_id (FK)│◄────┤ max_stack       │
│ resource_amount │     └─────────────────┘
│ width, height   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│     deposit     │
├─────────────────┤
│ deposit_id (PK) │
│deposit_type_id  │
│ x               │
│ y               │
│ resource_amount │
└─────────────────┘
```

## Table Definitions

### landing (terrain types)
Defines types of terrain tiles for the background layer.

| Column            | Type                  | Description                          |
|-------------------|-----------------------|--------------------------------------|
| landing_id        | INT UNSIGNED AUTO_INC | Primary key                          |
| is_buildable      | ENUM('yes','no')      | Can buildings be placed here?        |
| folder            | VARCHAR(256)          | Folder name (e.g., 'grass', 'lava')  |
| variations_count  | INT DEFAULT 5         | Procedurally generated variations    |
| ai_seed           | BIGINT NULL           | Stable Diffusion seed for base image |

**Landing Types:**
| ID | Name        | Buildable | Description                      |
|----|-------------|-----------|----------------------------------|
| 1  | grass       | yes       | Basic green terrain              |
| 2  | dirt        | yes       | Brown path                       |
| 3  | sand        | yes       | Desert/beach                     |
| 4  | water       | no        | Blue water                       |
| 5  | stone       | no        | Gray rocky terrain (unbuildable) |
| 6  | lava        | no        | Red/orange hazard                |
| 7  | snow        | yes       | White winter                     |
| 8  | swamp       | no        | Dark green marsh (unbuildable)   |
| 9  | sky         | no        | Sky background                   |
| 10 | island_edge | no        | Floating island bottom edge      |

### deposit_type (deposit definitions)
Defines types of natural resources (trees, rocks, ores) that exist in the world.

| Column              | Type                          | Description                              |
|---------------------|-------------------------------|------------------------------------------|
| deposit_type_id     | INT UNSIGNED                  | Primary key                              |
| type                | ENUM('tree','rock','ore')     | Category of deposit                      |
| name                | VARCHAR(128)                  | Display name                             |
| image_url           | VARCHAR(256)                  | Folder name for sprite (only normal.png) |
| resource_id         | INT UNSIGNED                  | FK to resource (what resource it contains)|
| resource_amount     | INT UNSIGNED DEFAULT 100      | Default resource amount in new deposits  |
| width               | TINYINT UNSIGNED DEFAULT 1    | Visual width in tiles                    |
| height              | TINYINT UNSIGNED DEFAULT 1    | Visual height in tiles                   |

**Note**: Unlike entities, deposits are always 1x1 for collision/placement calculations. `width` and `height` are for visual sprite dimensions only.

**Deposit Types:**
| ID  | Type | Name                | Resource    | Amount |
|-----|------|---------------------|-------------|--------|
| 1   | tree | Pine Tree           | Wood (1)    | 100    |
| 2   | tree | Oak Tree            | Wood (1)    | 120    |
| 3   | tree | Dead Tree           | Wood (1)    | 50     |
| 4   | tree | Birch Tree          | Wood (1)    | 100    |
| 5   | tree | Spruce Tree         | Wood (1)    | 110    |
| 6   | tree | Maple Tree          | Wood (1)    | 100    |
| 7   | tree | Willow Tree         | Wood (1)    | 90     |
| 8   | tree | Ash Tree            | Wood (1)    | 105    |
| 10  | rock | Small Rock          | Stone (5)   | 50     |
| 11  | rock | Medium Rock         | Stone (5)   | 100    |
| 12  | rock | Large Rock          | Stone (5)   | 150    |
| 300 | ore  | Iron Ore            | Iron Ore (2)| 200    |
| 301 | ore  | Copper Ore          | Copper Ore (3)| 200  |
| 302 | ore  | Aluminum Deposit    | Aluminum Ore (14)| 150|
| 303 | ore  | Titanium Deposit    | Titanium Ore (15)| 150|
| 304 | ore  | Silver Deposit      | Silver Ore (16)| 100 |
| 305 | ore  | Gold Deposit        | Gold Ore (17)| 80   |

### deposit (deposit instances)
Stores actual deposit placement on the game map.

| Column          | Type         | Description                          |
|-----------------|--------------|--------------------------------------|
| deposit_id      | INT UNSIGNED | Primary key (AUTO_INCREMENT)         |
| deposit_type_id | INT UNSIGNED | FK to deposit_type.deposit_type_id   |
| x               | INT UNSIGNED | Tile X coordinate                    |
| y               | INT UNSIGNED | Tile Y coordinate                    |
| resource_amount | INT UNSIGNED | Current resource amount remaining    |

**Key Differences from Entity:**
- **No state field**: Deposits are always "active", cannot be blueprints
- **No durability**: Deposits are removed entirely when extraction buildings placed
- **Tile coordinates**: Like map table, x/y are in tiles (not pixels)
- **Single resource**: Each deposit contains only one resource type

### map (terrain instances)
Stores actual terrain placement on the game map.

| Column     | Type         | Description                    |
|------------|--------------|--------------------------------|
| map_id     | INT UNSIGNED | Primary key                    |
| landing_id | INT UNSIGNED | FK to landing.landing_id       |
| x          | INT UNSIGNED | Tile X coordinate              |
| y          | INT UNSIGNED | Tile Y coordinate              |

**Current map:** Floating island with irregular edges
- **Max bounds**: 100x75 tiles (3200x1800 pixels)
- **Actual tiles**: ~6251 (island shape with holes)
- **Style**: Wavy edges, internal holes for floating island effect

### landing_adjacency (terrain transitions)
Defines which landing types can be adjacent to each other (currently not used for atlas generation).

| Column              | Type         | Description                              |
|---------------------|--------------|------------------------------------------|
| adjacency_id        | INT UNSIGNED | Primary key                              |
| landing_id_1        | INT UNSIGNED | FK to landing.landing_id (base terrain)  |
| landing_id_2        | INT UNSIGNED | FK to landing.landing_id (adjacent)      |

**Note:** Atlas generation now creates ALL possible transitions between ALL landing types, so this table is optional. The texture atlas system uses `landing_id` directly for coordinates:
- Row = top_landing_id + 1
- Column = right_landing_id

**Historical adjacency rules (no longer enforced):**
- Bidirectional entries for all terrain pairs
- sky (id=9) NOT adjacent to sky
- sky (id=9) NOT adjacent to island_edge (id=10)
- Total records: 88 bidirectional adjacencies

### entity_type (entity definitions)
Defines types of entities that can be placed on the map.

| Column               | Type                                                                              | Description                           |
|----------------------|-----------------------------------------------------------------------------------|---------------------------------------|
| entity_type_id       | INT UNSIGNED                                                                      | Primary key                           |
| type                 | ENUM('building','transporter','manipulator','tree','relief','resource','eye','mining','storage') | Category of entity          |
| name                 | VARCHAR(128)                                                                      | Display name                          |
| image_url            | VARCHAR(256)                                                                      | Folder name for sprite states         |
| extension            | VARCHAR(4) DEFAULT 'svg'                                                          | File extension (svg, jpg, png)        |
| max_durability       | INT UNSIGNED                                                                      | Maximum durability (health)           |
| width                | TINYINT UNSIGNED DEFAULT 1                                                        | Entity width in tiles                 |
| height               | TINYINT UNSIGNED DEFAULT 1                                                        | Entity height in tiles                |
| icon_url             | VARCHAR(256) NULL                                                                 | 64x64 icon for UI panels              |
| power                | INT UNSIGNED DEFAULT 1                                                            | Visibility radius for eye type        |
| parent_entity_type_id| INT UNSIGNED NULL                                                                 | Parent entity for orientation variants|
| orientation          | ENUM('none','up','right','down','left') DEFAULT 'none'                            | Entity orientation/direction          |

**Entity Type Categories:**
- `building` — производственные здания (furnace, assembler) - стандартные правила постройки
- `mining` — добывающие машины (drill) - требуют resource entity для размещения
- `transporter` — конвейеры и трубы
- `manipulator` — манипуляторы для загрузки/выгрузки
- `tree` — деревья (не строятся игроком, не показывают tooltip)
- `relief` — камни и рельеф (неуничтожаемые)
- `resource` — ресурсные залежи (неуничтожаемые)
- `eye` — башни видимости (Crystal Towers) с радиусом обзора = power
- `storage` — хранилища для ресурсов (сундуки, контейнеры)

**Entity Behavior System:**
Каждый тип сущности имеет свой класс поведения (EntityBehavior):
- `DefaultEntityBehavior` — building, transporter, manipulator
- `MiningEntityBehavior` — mining (требует resource entity, игнорирует проверку landing)
- `TreeEntityBehavior` — tree (не строится, без tooltip)
- `ReliefEntityBehavior` — relief (неуничтожаем)
- `ResourceEntityBehavior` — resource (неуничтожаем)
- `EyeEntityBehavior` — eye (предоставляет видимость)

**Placement Rules by Type:**
| Type     | Fog Check | Landing Check | Collision Check | Requires Target |
|----------|-----------|---------------|-----------------|-----------------|
| building | Yes       | Yes           | Yes             | No              |
| mining   | Yes       | No            | Yes             | resource entity |
| tree     | N/A       | N/A           | N/A             | N/A (non-buildable) |
| relief   | N/A       | N/A           | N/A             | N/A (non-buildable) |
| resource | N/A       | N/A           | N/A             | N/A (non-buildable) |
| eye      | Yes       | Yes           | Yes             | No              |

**Entity Types:**
| ID  | Name                  | Type        | Max Dur | Orientation | Parent |
|-----|-----------------------|-------------|---------|-------------|--------|
| 1   | Pine Tree             | tree        | 50      | none        | -      |
| 2   | Oak Tree              | tree        | 60      | none        | -      |
| 3   | Dead Tree             | tree        | 20      | none        | -      |
| 10  | Small Rock            | relief      | 100     | none        | -      |
| 11  | Medium Rock           | relief      | 200     | none        | -      |
| 12  | Large Rock            | relief      | 300     | none        | -      |
| 100 | Conveyor Belt         | transporter | 100     | right       | -      |
| 120 | Conveyor Belt         | transporter | 100     | up          | 100    |
| 121 | Conveyor Belt         | transporter | 100     | down        | 100    |
| 122 | Conveyor Belt         | transporter | 100     | left        | 100    |
| 101 | Small Furnace         | building    | 200     | none        | -      |
| 102 | Mining Drill          | mining      | 300     | none        | -      |
| 103 | Assembly Machine      | building    | 400     | none        | -      |
| 104 | Storage Chest         | storage     | 150     | none        | -      |
| 105 | Power Pole            | building    | 100     | none        | -      |
| 106 | Steam Engine          | building    | 350     | none        | -      |
| 107 | Boiler                | building    | 250     | none        | -      |
| 108 | Fast Mining Drill     | mining      | 250     | none        | -      |
| 200 | Short Manipulator     | manipulator | 80      | right       | -      |
| 210 | Short Manipulator     | manipulator | 80      | up          | 200    |
| 211 | Short Manipulator     | manipulator | 80      | down        | 200    |
| 212 | Short Manipulator     | manipulator | 80      | left        | 200    |
| 201 | Long Manipulator      | manipulator | 80      | right       | -      |
| 213 | Long Manipulator      | manipulator | 80      | up          | 201    |
| 214 | Long Manipulator      | manipulator | 80      | down        | 201    |
| 215 | Long Manipulator      | manipulator | 80      | left        | 201    |
| 300 | Iron Ore              | resource    | 9999    | none        | -      |
| 301 | Copper Ore            | resource    | 9999    | none        | -      |
| 400 | Small Crystal Tower   | eye         | 100     | none        | -      |
| 401 | Medium Crystal Tower  | eye         | 200     | none        | -      |
| 402 | Large Crystal Tower   | eye         | 300     | none        | -      |

**Orientation System:**
- Сущности с `parent_entity_type_id` - это варианты ориентации базовой сущности
- В окне построек показываются только базовые сущности (без parent)
- При постройке можно вращать объект клавишей **R** (или **К** на русской раскладке)
- Порядок вращения: right → down → left → up → right (по часовой стрелке)

### entity (entity instances)
Stores actual entity placement on the game map.

| Column         | Type                       | Description                         |
|----------------|----------------------------|-------------------------------------|
| entity_id      | INT UNSIGNED               | Primary key                         |
| entity_type_id | INT UNSIGNED               | FK to entity_type.entity_type_id    |
| state          | ENUM('built', 'blueprint') | Construction state                  |
| durability     | INT UNSIGNED               | Current durability (0 = destroyed)  |
| x              | INT UNSIGNED               | Entity X coordinate (tiles)         |
| y              | INT UNSIGNED               | Entity Y coordinate (tiles)         |

**Entity States:**
- `built` — полностью построенный объект
- `blueprint` — каркас для будущей постройки

### resource (game resources)
Defines types of resources in the game (ores, ingots, crafted items).

| Column      | Type                          | Description                          |
|-------------|-------------------------------|--------------------------------------|
| resource_id | INT UNSIGNED AUTO_INC                  | Primary key                          |
| name        | VARCHAR(128)                           | Display name                         |
| icon_url    | VARCHAR(256)                           | Path to 16x16 icon (resources folder)|
| type        | ENUM('raw','liquid','crafted','deposit')| Resource category                   |

**Resource Types:**
- `raw` — сырые ресурсы (руды, дерево, уголь)
- `liquid` — жидкие ресурсы (топливо, масла)
- `crafted` — обработанные ресурсы (слитки, пластины, компоненты)
- `deposit` — абстрактные залежи внутри resource entities (не перемещаются)

**Resources:**
| ID  | Name          | Type    | Description              |
|-----|---------------|---------|--------------------------|
| 1   | Wood          | raw     | Дерево                   |
| 2   | Iron Ore      | raw     | Железная руда            |
| 3   | Copper Ore    | raw     | Медная руда              |
| 4   | Coal          | raw     | Уголь                    |
| 5   | Stone         | raw     | Камень                   |
| 6   | Raw Crystal   | raw     | Необработанный кристалл  |
| 7   | Crude Oil        | raw     | Сырая нефть                 |
| 8   | Iron Deposit     | deposit | Железная залежь (в руде)    |
| 9   | Copper Deposit   | deposit | Медная залежь (в руде)      |
| 10  | Aluminum Deposit | deposit | Алюминиевая залежь          |
| 11  | Titanium Deposit | deposit | Титановая залежь            |
| 12  | Silver Deposit   | deposit | Серебряная залежь           |
| 13  | Gold Deposit     | deposit | Золотая залежь              |
| 14  | Aluminum Ore     | raw     | Алюминиевая руда            |
| 15  | Titanium Ore     | raw     | Титановая руда              |
| 16  | Silver Ore       | raw     | Серебряная руда             |
| 17  | Gold Ore         | raw     | Золотая руда                |
| 20  | Refined Fuel     | liquid  | Очищенное топливо           |
| 21  | Lubricant     | liquid  | Смазка                   |
| 22  | Heavy Oil     | liquid  | Тяжёлое масло            |
| 23  | Light Oil     | liquid  | Лёгкое масло             |
| 100 | Iron Ingot    | crafted | Железный слиток          |
| 101 | Copper Ingot  | crafted | Медный слиток            |
| 102 | Iron Plate    | crafted | Железная пластина        |
| 103 | Copper Plate  | crafted | Медная пластина          |
| 104 | Copper Wire   | crafted | Медный провод            |
| 105 | Screw         | crafted | Шуруп                    |
| 106 | Gear          | crafted | Шестерня                 |
| 107 | Rotor         | crafted | Ротор                    |
| 108 | Crystal       | crafted | Кристалл                 |
| 109 | Steel Plate   | crafted | Стальная пластина        |
| 110 | Circuit       | crafted | Микросхема               |
| 111 | Motor         | crafted | Мотор                    |
| 112 | Charcoal      | crafted | Древесный уголь          |
| 113 | Fuel Cell     | crafted | Топливный элемент        |

### entity_resource (entity-resource links)
Links entities to their contained resources and transport state.

| Column            | Type                 | Description                              |
|-------------------|----------------------|------------------------------------------|
| entity_resource_id| INT UNSIGNED AUTO_INC| Primary key                              |
| entity_id         | INT UNSIGNED         | FK to entity.entity_id (CASCADE)         |
| resource_id       | INT UNSIGNED         | FK to resource.resource_id (CASCADE)     |
| amount            | INT UNSIGNED         | Amount of resource                       |
| position          | DECIMAL(5,4) NULL    | Resource position on conveyor (0-1)      |
| lateral_offset    | DECIMAL(5,4) NULL    | Lateral offset on conveyor               |
| arm_position      | DECIMAL(5,4) NULL    | Arm position for manipulators (0-1)      |
| status            | ENUM NULL            | Transport status (empty, carrying, etc.) |

**Unique constraint:** (entity_id, resource_id) — одна entity может иметь только одну запись для каждого ресурса.

**Использование:**

**For buildings, storage, mining (position IS NULL):**
- Resource entities (Iron Ore, Copper Ore) содержат Iron Deposit / Copper Deposit
- Mining Drill добывает из залежей руду через рецепты
- Здания могут хранить и обрабатывать ресурсы (несколько записей на entity)

**For conveyors, manipulators (position IS NOT NULL):**
- Транспортное состояние ресурса на конвейере/манипуляторе
- Только одна запись на entity (текущий переносимый ресурс)
- `position`: положение ресурса на конвейере (0-1)
- `lateral_offset`: боковое смещение для визуального разнообразия
- `arm_position`: положение руки манипулятора (0 = источник, 0.5 = центр, 1 = цель)
- `status`: состояние транспорта (empty, carrying, waiting_transfer, idle, picking, placing)

### recipe (crafting recipes)
Defines crafting/processing recipes for buildings.

| Column            | Type         | Description                          |
|-------------------|--------------|--------------------------------------|
| recipe_id         | INT UNSIGNED | Primary key                          |
| output_resource_id| INT UNSIGNED | FK to resource (result)              |
| output_amount     | INT UNSIGNED | Amount produced (default: 1)         |
| input1_resource_id| INT UNSIGNED | FK to resource (first input)         |
| input1_amount     | INT UNSIGNED | Amount of first input (default: 1)   |
| input2_resource_id| INT UNSIGNED | FK to resource (second input, NULL)  |
| input2_amount     | INT UNSIGNED | Amount of second input               |
| input3_resource_id| INT UNSIGNED | FK to resource (third input, NULL)   |
| input3_amount     | INT UNSIGNED | Amount of third input                |
| ticks             | INT UNSIGNED | Processing time in game ticks        |

**Time Calculation:**
- 60 ticks = 1 second (базовая скорость)
- Entity power влияет на скорость: `power=100` — обычная, `power=200` — в 2 раза быстрее

**Формула отображения времени:**
```
time_seconds = (ticks / 60) * (100 / power)
```

**Примеры:**
| Ticks | Power | Calculation          | Display |
|-------|-------|----------------------|---------|
| 60    | 100   | (60/60) * (100/100)  | 1       |
| 30    | 100   | (30/60) * (100/100)  | 0.5     |
| 120   | 100   | (120/60) * (100/100) | 2       |
| 120   | 200   | (120/60) * (100/200) | 1       |
| 120   | 400   | (120/60) * (100/400) | 0.5     |

**Recipes by Building:**

| Building         | Recipe                                      | Ticks |
|------------------|---------------------------------------------|-------|
| Mining Drill     | 1 Iron Deposit → 1 Iron Ore                 | 30    |
| Mining Drill     | 1 Copper Deposit → 1 Copper Ore             | 30    |
| Small Furnace    | 3 Iron Ore + 1 Coal → 1 Iron Ingot          | 60    |
| Small Furnace    | 3 Copper Ore + 1 Coal → 1 Copper Ingot      | 60    |
| Small Furnace    | 2 Iron Ingot + 1 Coal → 1 Steel Plate       | 90    |
| Small Furnace    | 1 Wood → 1 Charcoal                         | 30    |
| Assembly Machine | 1 Iron Ingot → 2 Iron Plate                 | 40    |
| Assembly Machine | 1 Copper Ingot → 2 Copper Plate             | 40    |
| Assembly Machine | 2 Copper Ingot → 4 Copper Wire              | 20    |
| Assembly Machine | 2 Iron Plate → 4 Screw                      | 20    |
| Assembly Machine | 2 Iron Plate → 1 Gear                       | 30    |
| Assembly Machine | 2 Gear + 4 Screw → 1 Rotor                  | 60    |
| Assembly Machine | 2 Copper Wire + 1 Iron Plate → 1 Circuit    | 50    |
| Assembly Machine | 1 Rotor + 2 Circuit + 1 Copper Wire → 1 Motor | 80  |
| Assembly Machine | 1 Raw Crystal → 1 Crystal                   | 45    |
| Assembly Machine | 2 Refined Fuel + 1 Circuit → 1 Fuel Cell    | 100   |
| Boiler           | 1 Crude Oil → 1 Heavy Oil                   | 60    |
| Boiler           | 2 Heavy Oil → 1 Light Oil                   | 40    |
| Boiler           | 2 Light Oil → 1 Refined Fuel                | 30    |
| Boiler           | 3 Heavy Oil → 1 Lubricant                   | 50    |

### entity_type_recipe (entity-recipe links)
Links entity types to available recipes.

| Column         | Type         | Description                       |
|----------------|--------------|-----------------------------------|
| entity_type_id | INT UNSIGNED | FK to entity_type (CASCADE)       |
| recipe_id      | INT UNSIGNED | FK to recipe (CASCADE)            |

**Primary key:** (entity_type_id, recipe_id)

## Coordinate System

- **Map coordinates**: tile-based (x=0 means tile 0, x=1 means tile 1)
- **Entity coordinates**: tile-based (same as map coordinates)
- **Deposit coordinates**: tile-based (same as map/entity coordinates)
- **Tile dimensions**: 64x64 pixels
- **Conversion (JS rendering)**: `pixel_x = tile_x * 64`, `pixel_y = tile_y * 64`

**Important**: All three tables (map, entity, deposit) use the same tile-based coordinate system for consistency.

## SQL Files

**ВАЖНО:** При изменении структуры или данных БД обновляй ОБА файла!

| File         | Description                                          |
|--------------|------------------------------------------------------|
| database.sql | Структуры таблиц + данные landing, entity_type       |
| map.sql      | Данные таблиц entity и map (большие объемы данных)   |

### Порядок импорта
```bash
mysql zfactory < docs/database.sql
mysql zfactory < docs/map.sql
```

### Что где хранится
- **database.sql** — CREATE TABLE для всех таблиц, INSERT для справочников (landing, entity_type)
- **map.sql** — INSERT для данных карты (entity, map)

### user (user accounts)
Stores user accounts and their settings.

| Column      | Type                 | Description                              |
|-------------|----------------------|------------------------------------------|
| user_id     | INT UNSIGNED         | Primary key                              |
| username    | VARCHAR(255)         | Unique username                          |
| auth_key    | VARCHAR(255)         | Authentication key for "remember me"     |
| build_panel | TEXT NULL            | JSON array of 10 slots with entity_type_id|

**Build Panel Format:**
```json
[101, null, 102, 103, null, null, null, null, null, 105]
```
- Array of 10 elements
- Each element is either `entity_type_id` or `null`
- Indexes 0-9 correspond to keys 1-9 and 0

## Migrations

| Migration                                       | Description                                    |
|-------------------------------------------------|------------------------------------------------|
| m251214_050249_init.php                         | Initial schema creation                        |
| m251214_063543_add_entity_state_durability.php  | Add state/durability fields                    |
| m251216_120000_entity_type_extend.php           | Extend type enum + add extension column        |
| m251219_120000_add_entity_type_dimensions.php   | Add width, height, icon_url to entity_type     |
| m251219_125900_create_resource_table.php        | Create resource table with initial data        |
| m251219_125910_create_entity_resource_table.php | Create entity_resource linking table           |
| m251219_130000_create_users_table.php           | Create user table with build_panel             |
| m251219_140000_add_ore_resources.php            | Add resources to ore entities                  |
| m251220_000000_add_eye_type_and_power.php       | Add 'eye' type, power column, new entities     |
| m251220_100000_add_user_camera_position.php     | Add camera_x, camera_y, zoom to user           |
| m251220_210000_convert_entity_coords_to_tiles.php | Convert entity x,y from pixels to tiles      |
| m251220_220000_add_orientation_and_conveyor_variants.php | Add orientation system, conveyor variants |
| m251220_230000_add_manipulator_orientations.php | Add manipulator orientation variants           |
| m251220_240000_create_recipe_system.php         | Create recipe system with deposit resources    |
| m251220_250000_add_deposit_resource_type.php    | Add 'deposit' type for abstract resources      |
| m251220_260000_add_storage_entity_type.php      | Add 'storage' type for chests/containers       |
| m251221_000200_create_entity_transport.php      | Create entity_transport table (deprecated)     |
| m251227_140644_add_transport_fields_to_entity_resource.php | Add transport fields to entity_resource |
| m251227_140759_drop_entity_transport_table.php  | Drop entity_transport table                    |
| m251227_151600_add_entity_type_fields.php       | Add description, construction_ticks to entity_type |
| m251227_151601_add_entity_construction_progress.php | Add construction_progress to entity        |
| m251227_151602_fill_entity_type_descriptions.php | Fill entity_type descriptions             |
| m251228_100000_create_deposit_system.php        | Create deposit_type and deposit tables         |
| m251228_110000_add_new_resources.php            | Add aluminum, titanium, silver, gold resources |
| m251228_120000_add_extraction_buildings.php     | Add sawmills, quarries, mines entity types     |
| m251228_130000_add_extraction_recipes.php       | Add extraction building recipes                |
| m251228_140000_migrate_entities_to_deposits.php | Migrate tree/rock/ore entities to deposits     |

## Future Considerations

### Planned Tables
- `inventory` - player resources
- `recipe` - crafting recipes
- `production` - active production processes
- `conveyor` - conveyor belt connections
