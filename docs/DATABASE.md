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
                        ┌─────────────────┐     ┌─────────────────┐
                        │    resource     │────►│ entity_resource │
                        ├─────────────────┤     └─────────────────┘
                        │ resource_id (PK)│
                        │ name            │
                        │ icon_url        │
                        │ type (enum)     │
                        └─────────────────┘
```

## Table Definitions

### landing (terrain types)
Defines types of terrain tiles for the background layer.

| Column       | Type                  | Description                     |
|--------------|-----------------------|---------------------------------|
| landing_id   | INT UNSIGNED AUTO_INC | Primary key                     |
| is_buildable | ENUM('yes','no')      | Can buildings be placed here?   |
| image_url    | VARCHAR(256)          | Path to 32x24 tile image        |

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
| icon_url             | VARCHAR(256) NULL                                                                 | 32x24 icon for UI panels              |
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
| 7   | Crude Oil     | raw     | Сырая нефть              |
| 8   | Iron Deposit  | deposit | Железная залежь (в руде) |
| 9   | Copper Deposit| deposit | Медная залежь (в руде)   |
| 20  | Refined Fuel  | liquid  | Очищенное топливо        |
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
Links entities to their contained resources.

| Column            | Type                 | Description                              |
|-------------------|----------------------|------------------------------------------|
| entity_resource_id| INT UNSIGNED AUTO_INC| Primary key                              |
| entity_id         | INT UNSIGNED         | FK to entity.entity_id (CASCADE)         |
| resource_id       | INT UNSIGNED         | FK to resource.resource_id (CASCADE)     |
| amount            | INT UNSIGNED         | Amount of resource                       |

**Unique constraint:** (entity_id, resource_id) — одна entity может иметь только одну запись для каждого ресурса.

**Использование:**
- Resource entities (Iron Ore, Copper Ore) содержат Iron Deposit / Copper Deposit
- Mining Drill добывает из залежей руду через рецепты
- Здания могут хранить и обрабатывать ресурсы

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
- **Tile dimensions**: 32x24 pixels
- **Conversion (JS rendering)**: `pixel_x = tile_x * 32`, `pixel_y = tile_y * 24`

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

## Future Considerations

### Planned Tables
- `inventory` - player resources
- `recipe` - crafting recipes
- `production` - active production processes
- `conveyor` - conveyor belt connections
