# Database Schema

## Database: zfactory

### Tables Overview

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│     landing     │     │   entity_type   │     │      user       │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ landing_id (PK) │     │ entity_type_id  │     │ user_id (PK)    │
│ is_walk         │     │ type            │     │ username        │
│ folder          │     │ name            │     │ auth_key        │
└────────┬────────┘     │ folder          │     │ build_panel     │
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
│ folder          │     │ type (enum)     │
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

| Column            | Type                                  | Description                          |
|-------------------|---------------------------------------|--------------------------------------|
| landing_id        | INT UNSIGNED AUTO_INC                 | Primary key                          |
| is_buildable      | ENUM('yes','no')                      | Can buildings be placed here?        |
| fluid_type        | ENUM('none','water','lava')           | Fluid type (for fluid pumps)         |
| folder            | VARCHAR(256)                          | Folder name (e.g., 'grass', 'lava')  |
| variations_count  | INT DEFAULT 5                         | Procedurally generated variations    |
| ai_seed           | BIGINT NULL                           | Stable Diffusion seed for base image |

**Landing Types:**
| ID | Name        | Buildable | fluid_type    | Description                      |
|----|-------------|-----------|---------------|----------------------------------|
| 1  | grass       | yes       | none          | Basic green terrain              |
| 2  | dirt        | yes       | none          | Brown path                       |
| 3  | sand        | yes       | none          | Desert/beach                     |
| 4  | water       | no        | **water**     | Blue water (water pump required) |
| 5  | stone       | no        | none          | Gray rocky terrain (unbuildable) |
| 6  | lava        | no        | **lava**      | Red/orange hazard (lava pump)    |
| 7  | snow        | yes       | none          | White winter                     |
| 8  | swamp       | no        | none          | Dark green marsh (unbuildable)   |
| 9  | sky         | no        | none          | Sky background                   |
| 10 | island_edge | no        | none          | Floating island bottom edge      |

### deposit_type (deposit definitions)
Defines types of natural resources (trees, rocks, ores) that exist in the world.

| Column              | Type                          | Description                              |
|---------------------|-------------------------------|------------------------------------------|
| deposit_type_id     | INT UNSIGNED                  | Primary key                              |
| type                | ENUM('tree','rock','ore')     | Category of deposit                      |
| name                | VARCHAR(128)                  | Display name                             |
| folder              | VARCHAR(256)                  | Folder name for sprite (only normal.png) |
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
| type                 | ENUM('building','conveyor','pipe','electricity','manipulator','tree','relief','resource','eye','mining','storage','ship','hq') | Category of entity |
| name                 | VARCHAR(128)                                                                      | Display name                          |
| folder               | VARCHAR(256)                                                                      | Folder name for sprite states         |
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
- `mining` — добывающие машины (drill, sawmill, mine) - требуют deposit для размещения
- `conveyor` — конвейерные ленты для транспортировки предметов
- `pipe` — трубы для транспортировки жидкостей
- `electricity` — электрические сооружения (pylons, batteries, generators)
- `manipulator` — манипуляторы для загрузки/выгрузки
- `tree` — деревья (не строятся игроком, не показывают tooltip)
- `relief` — камни и рельеф (неуничтожаемые)
- `resource` — ресурсные залежи (неуничтожаемые)
- `eye` — башни видимости (Crystal Towers) с радиусом обзора = power
- `storage` — хранилища для ресурсов (сундуки, контейнеры)
- `ship` — корабельные сегменты (ship floor tiles)
- `hq` — штаб-квартира

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
| 123 | Conveyor Belt (Dual)  | transporter | 100     | right       | 100    |
| 124 | Conveyor Belt (Dual)  | transporter | 100     | down        | 100    |
| 125 | Conveyor Belt (Dual)  | transporter | 100     | left        | 100    |
| 126 | Conveyor Belt (Dual)  | transporter | 100     | up          | 100    |
| 127 | Fast Conveyor (Dual)  | transporter | 200     | right       | 100    |
| 128 | Fast Conveyor (Dual)  | transporter | 200     | down        | 100    |
| 129 | Fast Conveyor (Dual)  | transporter | 200     | left        | 100    |
| 130 | Fast Conveyor (Dual)  | transporter | 200     | up          | 100    |
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
| 900 | Small Pylon           | electricity | 100     | none        | -      |
| 901 | Medium Pylon          | electricity | 200     | none        | -      |
| 902 | Large Pylon           | electricity | 300     | none        | -      |
| 910 | Small Battery         | electricity | 150     | none        | -      |
| 911 | Medium Battery        | electricity | 250     | none        | -      |
| 912 | Large Battery         | electricity | 400     | none        | -      |
| 920 | Coal Generator        | electricity | 300     | none        | -      |
| 921 | Solar Panel Small     | electricity | 100     | none        | -      |
| 922 | Solar Panel Large     | electricity | 200     | none        | -      |

**Electricity System Entities:**
Electricity entities use the `power` field with different meanings:
- **Pylons**: power = transmission radius in tiles (7, 15, 30)
- **Batteries**: power = storage capacity (100, 500, 2000)
- **Generators**: power = production rate per tick (10, 5, 25)

**Orientation System:**
- Сущности с `parent_entity_type_id` - это варианты ориентации базовой сущности
- В окне построек показываются только базовые сущности (без parent)
- При постройке можно вращать объект клавишей **R** (или **К** на русской раскладке)
- Порядок вращения: right → down → left → up → right (по часовой стрелке)

**Conveyor Color Variants (Dual & Fast Dual):**
Двухполосные конвейеры (Dual) и быстрые двухполосные (Fast Dual) - это цветовые варианты базового конвейера:
- **conveyor_dual** (123-126): HSL hue shift +240° → **Strong Blue** color
- **conveyor_fast_dual** (127-130): HSL hue shift +120° → **Strong Green** color
- Генерируются БЕЗ ComfyUI - используют HSL преобразование базового спрайта (entity_type_id=100)
- Все 5 состояний (normal, damaged, blueprint, normal_selected, damaged_selected) создаются автоматически
- Ротационные варианты (_up, _down, _left) наследуют цвет базовой ориентации

**Sprite Generation Commands:**
```bash
# Синий двухполосный (не требует ComfyUI)
php yii entity/generate-ai-flux conveyor_dual 0

# Зеленый быстрый двухполосный (не требует ComfyUI)
php yii entity/generate-ai-flux conveyor_fast_dual 0

# Базовый конвейер (требует ComfyUI для AI генерации)
php yii entity/generate-ai-flux conveyor 0
```

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
| type        | ENUM('raw','liquid','crafted','deposit','energy')| Resource category                   |

**Resource Types:**
- `raw` — сырые ресурсы (руды, дерево, уголь)
- `liquid` — жидкие ресурсы (топливо, масла)
- `crafted` — обработанные ресурсы (слитки, пластины, компоненты)
- `deposit` — абстрактные залежи внутри resource entities (не перемещаются)
- `energy` — энергетические ресурсы (электричество, солнечный свет)

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
| 400 | Electricity   | energy  | Электричество            |

**Energy Resources:**
Energy resources (type='energy') are used for electricity system:
- `400` - **Electricity**: Stored in batteries, consumed by recipes
- Resource is not created in database - only used as placeholder for recipe system

### entity_resource (entity-resource links)
Links entities to their contained resources and transport state.

| Column            | Type                 | Description                              |
|-------------------|----------------------|------------------------------------------|
| entity_resource_id| INT UNSIGNED AUTO_INC| Primary key                              |
| entity_id         | INT UNSIGNED         | FK to entity.entity_id (CASCADE)         |
| resource_id       | INT UNSIGNED         | FK to resource.resource_id (CASCADE)     |
| amount            | INT UNSIGNED         | Amount of resource                       |
| position_px       | INT NULL             | Resource position in pixels (centered)   |
| from_direction    | ENUM('up','down','left','right') NULL | Entry direction for conveyors |
| status            | ENUM NULL            | Transport status (empty, carrying, etc.) |

**Unique constraint:** (entity_id, resource_id) — одна entity может иметь только одну запись для каждого ресурса.

**Использование:**

**For buildings, storage, mining (position_px IS NULL):**
- Resource entities (Iron Ore, Copper Ore) содержат Iron Deposit / Copper Deposit
- Mining Drill добывает из залежей руду через рецепты
- Здания могут хранить и обрабатывать ресурсы (несколько записей на entity)

**For conveyors, manipulators (position_px IS NOT NULL):**
- Транспортное состояние ресурса на конвейере/манипуляторе
- Только одна запись на entity (текущий переносимый ресурс)
- **Centered coordinate system:**
  - `position_px = 0` - центр пути
  - Отрицательные значения (-32 to 0 для tileWidth=64) - движение К центру
  - Положительные значения (0 to +32) - движение ОТ центра
- **For conveyors:** position_px от -32 до +32 (для 64px тайлов)
- **For manipulators:**
  - Short (reach=1): -96 to +96 (центр через 1.5 тайла)
  - Long (reach=2): -192 to +192 (центр через 2.5 тайла)
- `from_direction`: направление, откуда ресурс вошел на конвейер (влияет на визуальное смещение полосы)
- `status`: состояние транспорта (empty, carrying, waiting_transfer, idle, picking, placing)

### electricity_system (electricity networks)
Stores electricity networks detected by BFS algorithm.

| Column            | Type         | Description                          |
|-------------------|--------------|--------------------------------------|
| system_id         | INT UNSIGNED | Primary key (AUTO_INCREMENT)         |
| region_id         | INT UNSIGNED | FK to region.region_id               |
| total_capacity    | INT DEFAULT 0| Sum of battery capacities            |
| total_electricity | INT DEFAULT 0| Current electricity in system        |

**Note:** Systems are recalculated on every CREATE/DELETE/FINISH_CONSTRUCTION of electricity entities.

### electricity_system_member (system membership)
Links entities to their electricity systems.

| Column     | Type         | Description                              |
|------------|--------------|------------------------------------------|
| system_id  | INT UNSIGNED | FK to electricity_system.system_id (CASCADE) |
| entity_id  | INT UNSIGNED | FK to entity.entity_id (CASCADE)         |
| role       | ENUM('pylon','battery','generator','consumer') | Entity role in system |

**Unique constraint:** (system_id, entity_id)

**Roles:**
- `pylon` - Transmits electricity via power radius
- `battery` - Stores electricity (capacity = entity_type.power)
- `generator` - Produces electricity (rate = entity_type.power)
- `consumer` - Consumes electricity (any building with electricity recipes)

**Algorithm (ElectricitySystemManager.php):**
1. Find all `type='electricity'` entities with `state='built'`
2. BFS: Group entities connected by power radius (Euclidean distance)
3. Create `electricity_system` for each network
4. Create `electricity_system_member` for each entity
5. Calculate `total_capacity` from batteries
6. Load `total_electricity` from `entity_resource`

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

### World Coordinates (Map, Entity, Deposit)
- **Map coordinates**: tile-based (x=0 means tile 0, x=1 means tile 1)
- **Entity coordinates**: tile-based (same as map coordinates)
- **Deposit coordinates**: tile-based (same as map/entity coordinates)
- **Tile dimensions**: 64x64 pixels (configurable via `tile_width`, `tile_height`)
- **Conversion (JS rendering)**: `pixel_x = tile_x * tileWidth`, `pixel_y = tile_y * tileHeight`

**Important**: All three tables (map, entity, deposit) use the same tile-based coordinate system for consistency.

### Transport Coordinates (Conveyors, Manipulators)
**Centered coordinate system** - используется для `position_px` в таблице `entity_resource`:

- **Center = 0**: Центр пути (середина тайла или середина досягаемости манипулятора)
- **Negative values**: Движение К центру (entry → center)
- **Positive values**: Движение ОТ центра (center → exit)

**Ranges by entity type:**
| Entity Type        | Range           | Center Calculation       |
|--------------------|-----------------|--------------------------|
| Conveyor           | -32 to +32      | tileWidth / 2            |
| Short Manipulator  | -96 to +96      | reach × tileWidth × 1.5  |
| Long Manipulator   | -192 to +192    | reach × tileWidth × 1.5  |

**Visual offsets:**
- `from_direction` определяет перпендикулярное смещение для визуального эффекта двухполосных конвейеров
- Смещение: ±tileHeight/4 (±16px для 64px тайлов)

**Advantages:**
- Symmetric: -center...0...+center
- Simple rendering: `offsetX = position_px` (no subtraction needed)
- Intuitive: 0 = always center
- No redundant fields: same `position_px` for conveyors and manipulators

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
| m260104_000005_add_center_position_px.php       | Add center_position_px to entity_type          |
| m260104_000006_migrate_to_pixel_coordinates.php | Migrate to pixel-based coordinate system       |
| m260104_000007_add_dual_lane_conveyors.php      | Add dual-lane conveyor types (IDs 123-130)    |
| m260104_000008_unify_position_fields.php        | Unify position_px, remove arm_position_px      |
| m260109_135158_refactor_sprite_folders.php      | Rename image_url to folder in entity_type and deposit_type |
| m260110_000001_add_electricity_to_entity_type.php | Split transporter into conveyor/pipe/electricity |
| m260110_000002_create_electricity_system_tables.php | Create electricity_system and electricity_system_member |
| m260110_000003_add_electricity_resources_and_types.php | Add resources 400-401, entity types 900-922 |
| m260110_000004_add_electricity_recipes.php       | Add electricity generation recipes (500-502)   |
| m260110_163000_fix_solar_panel_recipes.php       | Remove Sunlight resource, fix generators       |
| m260110_163100_fix_coal_generator_recipe.php     | Change Coal Generator to use Coal (ID 4)       |
| m260110_164000_remove_power_pole.php             | Remove obsolete power_pole entity type (ID 105)|

## Future Considerations

### Planned Tables
- `inventory` - player resources
- `recipe` - crafting recipes
- `production` - active production processes
- `conveyor` - conveyor belt connections
