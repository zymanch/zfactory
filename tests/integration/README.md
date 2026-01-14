# Game Simulation Integration Tests

Интеграционные тесты для полных игровых систем с использованием **data provider** подхода и **ASCII карт**.

## 🎯 Что это?

Система позволяет тестировать полные игровые циклы:
- ✅ **Конвейеры** - движение ресурсов между entities
- 🚧 **Манипуляторы** - transfer ресурсов
- 🚧 **Крафт** - производство в печах
- 🚧 **Сплиттеры** - разделение потоков
- 🚧 **Электричество** - сети электричества
- 🚧 **Трубы** - транспорт жидкостей

## 📝 Как работает

### 1. ASCII Карта

Определяем layout entities как ASCII строки:

```javascript
map: [
    '12#',
    '###'
]
```

- `#` = пустая клетка
- Цифры/буквы = entities
- Каждая клетка = 1 пиксель (см. замечания ниже)

### 2. Легенда

Описываем что означает каждый символ:

```javascript
legend: {
    '1': {
        type: 'conveyor_right',
        resources: [{ id: 1, amount: 1 }]  // Начальные ресурсы
    },
    '2': {
        type: 'conveyor_right'
    }
}
```

### 3. Рецепты (Recipes)

Есть три способа указать рецепты:

**A. Inline рецепты (рекомендуется)**
```javascript
'1': {
    type: 'furnace',
    recipes: [
        // Один входной ресурс
        {
            input: { resource: 'iron_ore', amount: 1 },
            output: { resource: 'iron_plate', amount: 1 },
            ticks: 30,
            name: 'Iron Smelting'  // опционально
        },
        // Несколько входных ресурсов
        {
            inputs: [
                { resource: 'iron_plate', amount: 2 },
                { resource: 'copper_plate', amount: 1 }
            ],
            output: { resource: 'steel', amount: 1 },
            ticks: 60
        }
    ],
    recipe: 0  // Индекс в массиве recipes
}
```

**B. По ID (для существующих рецептов)**
```javascript
'1': {
    type: 'furnace',
    recipes: [1],  // recipe_id = 1
    recipe: 1  // Или: recipe: 'iron_smelting'
}
```

**C. По имени (для default рецептов)**
```javascript
'1': {
    type: 'furnace',
    recipe: 'iron_smelting'  // Использует getDefaultRecipes()
}
```

### 4. Симуляция

Запускаем N тиков и проверяем результат:

```javascript
ticks: 120,  // Симулировать 120 игровых тиков
expectedState: {
    entityResources: {
        2: { 1: 1 }  // Entity 2 должен иметь 1 железную руду
    }
}
```

## 🎯 Ключевые исправления

### Проблемы найденные и решенные:

1. **Orientation манипуляторов и сплиттеров**: MapBuilder не устанавливал поле `orientation`, необходимое для работы links
2. **Power манипуляторов**: Был установлен в 10 вместо 100, что делало движение очень медленным
3. **Target для конвейеров**: Конвейеры устанавливали манипуляторов как target, но манипуляторы берут ресурсы из source
4. **EntityData keys**: В тестах использовались числовые ключи, а код ожидал строковые (`entity_${id}`)
5. **BuildingState.inputResourceIds**: Печи без рецептов не принимали ресурсы

## 🔧 Инфраструктура

### MapBuilder.js

Парсит ASCII карту и создает entities:

```javascript
const state = MapBuilder.build(mapLines, legend, recipes);
// Returns: { entities, entityTypes, resources, recipes, entityResources, ... }
```

### GameSimulator.js

Запускает game ticks без rendering:

```javascript
const game = createGameInstance(state);
await initializeManagers(game);
const finalState = runSimulation(game, 120);

// Assert
assertEntityResources(finalState, 2, { 1: 1 });
```

## ⚠️ ВАЖНЫЕ ЗАМЕЧАНИЯ

### Grid Size = 1 пиксель!

**КРИТИЧНО**: Entities должны быть на расстоянии **1 пиксель** друг от друга, а не 64!

```javascript
// ❌ НЕ РАБОТАЕТ (gridSize = 64)
// Entity 1 at (0, 0), Entity 2 at (64, 0) - не найдет связь!

// ✅ РАБОТАЕТ (gridSize = 1)
// Entity 1 at (0, 0), Entity 2 at (1, 0) - правильно!
```

**Причина**: `ResourceTransportManager.getNextPosition()` использует `distance=1` по умолчанию.

### Power конвейеров

Скорость движения зависит от `power`:

```javascript
speed_px_per_tick = (power / 100) * (tileWidth / 60)

// power=100, tileWidth=64 → speed ≈ 1.07 px/tick
// Чтобы пройти 64px нужно ~60 тиков
```

**Стандартное значение**: `power: 100`

### Необходимое количество тиков

**Формула**: Для N конвейеров нужно **~90 * N тиков**

Примеры из работающих тестов:
- **2 конвейера**: 120 тиков (фактически хватает 90+)
- **4 конвейера**: 300 тиков (наблюдается transfer на tick 91, 181, 271)

**Breakdown**:
- ~60 тиков для движения ресурса по конвейеру (position_px: -32 → +32)
- ~30 тиков для logic tick и transfer
- Итого: **~90 тиков на 1 конвейер**

### Ориентация конвейеров

ОБЯЗАТЕЛЬНО устанавливать `orientation` в entity type:

```javascript
{
    type: 'conveyor_right',  // Имя типа
    // MapBuilder автоматически извлекает orientation из имени
    // 'conveyor_right' → orientation: 'right'
}
```

## 📊 Текущий статус

### ✅ Работающие тесты (9/13)

| Test | Status | Ticks | Notes |
|------|--------|-------|-------|
| ✅ Simple conveyor transport | PASS | 120 | Ресурс движется из A в B |
| ✅ Conveyor chain (4 conveyors) | PASS | 300 | Ресурс проходит через всю цепочку |
| ✅ Furnace smelting | PASS | 900 | Печь плавит руду в слиток |
| ✅ Manipulator transfer | PASS | 1200 | Манипулятор переносит ресурс из конвейера в печь |
| ✅ Multi-direction conveyors | PASS | 300 | L-образный путь работает |
| ✅ Multiple furnaces | PASS | 900 | Параллельный крафт работает |
| ✅ Splitter basic | PASS | 300 | Сплиттер распределяет ресурсы |
| ✅ Circular conveyors | PASS | 180 | Ресурсы вращаются по кругу |
| ✅ Custom assertions | PASS | 120 | getEntityResources работает |

### 🟡 Требуют архитектурных изменений (4/13)

| Feature | Status | Missing | Notes |
|---------|--------|---------|-------|
| 🚧 Dual conveyors | SKIP | Multiple resources/conveyor | TransporterState держит 1 ресурс, нужен массив items |
| 🚧 Production chain | SKIP | Mining drill support | Нужна логика добычи ресурсов |
| 🚧 Buffer storage | SKIP | Multiple items/conveyor | Очередь на конвейере (связано с dual) |
| 🚧 Complex production | SKIP | Pipes + Mining + Multi-input recipes | Комплексная интеграция (трубы, добыча, рецепты с 3 входами) |

## 🚀 Запуск тестов

```bash
# Все интеграционные тесты
npm test gameSimulation

# С UI
npm run test:ui
```

## 📖 Примеры

### Работающий тест

```javascript
{
    name: 'Simple conveyor transport',
    map: ['12#'],
    legend: {
        '1': { type: 'conveyor_right', resources: [{ id: 1, amount: 1 }] },
        '2': { type: 'conveyor_right' }
    },
    ticks: 120,
    expectedState: {
        entityResources: { 2: { 1: 1 } }
    }
}
```

### Рабочий тест с печью

```javascript
{
    name: 'Furnace smelting',
    map: ['1#'],
    legend: {
        '1': {
            type: 'furnace',
            power: 100,
            // Inline recipe - более читаемо!
            recipes: [
                {
                    input: { resource: 'iron_ore', amount: 1 },
                    output: { resource: 'iron_plate', amount: 1 },
                    ticks: 30,
                    name: 'Iron Smelting'
                }
            ],
            recipe: 0  // Индекс рецепта из recipes
        }
    },
    ticks: 900,  // 30 logic ticks * 30 game ticks
    expectedState: {
        entityResources: { 1: { 2: 1 } },  // Iron plate
        craftingStates: { 1: null }  // Not crafting
    }
}
```

## 🔍 Debugging

Добавь логирование в runSimulation:

```javascript
runSimulation(game, 120, {
    logTicks: true,  // Логи каждые 10 тиков
    tickCallback: (tick, game) => {
        if (tick % 30 === 0) {
            console.log(`Tick ${tick}:`, extractGameState(game));
        }
    }
});
```

## 📚 Документация

- **[js-test-writer.md](../../.claude/agents/js-test-writer.md)** - Полное руководство агента
- **[MapBuilder.js](../helpers/MapBuilder.js)** - API для создания карт
- **[GameSimulator.js](../helpers/GameSimulator.js)** - API для симуляции
