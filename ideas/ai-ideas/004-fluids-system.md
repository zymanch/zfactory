# Система жидкостей (Упрощенная)

## Описание
Новый тип транспорта - **трубы** для жидкостей (вода, нефть, газ, магма). Все соединенные трубы образуют **единую замкнутую систему с общим объемом**. Если одно здание добавило жидкость в трубу, другое здание может сразу же её использовать.

## Оценка сложности
**Средняя (6/10)** _(снижена с 9/10 благодаря упрощению)_

- ✅ Упрощенная физика (нет давления, нет насосов)
- ✅ Общий пул жидкости для всей системы труб
- ✅ Автоматическая генерация спрайтов соединений
- ⚠️ Визуализация жидкости в трубах
- ⚠️ Вычисление замкнутых систем труб
- ⚠️ Новые качалки для 4 типов жидкостей

## Оценка интересности
**Очень высокая (9/10)**

Добавляет новое измерение в производственные цепочки. Открывает химию и нефтепереработку. Упрощенная система легче в понимании для игроков.

---

## Ключевые принципы системы

### 1. Общий объем (Pooled System)
- Все соединенные трубы = **одна система** с общим объемом
- Если здание А добавило 100 единиц воды → здание Б сразу может их забрать
- **Нет потока, нет давления, нет задержки**

### 2. Запрет смешивания
- В одной системе может быть только **один тип жидкости**
- Попытка добавить другую жидкость = ошибка (блокировка добавления)
- Для смены жидкости нужно **очистить/разобрать трубы**

### 3. Емкости = трубы
- Резервуары (storage tanks) = труба с большой вместимостью
- Используют то же поле `entity_type.power` для емкости
- Пример: обычная труба power=100, резервуар power=10000

### 4. Несвязанные системы
- Если трубы не соединены физически → разные системы
- Каждая система имеет свой объем и ресурс
- Системы не влияют друг на друга

---

## Детальный план реализации

### 1. БАЗА ДАННЫХ

#### 1.1 Новая таблица: pipe_system
Хранит информацию о замкнутых системах труб:

```sql
CREATE TABLE pipe_system (
    pipe_system_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    region_id INT UNSIGNED NOT NULL,
    resource_id INT UNSIGNED NULL,              -- Тип жидкости (NULL = пустая)
    current_amount INT UNSIGNED DEFAULT 0,      -- Текущее количество жидкости
    max_capacity INT UNSIGNED DEFAULT 0,        -- Суммарная емкость всех труб
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_region (region_id)
);
```

#### 1.2 Новая таблица: pipe_system_member
Связывает entity с системами труб:

```sql
CREATE TABLE pipe_system_member (
    pipe_system_id INT UNSIGNED NOT NULL,
    entity_id INT UNSIGNED NOT NULL,

    PRIMARY KEY (pipe_system_id, entity_id),
    INDEX idx_entity (entity_id)
);
```

#### 1.3 Новые типы entity

**A. Трубы (pipes):**
```sql
-- Горизонтальная труба (базовая)
INSERT INTO entity_type VALUES (
    900, 'pipe', 'Pipe', 'pipe', 'png', 100, 1, 1,
    'pipe/normal.png', 100, NULL, 'horizontal', NULL,
    'Труба для транспортировки жидкостей', 60
);

-- Вертикальная труба (ротация от горизонтальной)
INSERT INTO entity_type VALUES (
    901, 'pipe', 'Pipe', 'pipe_vertical', 'png', 100, 1, 1,
    'pipe_vertical/normal.png', 100, 900, 'vertical', NULL,
    'Труба для транспортировки жидкостей (вертикальная)', 60
);
```

**B. Резервуары (storage tanks):**
```sql
-- Маленький резервуар 2x2
INSERT INTO entity_type VALUES (
    905, 'pipe', 'Small Tank', 'tank_small', 'png', 200, 2, 2,
    'tank_small/normal.png', 5000, NULL, 'none', NULL,
    'Резервуар для хранения жидкостей (5000 единиц)', 180
);

-- Большой резервуар 3x3
INSERT INTO entity_type VALUES (
    906, 'pipe', 'Large Tank', 'tank_large', 'png', 300, 3, 3,
    'tank_large/normal.png', 25000, NULL, 'none', NULL,
    'Большой резервуар для жидкостей (25000 единиц)', 300
);
```

**C. Подземные трубы:**
```sql
-- Вход подземной трубы
INSERT INTO entity_type VALUES (
    910, 'pipe', 'Underground Pipe (Input)', 'pipe_underground_in', 'png', 100, 1, 1,
    'pipe_underground_in/normal.png', 100, NULL, 'right', NULL,
    'Вход подземной трубы', 60
);

-- Выход подземной трубы (4 ориентации: up/down/left/right)
INSERT INTO entity_type VALUES (
    911, 'pipe', 'Underground Pipe (Output)', 'pipe_underground_out', 'png', 100, 1, 1,
    'pipe_underground_out/normal.png', 100, 910, 'right', NULL,
    'Выход подземной трубы', 60
);
```

**D. Качалки жидкостей (type='mining'):**
```sql
-- Водяной насос (добывает из landing water)
INSERT INTO entity_type VALUES (
    920, 'mining', 'Water Pump', 'water_pump', 'png', 200, 2, 2,
    'water_pump/normal.png', 100, NULL, 'none', NULL,
    'Качает воду из водоёма', 120
);

-- Нефтяной насос (добывает из deposit oil)
INSERT INTO entity_type VALUES (
    921, 'mining', 'Oil Pump', 'oil_pump', 'png', 200, 3, 3,
    'oil_pump/normal.png', 100, NULL, 'none', NULL,
    'Качает сырую нефть из скважины', 180
);

-- Газовый насос (добывает из deposit gas)
INSERT INTO entity_type VALUES (
    922, 'mining', 'Gas Pump', 'gas_pump', 'png', 200, 2, 2,
    'gas_pump/normal.png', 150, NULL, 'none', NULL,
    'Качает природный газ', 120
);

-- Магматический насос (добывает из landing lava)
INSERT INTO entity_type VALUES (
    923, 'mining', 'Lava Pump', 'lava_pump', 'png', 200, 2, 2,
    'lava_pump/normal.png', 50, NULL, 'none', NULL,
    'Качает магму из лавового озера', 180
);
```

#### 1.4 Новые ресурсы (жидкости)

```sql
-- Вода (уже может быть в базе, проверить)
INSERT INTO resource VALUES (300, 'Water', 'water.svg', 'liquid');

-- Сырая нефть
INSERT INTO resource VALUES (301, 'Crude Oil', 'crude_oil.svg', 'liquid');

-- Природный газ
INSERT INTO resource VALUES (302, 'Natural Gas', 'natural_gas.svg', 'liquid');

-- Магма/лава
INSERT INTO resource VALUES (303, 'Lava', 'lava.svg', 'liquid');
```

#### 1.5 Landing для добычи жидкостей (использовать существующие)

```sql
-- Вода (landing_id=4, уже существует в БД)
-- Используется Water Pump для добычи воды с этих тайлов
-- Уже есть в таблице landing, не требует создания

-- Лава (landing_id=6, уже существует в БД)
-- Используется Lava Pump для добычи магмы с этих тайлов
-- Уже есть в таблице landing, не требует создания
```

#### 1.6 Новые deposit_type для жидкостей

```sql
-- Нефтяное месторождение
INSERT INTO deposit_type VALUES (
    20, 'ore', 'Oil Well', 'oil_well', 301, 10000, 3, 3,
    'Нефтяная скважина - источник сырой нефти'
);

-- Газовое месторождение
INSERT INTO deposit_type VALUES (
    21, 'ore', 'Gas Vent', 'gas_vent', 302, 8000, 2, 2,
    'Газовое месторождение - источник природного газа'
);
```

#### 1.7 Рецепты для качалок

```sql
-- Водяной насос: бесконечный ресурс (не требует deposit)
-- Рецепт не нужен, вода генерируется автоматически при размещении на landing water

-- Нефтяной насос: Oil Deposit → Crude Oil
INSERT INTO recipe VALUES (
    50, 301, 10, 401, 10, NULL, NULL, NULL, NULL, 60
);

-- Газовый насос: Gas Deposit → Natural Gas
INSERT INTO recipe VALUES (
    51, 302, 10, 402, 10, NULL, NULL, NULL, NULL, 60
);

-- Магматический насос: бесконечный ресурс (на landing lava)
-- Рецепт не нужен, магма генерируется автоматически
```

---

### 2. СПРАЙТЫ ТРУБ

#### 2.1 Базовый спрайт горизонтальной трубы (64x64px, PNG)

**Требования к спрайту:**
- Ширина трубы: **20 пикселей** (центрировано)
- Прозрачное окошко посередине: **12x12 пикселей**
- Симметрия: горизонтально и вертикально
- Цвет трубы: металлический серый (#5A5A5A)
- Окошко: полностью прозрачное (alpha=0)

**Структура спрайта (pipe/normal.png):**
```
┌────────────────────────────────────────────────────────────────┐
│                                                                │
│         ┌──────────────────────────────────────────┐          │ ← Верхний край трубы (y=22)
│         │▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓│          │
│         │▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓│          │
│         │▓▓▓▓▓▓▓▓▓▓  [ОКОШКО 12x12]  ▓▓▓▓▓▓▓▓▓▓▓│ ← Центр (y=32)
│         │▓▓▓▓▓▓▓▓▓▓     прозрачное    ▓▓▓▓▓▓▓▓▓▓▓│
│         │▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓│          │
│         │▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓│          │
│         └──────────────────────────────────────────┘          │ ← Нижний край (y=42)
│                                                                │
└────────────────────────────────────────────────────────────────┘
    x=0                                                    x=64

Координаты:
- Труба: x=22 to x=42 (ширина 20px)
- Окошко: x=26 to x=38, y=26 to y=38 (12x12px, центр в 32,32)
```

#### 2.2 Система автогенерации вариантов соединений

**Алгоритм генерации:**

1. **Базовый спрайт** - горизонтальная труба (pipe/normal.png)
2. **Вертикальная труба** - поворот базового на 90° (pipe_vertical/normal.png)
3. **Пересечение труб** - комбинация четвертинок:

**Четвертинки трубы:**
Режем базовый спрайт по диагоналям:
- Верхний-левый угол (TL): от (0,0) до (32,32)
- Верхний-правый угол (TR): от (32,0) до (64,32)
- Нижний-левый угол (BL): от (0,32) до (32,64)
- Нижний-правый угол (BR): от (32,32) до (64,64)

**Варианты соединений (16 типов):**

```
Вход только слева:          Вход слева + справа:        Все 4 входа:
      │                           ───│───                    │
  ────┤                           ───┼───                  ──┼──
      │                              │                       │

Вход сверху + снизу:        Вход слева + сверху:        ...и т.д.
      │                           │
      │                           │
      ├────                        └────
      │
      │
```

**Правила комбинирования четвертинок:**

```javascript
// Пример: труба с входами сверху, снизу и слева (T-образная)
function generatePipeCrossing(connections) {
    const canvas = new Canvas(64, 64);
    const baseQuarter = loadQuarter('BL'); // Нижний-левый как базовый

    if (connections.up) canvas.drawQuarter(baseQuarter, 0, 0, 0);      // 0° (TL)
    if (connections.right) canvas.drawQuarter(baseQuarter, 32, 0, 90); // 90° (TR)
    if (connections.down) canvas.drawQuarter(baseQuarter, 32, 32, 180); // 180° (BR)
    if (connections.left) canvas.drawQuarter(baseQuarter, 0, 32, 270); // 270° (BL)

    return canvas.toPNG();
}
```

**Консольная команда для генерации всех вариантов:**
```bash
php yii pipe/generate-connection-variants
```

#### 2.3 Визуализация жидкости в трубе

**Рендеринг в JS (PipeRenderer.js):**

```javascript
class PipeRenderer {
    renderPipeWithFluid(pipeEntity, pipeSystem) {
        const pipeSprite = this.loadSprite(pipeEntity.image_url);

        if (pipeSystem.resource_id) {
            // Получаем цвет жидкости из ресурса
            const fluidColor = this.getFluidColor(pipeSystem.resource_id);

            // Создаем прямоугольник жидкости для окошка (12x12px в центре)
            const fluidRect = new PIXI.Graphics();
            fluidRect.beginFill(fluidColor);
            fluidRect.drawRect(26, 26, 12, 12); // Окошко в центре трубы
            fluidRect.endFill();

            // Добавляем жидкость под спрайт трубы
            pipeSprite.addChildAt(fluidRect, 0);
        }

        return pipeSprite;
    }

    getFluidColor(resourceId) {
        const colors = {
            300: 0x3498db, // Water - синий
            301: 0x2c3e50, // Crude Oil - чёрный
            302: 0x95a5a6, // Natural Gas - серый
            303: 0xe74c3c, // Lava - красный/оранжевый
        };
        return colors[resourceId] || 0xffffff;
    }
}
```

---

### 3. BACKEND (PHP)

#### 3.1 Новый класс: PipeSystemManager

**Файл:** `src/bl/pipes/PipeSystemManager.php`

```php
<?php
namespace bl\pipes;

use models\PipeSystem;
use models\PipeSystemMember;
use models\Entity;
use Yii;

class PipeSystemManager
{
    /**
     * Вычисляет все системы труб в регионе
     * @param int $regionId
     * @return void
     */
    public static function recalculateSystems(int $regionId): void
    {
        // 1. Удалить все старые системы для региона
        PipeSystem::deleteAll(['region_id' => $regionId]);

        // 2. Получить все трубы в регионе
        $pipes = Entity::find()
            ->joinWith('entityType')
            ->where(['entity_type.type' => 'pipe'])
            ->andWhere(['entity.region_id' => $regionId])
            ->all();

        // 3. Для каждой необработанной трубы - создать новую систему через BFS
        $processed = [];
        foreach ($pipes as $pipe) {
            if (isset($processed[$pipe->entity_id])) continue;

            $systemMembers = self::findConnectedPipes($pipe, $pipes, $processed);
            self::createSystem($regionId, $systemMembers);
        }
    }

    /**
     * BFS поиск всех соединенных труб
     * @param Entity $startPipe
     * @param Entity[] $allPipes
     * @param array &$processed
     * @return Entity[]
     */
    private static function findConnectedPipes(Entity $startPipe, array $allPipes, array &$processed): array
    {
        $queue = [$startPipe];
        $system = [];
        $processed[$startPipe->entity_id] = true;

        while (!empty($queue)) {
            $pipe = array_shift($queue);
            $system[] = $pipe;

            // Найти все трубы, соединенные с текущей (8 направлений)
            $neighbors = self::getNeighborPipes($pipe, $allPipes);

            foreach ($neighbors as $neighbor) {
                if (!isset($processed[$neighbor->entity_id])) {
                    $processed[$neighbor->entity_id] = true;
                    $queue[] = $neighbor;
                }
            }
        }

        return $system;
    }

    /**
     * Находит соседние трубы (проверка физического соединения)
     */
    private static function getNeighborPipes(Entity $pipe, array $allPipes): array
    {
        $neighbors = [];

        // Проверяем 4 направления (up, down, left, right)
        $directions = [
            ['dx' => 0, 'dy' => -1], // up
            ['dx' => 0, 'dy' => 1],  // down
            ['dx' => -1, 'dy' => 0], // left
            ['dx' => 1, 'dy' => 0],  // right
        ];

        foreach ($directions as $dir) {
            $x = $pipe->x + $dir['dx'];
            $y = $pipe->y + $dir['dy'];

            foreach ($allPipes as $otherPipe) {
                if ($otherPipe->x == $x && $otherPipe->y == $y) {
                    $neighbors[] = $otherPipe;
                    break;
                }
            }
        }

        return $neighbors;
    }

    /**
     * Создает новую систему труб
     */
    private static function createSystem(int $regionId, array $members): void
    {
        // Вычисляем суммарную емкость
        $maxCapacity = 0;
        foreach ($members as $pipe) {
            $maxCapacity += $pipe->entityType->power;
        }

        // Определяем текущий ресурс и количество
        $resourceId = null;
        $currentAmount = 0;

        foreach ($members as $pipe) {
            $resource = EntityResource::find()
                ->where(['entity_id' => $pipe->entity_id])
                ->one();

            if ($resource) {
                $resourceId = $resource->resource_id;
                $currentAmount += $resource->amount;
                break; // Все трубы в системе имеют один ресурс
            }
        }

        // Создаем систему
        $system = new PipeSystem();
        $system->region_id = $regionId;
        $system->resource_id = $resourceId;
        $system->current_amount = $currentAmount;
        $system->max_capacity = $maxCapacity;
        $system->save();

        // Добавляем членов системы
        foreach ($members as $pipe) {
            $member = new PipeSystemMember();
            $member->pipe_system_id = $system->pipe_system_id;
            $member->entity_id = $pipe->entity_id;
            $member->save();
        }
    }

    /**
     * Добавить ресурс в систему труб
     * @return bool успешно ли добавлено
     */
    public static function addFluid(int $pipeEntityId, int $resourceId, int $amount): bool
    {
        $member = PipeSystemMember::find()
            ->where(['entity_id' => $pipeEntityId])
            ->one();

        if (!$member) return false;

        $system = PipeSystem::findOne($member->pipe_system_id);

        // Проверка смешивания
        if ($system->resource_id && $system->resource_id != $resourceId) {
            return false; // Нельзя смешивать жидкости
        }

        // Проверка переполнения
        if ($system->current_amount + $amount > $system->max_capacity) {
            return false; // Переполнение
        }

        // Добавляем ресурс
        $system->resource_id = $resourceId;
        $system->current_amount += $amount;
        $system->save();

        return true;
    }

    /**
     * Забрать ресурс из системы труб
     * @return int количество забранного ресурса (может быть меньше запрошенного)
     */
    public static function takeFluid(int $pipeEntityId, int $resourceId, int $amount): int
    {
        $member = PipeSystemMember::find()
            ->where(['entity_id' => $pipeEntityId])
            ->one();

        if (!$member) return 0;

        $system = PipeSystem::findOne($member->pipe_system_id);

        // Проверка типа ресурса
        if ($system->resource_id != $resourceId) {
            return 0; // Не тот ресурс
        }

        // Забираем сколько можем
        $taken = min($amount, $system->current_amount);
        $system->current_amount -= $taken;

        // Если система опустела - очищаем resource_id
        if ($system->current_amount == 0) {
            $system->resource_id = null;
        }

        $system->save();

        return $taken;
    }

    /**
     * Получить информацию о системе для tooltip
     */
    public static function getSystemInfo(int $pipeEntityId): ?array
    {
        $member = PipeSystemMember::find()
            ->where(['entity_id' => $pipeEntityId])
            ->one();

        if (!$member) return null;

        $system = PipeSystem::findOne($member->pipe_system_id);

        return [
            'resource_id' => $system->resource_id,
            'resource_name' => $system->resource ? $system->resource->name : 'Empty',
            'current_amount' => $system->current_amount,
            'max_capacity' => $system->max_capacity,
            'fill_percent' => ($system->max_capacity > 0)
                ? round(($system->current_amount / $system->max_capacity) * 100, 1)
                : 0,
        ];
    }
}
```

#### 3.2 EntityType классы для труб

**Файл:** `src/bl/entity/types/PipeEntityType.php`

```php
<?php
namespace bl\entity\types;

abstract class PipeEntityType extends AbstractEntityType
{
    public function getTypeCategory(): string
    {
        return 'pipe';
    }

    public function getCapacity(): int
    {
        return $this->power ?? 100;
    }

    public function canAcceptFluid(int $resourceId): bool
    {
        // Проверка через PipeSystemManager
        return true;
    }
}
```

#### 3.3 Хуки при создании/удалении труб

**В CreateEntity.php:**
```php
use bl\pipes\PipeSystemManager;

// После создания трубы
if ($entityType->type === 'pipe') {
    PipeSystemManager::recalculateSystems($regionId);
}
```

**В DeleteEntity.php:**
```php
// После удаления трубы
if ($entityType->type === 'pipe') {
    PipeSystemManager::recalculateSystems($regionId);
}
```

---

### 4. FRONTEND (JavaScript)

#### 4.1 Новый модуль: PipeSystemManager.js

**Файл:** `resources/js/modules/pipes/PipeSystemManager.js`

```javascript
export class PipeSystemManager {
    constructor(game) {
        this.game = game;
        this.systems = new Map(); // pipe_system_id => system data
        this.entityToSystem = new Map(); // entity_id => pipe_system_id
    }

    loadSystems(systemsData) {
        this.systems.clear();
        this.entityToSystem.clear();

        for (const system of systemsData) {
            this.systems.set(system.pipe_system_id, system);

            // Маппинг entity → system
            for (const entityId of system.entity_ids) {
                this.entityToSystem.set(entityId, system.pipe_system_id);
            }
        }
    }

    getSystemForEntity(entityId) {
        const systemId = this.entityToSystem.get(entityId);
        return systemId ? this.systems.get(systemId) : null;
    }

    getSystemInfo(entityId) {
        const system = this.getSystemForEntity(entityId);
        if (!system) return null;

        const resource = this.game.resources[system.resource_id];

        return {
            resourceName: resource ? resource.name : 'Empty',
            currentAmount: system.current_amount,
            maxCapacity: system.max_capacity,
            fillPercent: system.max_capacity > 0
                ? Math.round((system.current_amount / system.max_capacity) * 100)
                : 0
        };
    }
}
```

#### 4.2 Рендеринг труб с жидкостью

**Файл:** `resources/js/modules/rendering/PipeRenderer.js`

```javascript
import * as PIXI from 'pixi.js';

export class PipeRenderer {
    constructor(game) {
        this.game = game;
        this.fluidColors = {
            300: 0x3498db, // Water - синий
            301: 0x2c3e50, // Crude Oil - чёрный
            302: 0x95a5a6, // Natural Gas - серый
            303: 0xe74c3c, // Lava - красный
        };
    }

    createPipeSprite(entity) {
        const container = new PIXI.Container();

        // Основной спрайт трубы
        const texture = this.game.textures[`entity_${entity.entity_type_id}_normal`];
        const pipeSprite = new PIXI.Sprite(texture);

        // Добавляем визуализацию жидкости
        const system = this.game.pipeSystemManager.getSystemForEntity(entity.entity_id);
        if (system && system.resource_id) {
            const fluidGraphics = this.createFluidGraphics(system.resource_id);
            container.addChild(fluidGraphics);
        }

        container.addChild(pipeSprite);

        return container;
    }

    createFluidGraphics(resourceId) {
        const graphics = new PIXI.Graphics();
        const color = this.fluidColors[resourceId] || 0xffffff;

        // Рисуем прямоугольник жидкости в окошке (12x12px, центр 32,32)
        graphics.beginFill(color);
        graphics.drawRect(26, 26, 12, 12);
        graphics.endFill();

        return graphics;
    }
}
```

#### 4.3 Тултип для труб

**В EntityTooltip.js:**
```javascript
generatePipeTooltip(entity) {
    const info = this.game.pipeSystemManager.getSystemInfo(entity.entity_id);

    if (!info) {
        return 'Pipe System\nNot connected';
    }

    return `Pipe System
Resource: ${info.resourceName}
Amount: ${info.currentAmount} / ${info.maxCapacity}
Fill: ${info.fillPercent}%`;
}
```

---

### 5. КОНСОЛЬНЫЕ КОМАНДЫ

#### 5.1 Генерация спрайтов труб

**Файл:** `src/commands/PipeController.php`

```php
<?php
namespace commands;

use yii\console\Controller;

class PipeController extends Controller
{
    /**
     * Генерирует базовый спрайт горизонтальной трубы
     */
    public function actionGenerateBase()
    {
        $img = imagecreatetruecolor(64, 64);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));

        $gray = imagecolorallocate($img, 90, 90, 90); // #5A5A5A

        // Труба (x=22 to x=42, ширина 20px)
        imagefilledrectangle($img, 22, 22, 42, 42, $gray);

        // Прозрачное окошко (x=26 to x=38, y=26 to y=38, 12x12px)
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefilledrectangle($img, 26, 26, 38, 38, $transparent);

        // Сохранить
        $path = Yii::getAlias('@app/../public/assets/tiles/entities/pipe/normal.png');
        imagepng($img, $path);
        imagedestroy($img);

        echo "Базовый спрайт создан: $path\n";
    }

    /**
     * Генерирует все варианты соединений (16 типов)
     */
    public function actionGenerateConnectionVariants()
    {
        // TODO: Реализовать генерацию 16 вариантов соединений
        // используя алгоритм комбинирования четвертинок
    }

    /**
     * Пересчитать все системы труб в регионе
     */
    public function actionRecalculateSystems($regionId = 1)
    {
        \bl\pipes\PipeSystemManager::recalculateSystems($regionId);
        echo "Системы труб пересчитаны для региона $regionId\n";
    }
}
```

---

## ТЕСТОВЫЕ СЦЕНАРИИ

### 1. Простая система из 5 труб
- Построить 5 труб в линию: A-B-C-D-E
- Качалка A добавляет 100 единиц воды
- Здание E сразу может забрать эти 100 единиц
- Проверить тултип: показывает общий объем 500 (5 труб × 100)

### 2. Две несвязанные системы
- Система 1: трубы A-B-C (300 capacity)
- Система 2: трубы X-Y (200 capacity)
- Добавить воду в систему 1 → система 2 остается пустой
- Проверить разные ресурсы в разных системах

### 3. Запрет смешивания
- Система с водой (100 единиц)
- Попытка добавить нефть → блокировка
- Разобрать трубы → построить заново → теперь можно добавить нефть

### 4. Резервуары
- Построить 1 трубу (power=100) + 1 резервуар (power=5000)
- Общая емкость системы = 5100
- Заполнить через качалку → проверить тултип

### 5. Подземные трубы
- Построить: труба → вход подземной трубы → (пустое пространство 5 тайлов) → выход подземной трубы → труба
- Проверить, что все соединено в одну систему
- Жидкость проходит через подземный участок

---

## ПОТЕНЦИАЛЬНЫЕ ПРОБЛЕМЫ

### 1. Производительность пересчета систем
**Проблема:** Пересчет BFS для большой карты может быть медленным
**Решение:**
- Кэшировать системы в памяти
- Пересчитывать только при создании/удалении труб
- Использовать инкрементальное обновление вместо полного пересчета

### 2. Визуализация окошка на разных ориентациях
**Проблема:** Окошко должно быть симметричным для всех вариантов соединений
**Решение:** Генерировать варианты так, чтобы окошко всегда оставалось в центре (32,32)

### 3. Синхронизация frontend/backend
**Проблема:** Клиент должен знать о системах труб для рендеринга
**Решение:** Отправлять данные систем в Config.php при загрузке игры

---

## ОЦЕНКА ВРЕМЕНИ

- **База данных (таблицы, миграции)**: 2-3 часа
- **Backend (PipeSystemManager, EntityType)**: 4-5 часов
- **Спрайты (генерация базового + варианты)**: 3-4 часа
- **Frontend (PipeSystemManager.js, рендеринг)**: 3-4 часа
- **Качалки жидкостей (4 типа)**: 2-3 часа
- **Тестирование и отладка**: 3-4 часа

**ИТОГО**: ~17-23 часа

---

## ПРИОРИТЕТЫ РЕАЛИЗАЦИИ

### Фаза 1 (MVP):
1. ✅ База данных (pipe_system, pipe_system_member)
2. ✅ Базовый entity_type для труб (горизонтальная + вертикальная)
3. ✅ PipeSystemManager backend (BFS, add/take fluid)
4. ✅ Один ресурс (вода) + одна качалка (water pump)
5. ✅ Frontend рендеринг труб с жидкостью

### Фаза 2 (Расширение):
6. ⚠️ Остальные жидкости (нефть, газ, магма) + качалки
7. ⚠️ Резервуары (маленький + большой)
8. ⚠️ Подземные трубы
9. ⚠️ Автогенерация вариантов соединений (16 типов)

### Фаза 3 (Полировка):
10. ⚠️ Анимация течения жидкости (опционально)
11. ⚠️ Звуки работы качалок
12. ⚠️ Рецепты с жидкостями для зданий
