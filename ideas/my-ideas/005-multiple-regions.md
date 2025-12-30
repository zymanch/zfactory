# Разные области (регионы)

## Описание
Ввести систему регионов - разных карт/областей, между которыми можно перемещаться на **корабле**.

### Основная механика
Каждый регион содержит свою карту (map, entity, deposit). Добавить в таблицы поле region_id. У пользователя хранить текущий регион.

### Страница регионов (`/regions/index`)
Отдельная страница с картой регионов (новые regions.js/regions.css).

**Визуал:**
- Фон голубой как в основной игре
- Каждый регион - летающий остров с координатами (x, y)
- Туман войны: вся карта черная, видны только исследованные области

**Интерактивность:**
- **Hover на текущий регион** → Tooltip: "Вы здесь"
- **Hover на другой регион** → Tooltip показывает:
  - Расстояние до региона: `sqrt((x2-x1)² + (y2-y1)²)`
  - Макс. дальность прыжка корабля
  - Общее количество ресурсов в регионе (сумма `resource_amount` из `deposit`, группировка по `resource_id`)
- **Клик на регион** → Мгновенный переход (без анимации), если дальность прыжка позволяет

### Характеристики корабля (реализуется позже в 006-ship-building.md)
1. **Дальность обзора** - какие регионы видны на карте
2. **Дальность прыжка** - максимальное расстояние перехода

**Правила видимости:**
- Регионы, в которых уже был - видны всегда (не зависят от дальности обзора)
- Новые регионы видны только в радиусе обзора от текущего/посещенных регионов

### Fog of War для регионов (легкая реализация)
- **Изначально:** видим только регион 1 + область вокруг (радиус обзора)
- **При переходе в новый регион:**
  - Сохраняется область видимости старого региона (радиус обзора на момент ухода)
  - Добавляется область видимости от нового региона (текущий радиус обзора)
- **Техническая реализация:** CSS opacity на регионах + сохранение в БД посещенных регионов с радиусом обзора

### Перенос ресурсов
- Все ресурсы пользователя (user_resource) автоматически переносятся в новый регион
- Никаких ограничений по массе/вместимости
- Ресурсы глобальные для пользователя

### Сохранение состояния регионов
При возврате в регион, где уже был:
- **Deposit (кроме деревьев)** - сохраняют текущее количество ресурсов
- **Деревья** - восстанавливаются с первоначальным количеством ресурсов
- **Entities** - сохраняются (здания остаются на месте)

## Оценка сложности
**Высокая (9/10)**

- Миграция существующих данных
- Изменение всех запросов (добавление фильтра по region_id)
- UI для переключения регионов
- Сохранение/загрузка разных карт

## Оценка интересности
**Очень высокая (10/10)**

Массово расширяет игровое пространство. 
Позволяет создавать разные биомы, сложность, ресурсы. 
Основа для бесконечного контента.

## Краткий план реализации

### 1. База данных

**Таблица `region`:**
```sql
CREATE TABLE region (
    region_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    difficulty TINYINT UNSIGNED DEFAULT 1,
    x INT NOT NULL,  -- координата X на карте регионов
    y INT NOT NULL,  -- координата Y на карте регионов
    width INT UNSIGNED NOT NULL,
    height INT UNSIGNED NOT NULL,
    image_url VARCHAR(255),  -- иконка острова
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

**Таблица `user_region_visit` (посещенные регионы):**
```sql
CREATE TABLE user_region_visit (
    user_region_visit_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    region_id INT UNSIGNED NOT NULL,
    view_radius INT UNSIGNED NOT NULL,  -- радиус обзора на момент посещения
    last_visit_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id, region_id),
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (region_id) REFERENCES region(region_id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

**Добавить `region_id` в существующие таблицы:**
- `map.region_id` (INT UNSIGNED, FOREIGN KEY)
- `entity.region_id` (INT UNSIGNED, FOREIGN KEY)
- `deposit.region_id` (INT UNSIGNED, FOREIGN KEY)
- `shake_zone.region_id` (INT UNSIGNED, FOREIGN KEY)

**Добавить в `user`:**
- `current_region_id` (INT UNSIGNED, DEFAULT 1, FOREIGN KEY)
- `ship_view_radius` (INT UNSIGNED, DEFAULT 300) - пока захардкодить
- `ship_jump_distance` (INT UNSIGNED, DEFAULT 500) - пока захардкодить

**Миграция:**
- Все существующие записы → `region_id = 1` (стартовый регион)
- Создать регион 1: "Стартовый остров", x=0, y=0
- Добавить в `user_region_visit` для всех пользователей: регион 1 с view_radius из user.ship_view_radius

### 2. Backend

**RegionsController** (`src/controllers/RegionsController.php`):
```php
class RegionsController extends Controller {
    public function actionIndex() // страница карты регионов
    public function actionList()  // JSON: список регионов + fog of war
    public function actionTravel($regionId) // переход в другой регион
    public function actionResources($regionId) // ресурсы региона
}
```

**Фильтрация по региону:**
- Все запросы `map/entity/deposit`: добавить фильтр `WHERE region_id = user.current_region_id`
- `game/config`: не возвращать номер региона (не нужен на странице игры)

**actionTravel($regionId) логика:**
1. Проверить расстояние до региона (Пифагор по координатам)
2. Проверить `ship_jump_distance` пользователя
3. Если можно прыгнуть:
   - Обновить `user.current_region_id`
   - Добавить/обновить запись в `user_region_visit`
   - Вернуть `{result: 'ok', redirect: '/game/index'}`

**actionResources($regionId) логика:**
```sql
SELECT resource_id, SUM(resource_amount) as total
FROM deposit
WHERE region_id = :regionId
GROUP BY resource_id
```

### 3. Frontend (`/regions/index`)

**Новые файлы:**
- `resources/js/regions.js` - карта регионов, Fog of War, tooltip
- `resources/css/regions.scss` - стили карты регионов

**regions.js структура:**
- `RegionsMap` класс - отрисовка карты регионов (Canvas или PixiJS)
- `RegionTooltip` класс - tooltip при hover
- `FogOfWar` класс - легкая реализация (CSS opacity + список посещенных регионов)

**Tooltip содержимое:**
```javascript
// Текущий регион
"Вы здесь"

// Другой регион
"Расстояние: 450 (макс: 500)
Ресурсы:
  Iron Ore: 1500
  Stone: 800
  Trees: 320"
```

**Fog of War реализация:**
```javascript
// Для каждого региона:
// 1. Если user_region_visit существует → opacity: 1
// 2. Если в радиусе обзора от посещенных → opacity: 0.5
// 3. Иначе → opacity: 0 (невидим)
```

### 4. Генерация регионов

**Автогенерация при старте игры** (похоже на генерацию карты):
- Сетка регионов: например, 5×5 (25 регионов)
- Координаты: x = col * 1000, y = row * 1000 (добавь значительный рандом)
- Случайные характеристики: width (100-200), height (100-200), difficulty (1-5)
- Генерация map/deposit для каждого региона (копия логики из MapGenerator)

### 5. Разнообразие регионов

- Разные наборы `landing_types` (пустыня, снег, лава)
- Разные ресурсы (редкие металлы в сложных регионах)
- Разные размеры карты (стартовый 100×100, сложные 200×200)
- Разная плотность deposit

### 6. Оптимизация

- Не загружать `map/entity/deposit` неактивных регионов
- Lazy load регионов на странице `/regions/index`
