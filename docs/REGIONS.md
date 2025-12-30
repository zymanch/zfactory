# Regions System

## Overview

Система множественных регионов позволяет игроку путешествовать между различными островами на карте мира. Каждый регион - это отдельный парящий остров со своими ресурсами, врагами и сложностью.

## Database Schema

### Table: `region`

Основная таблица регионов.

| Field | Type | Description |
|-------|------|-------------|
| region_id | INT UNSIGNED | Primary key |
| name | VARCHAR(100) | Название региона (например, "Green Isle") |
| description | TEXT | Описание региона |
| difficulty | TINYINT(3) | Уровень сложности (1-5) |
| x | INT | X координата центра региона на карте мира |
| y | INT | Y координата центра региона на карте мира |
| width | INT UNSIGNED | Ширина региона |
| height | INT UNSIGNED | Высота региона |
| image_url | VARCHAR(255) | URL схематичного изображения острова |
| created_at | TIMESTAMP | Дата создания |

**Регионы:**
- 5x5 сетка = 25 регионов
- Region #1 (центр сетки) - стартовый остров
- Координаты с рандомизацией (BASE_SPACING=222, RANDOM_OFFSET=89)
- Сложность зависит от расстояния от центра (Manhattan distance)

### Table: `user_region_visit`

Отслеживание посещенных регионов и маршрутов игрока.

| Field | Type | Description |
|-------|------|-------------|
| user_region_visit_id | INT UNSIGNED | Primary key |
| user_id | INT UNSIGNED | FK -> user.user_id |
| region_id | INT UNSIGNED | FK -> region.region_id |
| from_region_id | INT UNSIGNED NULL | FK -> region.region_id (откуда пришел при первом визите) |
| view_radius | INT UNSIGNED | Радиус обзора корабля при посещении |
| last_visit_at | TIMESTAMP | Дата последнего визита |

**Unique index:** (user_id, region_id)

**Логика:**
- При первом посещении региона сохраняется `from_region_id` (для отображения маршрута)
- При повторном посещении `from_region_id` НЕ обновляется (сохраняется первый маршрут)
- `view_radius` обновляется если увеличился (апгрейд корабля)

### Ship Fields in `user` table

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| current_region_id | INT UNSIGNED | 1 | FK -> region.region_id |
| ship_view_radius | INT UNSIGNED | 400 | Радиус обзора корабля (пиксели) |
| ship_jump_distance | INT UNSIGNED | 278 | Максимальная дистанция прыжка (пиксели) |

## Region Generation

**RegionGenerator** (src/helpers/RegionGenerator.php):

```php
const GRID_SIZE = 5;           // 5x5 сетка
const BASE_SPACING = 222;      // Базовое расстояние между регионами
const RANDOM_OFFSET = 89;      // Случайный разброс (-44 to +44)
const BASE_WIDTH = 100;
const BASE_HEIGHT = 100;
```

**Генерация:**
1. Регион #1 (центр) создается миграцией
2. Остальные 24 региона генерируются скриптом
3. Координаты: `(gridX - 2) * BASE_SPACING + random_offset`
4. Сложность: `min(abs(gridX-2) + abs(gridY-2), 4) + 1`
5. Размер увеличивается с сложностью: `BASE_WIDTH + (difficulty * 20)`

## Resources Distribution

Каждый регион имеет deposits (месторождения ресурсов):

**Cluster-based generation:**
- 5-12 кластеров на регион (зависит от difficulty)
- 3-8 deposits в кластере
- Радиус кластера: 50-150 пикселей

**Distribution by difficulty:**

| Difficulty | Trees | Rocks | Ores |
|------------|-------|-------|------|
| 1-2 (Easy) | 50% | 30% | 20% (Iron/Copper) |
| 3 (Medium) | 30% | 30% | 40% (all ores) |
| 4-5 (Hard) | 20% | 20% | 60% (rare ores) |

**Resource amounts:**
- Trees: 50-150 wood
- Rocks: 100-300 stone
- Ores: 5000-9999 (практически infinite)

## Travel Mechanics

**Видимость регионов:**
- Посещенные регионы (is_visited) - всегда видны
- Непосещенные - видны только в пределах ship_view_radius от текущего региона

**Перемещение:**
1. Регион должен быть в пределах ship_jump_distance
2. POST /regions/travel с region_id
3. Обновляется user.current_region_id
4. Создается/обновляется запись в user_region_visit
5. Редирект на /game

**Fog of War:**
- Черный туман покрывает всю карту
- Вырезаются "окна" прозрачности:
  - Вокруг текущего региона (ship_view_radius с градиентом)
  - Вокруг каждого посещенного региона (saved view_radius с градиентом)

## Frontend (Regions Map)

**File:** resources/js/regions.js

**Features:**
- Canvas-based rendering
- Pan (drag)
- Zoom (mouse wheel, 0.3x - 2x)
- Fog of war overlay
- Route visualization
- Tooltip on hover

**Rendering order:**
1. Background (#1a1a2e)
2. Grid (500px cells)
3. Regions (islands)
4. Connections:
   - Green dashed lines: possible travels (can_travel)
   - Yellow solid lines: traveled route (from_region_id)
5. Fog of war (temporary canvas with destination-out)

**Region states:**
- is_current: зеленая рамка
- can_travel: кликабельный, зеленая линия от текущего
- is_visited: окно в fog of war, желтая линия от from_region_id
- hidden: за пределами view_radius, непосещенный

## API Endpoints

### GET /regions/list

Возвращает список видимых регионов.

**Response:**
```json
{
  "result": "ok",
  "regions": [
    {
      "region_id": 1,
      "name": "Green Isle",
      "difficulty": 1,
      "x": 0,
      "y": 0,
      "distance": 0,
      "is_visited": true,
      "is_current": true,
      "can_travel": true,
      "resources": "Wood: 450<br>Stone: 1.2k",
      "visited_view_radius": 0,
      "from_region_id": null
    }
  ],
  "current_region_id": 1,
  "ship_view_radius": 400,
  "ship_jump_distance": 278
}
```

### POST /regions/travel

Перемещение в другой регион.

**Request:**
```json
{
  "region_id": 2
}
```

**Response:**
```json
{
  "result": "ok",
  "current_region_id": 2,
  "message": "Traveled to Pleasant Island"
}
```

**Errors:**
- "Target region not found"
- "Already in this region"
- "Region is too far to travel"

## Game Integration

**Filtering by region:**
- /game/config - loads entities/deposits for current_region_id
- /game/entities - filters by current_region_id
- /map/tiles - filters by current_region_id
- /map/create-entity - sets region_id = current_region_id

**Navigation:**
- Header link: "Regions Map" → /regions
- Region click → /game (opens game in selected region)

## Upgrade Path

**Корабль может улучшаться:**
1. ship_view_radius увеличивается → видны дальние регионы
2. ship_jump_distance увеличивается → доступны дальние регионы
3. При повторном посещении с большим view_radius → окно fog of war расширяется

**Потенциальные улучшения:**
- Региональные враги/события
- Уникальные ресурсы по регионам
- Торговля между регионами
- Телепорты/порталы
- Региональные квесты
