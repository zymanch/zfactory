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

### 3. Симуляция

Запускаем N тиков и проверяем результат:

```javascript
ticks: 120,  // Симулировать 120 игровых тиков
expectedState: {
    entityResources: {
        2: { 1: 1 }  // Entity 2 должен иметь 1 железную руду
    }
}
```

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

### ✅ Работающие тесты (4/11)

| Test | Status | Ticks | Notes |
|------|--------|-------|-------|
| ✅ Simple conveyor transport | PASS | 120 | Ресурс движется из A в B |
| ✅ Conveyor chain (4 conveyors) | PASS | 300 | Ресурс проходит через всю цепочку |
| ✅ Multi-direction conveyors | PASS | 300 | L-образный путь работает |
| ✅ Custom assertions | PASS | 120 | getEntityResources работает |

### 🟡 Ожидают реализации (7/11)

| Feature | Status | Missing | Notes |
|---------|--------|---------|-------|
| 🚧 Furnace smelting | SKIP | BuildingState, crafting | Нужна поддержка крафта |
| 🚧 Manipulator transfer | SKIP | ManipulatorState | Нужен ManipulatorState.extractGameState |
| 🚧 Dual conveyors | SKIP | Multiple resources/conveyor | TransporterState держит 1 ресурс |
| 🚧 Splitter | SKIP | SplitterState | Нужен SplitterState.extractGameState |
| 🚧 Production chain | SKIP | Mining + Manipulator + Furnace | Комплексный тест |
| 🚧 Multiple furnaces | SKIP | BuildingState | Параллельный крафт |
| 🚧 Buffer storage | SKIP | Multiple items/conveyor | Очередь на конвейере |

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

### Будущий тест (когда реализуем furnaces)

```javascript
{
    name: 'Iron smelting',
    map: ['12##', '####'],
    legend: {
        '1': { type: 'conveyor_right', resources: [{ id: 1, amount: 1 }] },
        '2': { type: 'furnace', recipe: 'iron_smelting' }
    },
    ticks: 120,
    expectedState: {
        entityResources: { 2: { 2: 1 } },  // Iron plate
        craftingStates: { 2: null }  // Not crafting
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
