# Atlas Generation System

## Обзор

Система генерации атласов для entity использует **Provider Pattern** для унификации и устранения хардкода entity_type_id.

### Ключевые компоненты

```
sprite_generators/     - Генерируют спрайты (sprite.png, animation.png)
atlas_providers/       - Определяют какие атласы нужны для типа
atlas_generators/      - Генерируют атласы из спрайтов
```

## Архитектура

### 1. Sprite Generators (src/bl/entity/sprite_generators/)

**Задача**: Создание базовых спрайтов (sprite.png, animation.png) из исходников.

**Интерфейс**: `SpriteGeneratorInterface`
```php
interface SpriteGeneratorInterface {
    public function generate(EntityType $entityType, bool $testMode = false): bool;
    public function generateStates(EntityType $entityType): bool;
    public function supports(EntityType $entityType): bool;
}
```

**Примеры**:
- `ConveyorSpriteGenerator` - создает animation.png для конвейеров
- `PipeSpriteGenerator` - создает sprite.png для труб
- `FurnaceSpriteGenerator` - создает sprite.png для печей

**Важно**: Sprite generators работают ТОЛЬКО с файлами (загрузка/сохранение). Все обработки делегируются ImageProcessor.

---

### 2. Image Processor (src/bl/entity/sprite_generators/base/ImageProcessor.php)

**Задача**: Трансформации изображений (damaged, blueprint, selected, rotation).

**Принцип**: Все методы работают с **GD ресурсами**, НЕ с файлами.

**Ключевые методы**:
```php
public static function removeBackground($img, int $brightnessThreshold = 200): resource;
public static function scaleImage($img, int $targetWidth, int $targetHeight): resource;
public static function createDamaged($src): resource;
public static function createBlueprint($src, ?string $orientation = null): resource;
public static function createSelected($src): resource;
public static function rotateImage($src, int $angle): resource;
```

**Caller ответственен за**:
- Загрузку файлов (`imagecreatefrompng()`)
- Сохранение результатов (`imagepng()`)
- Освобождение памяти (`imagedestroy()`)

---

### 3. Atlas Providers (src/bl/entity/atlas_providers/)

**Задача**: Определить какие атласы нужны для конкретного entity_type.

**Интерфейс**: `AtlasProviderInterface`
```php
interface AtlasProviderInterface {
    public function getAtlasGenerators(EntityType $entityType): array;
    public function getSourceSprite(EntityType $entityType);
}
```

**Регистрация**: `AtlasProviderRegistry`
```php
AtlasProviderRegistry::init();
AtlasProviderRegistry::register('building', null, BuildingAtlasProvider::class);
AtlasProviderRegistry::register('conveyor', 'conveyor', ConveyorAtlasProvider::class);
AtlasProviderRegistry::register('pipe', 'pipe', PipeAtlasProvider::class);

$provider = AtlasProviderRegistry::getProvider($entityType);
```

**Примеры провайдеров**:

#### BuildingAtlasProvider
Один атлас с 2 рядами:
- Ряд 1: 7 состояний (normal, damaged, blueprint, normal_selected, damaged_selected, deleting, crafting)
- Ряд 2: 9 кадров строительства (10%, 20%, ..., 90%)

```php
public function getAtlasGenerators(EntityType $entityType): array
{
    $sprite = $this->getSourceSprite($entityType);
    return [
        'atlas' => new EntityAtlasGenerator($sprite, $entityType->width, $entityType->height)
    ];
}
```

#### ConveyorAtlasProvider
5 атласов для разных состояний (каждый 16×8):
- normal_atlas.png
- damaged_atlas.png
- blueprint_atlas.png
- normal_selected_atlas.png
- damaged_selected_atlas.png

```php
public function getAtlasGenerators(EntityType $entityType): array
{
    $animationGd = $this->getSourceSprite($entityType);
    $states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];
    $generators = [];

    foreach ($states as $state) {
        $atlasName = "{$state}_atlas";
        $generators[$atlasName] = new ConveyorAtlasGenerator(
            $animationGd,
            $state,
            $entityType->orientation
        );
    }

    return $generators;
}
```

#### PipeAtlasProvider
4 атласа для разных состояний (каждый 16×1):
- pipe_atlas_normal.png
- pipe_atlas_normal_selected.png
- pipe_atlas_damaged.png
- pipe_atlas_damaged_selected.png

```php
public function getAtlasGenerators(EntityType $entityType): array
{
    $spriteGd = $this->getSourceSprite($entityType);
    $states = ['normal', 'normal_selected', 'damaged', 'damaged_selected'];
    $generators = [];

    foreach ($states as $state) {
        $atlasName = "pipe_atlas_{$state}";
        $generators[$atlasName] = new PipeAtlasGenerator($spriteGd, $state);
    }

    return $generators;
}
```

---

### 4. Atlas Generators (src/bl/entity/atlas_generators/)

**Задача**: Создать атлас из GD ресурса спрайта.

**Интерфейс**: `AtlasGeneratorInterface`
```php
interface AtlasGeneratorInterface {
    public function generate(); // Returns GD resource
    public function getDimensions(): array;
}
```

**Важно**: Atlas generators работают ТОЛЬКО с **GD ресурсами** (НЕ файлами).

**Примеры**:

#### EntityAtlasGenerator
Создает 2-рядный атлас для buildings:
- Принимает: `sprite.png` (GD resource)
- Возвращает: atlas (GD resource)
- Размер: `(max(7, 9) * width) × (2 * height)`

```php
$sprite = imagecreatefrompng('path/to/sprite.png');
$generator = new EntityAtlasGenerator($sprite, 3, 3); // 3×3 tiles
$atlasGd = $generator->generate();
imagepng($atlasGd, 'path/to/atlas.png');
imagedestroy($sprite);
imagedestroy($atlasGd);
```

#### ConveyorAtlasGenerator
Создает атлас с вариантами подключений (16) и кадрами анимации (8):
- Принимает: `animation.png` (GD resource, 8 кадров side-by-side)
- Возвращает: atlas (GD resource)
- Размер: `(16 * 64) × (8 * 64)` = 1024×512

```php
$animation = imagecreatefrompng('path/to/animation.png');
$generator = new ConveyorAtlasGenerator($animation, 'normal', 'right');
$atlasGd = $generator->generate();
imagepng($atlasGd, 'path/to/normal_atlas.png');
imagedestroy($animation);
imagedestroy($atlasGd);
```

#### PipeAtlasGenerator
Создает атлас с вариантами подключений (16):
- Принимает: `sprite.png` (GD resource)
- Возвращает: atlas (GD resource)
- Размер: `(16 * 64) × 64` = 1024×64

```php
$sprite = imagecreatefrompng('path/to/sprite.png');
$generator = new PipeAtlasGenerator($sprite, 'normal');
$atlasGd = $generator->generate();
imagepng($atlasGd, 'path/to/pipe_atlas_normal.png');
imagedestroy($sprite);
imagedestroy($atlasGd);
```

---

## Колонка `subtype` в entity_type

Для устранения хардкода entity_type_id добавлена колонка `subtype`:

```sql
ALTER TABLE entity_type
ADD COLUMN subtype VARCHAR(64) NULL DEFAULT NULL AFTER type;
```

### Mapping типов

| type | subtype | entity_type_id | Описание |
|------|---------|----------------|----------|
| conveyor | conveyor | 100, 120-122 | Обычный конвейер |
| conveyor | underground_in | - | Вход подземного конвейера |
| conveyor | underground_out | - | Выход подземного конвейера |
| conveyor | splitter | - | Y-разветвитель |
| pipe | pipe | 131-132 | Обычная труба |
| pipe | underground_pipe | - | Подземная труба |
| electricity | pylon | 900-902 | Электрические столбы |
| electricity | battery | 910-912 | Батареи |
| electricity | generator | 920-922 | Генераторы |
| manipulator | short | 200, 210-212 | Короткий манипулятор |
| manipulator | long | 201, 213-215 | Длинный манипулятор |
| building | none | 101, 103-107 | Здания |
| mining | none | 102, 108, 500-512 | Добывающие |
| storage | none | 104 | Хранилища |
| eye | none | 400-402 | Башни обзора |

**Преимущества**:
- Нет хардкода entity_type_id в коде
- Провайдеры регистрируются по `type:subtype`
- Легко добавлять новые подтипы

---

## Команды

### Миграция спрайтов

Копирует `normal.png` → `sprite.png` для всех entity types:

```bash
php yii entity/migrate-sprites
```

**Логика**:
- Пропускает rotational variants (parent_entity_type_id != NULL)
- Пропускает если sprite.png уже существует
- Копирует только base entities

### Генерация атласов

Генерирует все атласы для всех entity types:

```bash
php yii atlas/generate-all
```

Генерирует атласы для конкретного entity:

```bash
php yii atlas/generate --entity_type_id=100
```

**Логика**:
1. Инициализирует AtlasProviderRegistry
2. Для каждого entity_type:
   - Получает провайдер через registry
   - Получает список генераторов от провайдера
   - Генерирует каждый атлас
   - Сохраняет в папку entity

---

## Структура файлов спрайтов

### Новая структура (после миграции)

```
public/assets/tiles/entities/
├── conveyor/
│   ├── sprite.png              (base sprite, 64×64)
│   ├── animation.png           (8 frames, 512×64)
│   ├── normal_atlas.png        (generated, 1024×512)
│   ├── damaged_atlas.png       (generated, 1024×512)
│   ├── blueprint_atlas.png     (generated, 1024×512)
│   ├── normal_selected_atlas.png
│   └── damaged_selected_atlas.png
├── building/furnace/
│   ├── sprite.png              (base sprite, 128×128)
│   └── atlas.png               (generated, 7×128 × 2×128)
└── pipe/
    ├── sprite.png              (base sprite, 64×64)
    ├── pipe_atlas_normal.png   (generated, 1024×64)
    ├── pipe_atlas_damaged.png
    ├── pipe_atlas_normal_selected.png
    └── pipe_atlas_damaged_selected.png
```

### Старая структура (deprecated)

```
public/assets/tiles/entities/
├── conveyor/
│   ├── normal.png          → DEPRECATED (use sprite.png)
│   ├── damaged.png         → DEPRECATED (generated via atlas)
│   ├── blueprint.png       → DEPRECATED (generated via atlas)
│   └── ...
```

---

## Workflow: Добавление нового entity type

### 1. Создание спрайта

```bash
# Создать папку
mkdir public/assets/tiles/entities/{type}/{folder}

# Поместить базовый спрайт
# - Для buildings: sprite.png
# - Для conveyors: sprite.png + animation.png (8 frames)
# - Для pipes: sprite.png
```

### 2. Добавление в базу

```sql
INSERT INTO entity_type (entity_type_id, type, subtype, name, image_url, ...)
VALUES (150, 'conveyor', 'underground_in', 'Underground Belt In', 'underground_in', ...);
```

### 3. Создание провайдера (если новый subtype)

```php
// src/bl/entity/atlas_providers/conveyor/UndergroundAtlasProvider.php
class UndergroundAtlasProvider extends AbstractAtlasProvider
{
    public function getAtlasGenerators(EntityType $entityType): array
    {
        // ... определить какие атласы нужны
    }
}

// Зарегистрировать в AtlasProviderRegistry::init()
self::register('conveyor', 'underground_in', UndergroundAtlasProvider::class);
```

### 4. Генерация атласов

```bash
php yii atlas/generate --entity_type_id=150
```

---

## Тестирование

### Проверка синтаксиса

```bash
php -l src/bl/entity/atlas_providers/AtlasProviderRegistry.php
php -l src/bl/entity/atlas_generators/EntityAtlasGenerator.php
php -l src/commands/AtlasController.php
```

### Проверка генерации

```bash
# Генерация всех атласов
php yii atlas/generate-all

# Проверка результата
find public/assets/tiles/entities -name "*atlas*.png"
```

### Проверка провайдера

```php
use bl\entity\atlas_providers\AtlasProviderRegistry;
use models\EntityType;

AtlasProviderRegistry::init();

$entityType = EntityType::findOne(100); // Conveyor
$provider = AtlasProviderRegistry::getProvider($entityType);
$generators = $provider->getAtlasGenerators($entityType);

foreach ($generators as $name => $generator) {
    echo "$name: " . json_encode($generator->getDimensions()) . "\n";
}
```

---

## FAQ

### Q: Почему ImageProcessor не работает с файлами?

**A**: Разделение ответственности. ImageProcessor - это low-level утилита для трансформаций GD ресурсов. File I/O делегируется caller'у (provider/generator), что позволяет:
- Работать в памяти без промежуточных файлов
- Легко тестировать (mock GD ресурсы)
- Переиспользовать в разных контекстах

### Q: Зачем нужны и Provider и Generator?

**A**: Разные уровни абстракции:
- **Provider**: "Какие атласы нужны для этого типа?" (логика высокого уровня)
- **Generator**: "Как создать конкретный атлас из спрайта?" (алгоритм low-level)

Provider может создавать несколько generators для разных состояний. Generator - это переиспользуемый алгоритм.

### Q: Как добавить новый state (например, 'frozen')?

**A**:
1. Добавить метод в ImageProcessor:
   ```php
   public static function createFrozen($src): resource {
       // ... применить голубой tint
   }
   ```

2. Обновить EntityAtlasGenerator::generateStateSprite():
   ```php
   case 'frozen':
       return ImageProcessor::createFrozen($this->spriteGd);
   ```

3. Добавить 'frozen' в массив $states

4. Регенерировать атласы

### Q: Почему ConveyorAtlasGenerator создает 16 вариантов?

**A**: 16 вариантов = 4-битная маска подключений (up, down, left, right):
- Variant 0 (0000): нет подключений
- Variant 1 (0001): right
- Variant 2 (0010): down
- Variant 3 (0011): right + down
- ...
- Variant 15 (1111): все 4 стороны

Каждый вариант - это визуальная комбинация углов/поворотов.

---

## Changelog

### v2.0 (Jan 2026) - Provider Pattern
- ✅ Добавлена колонка `subtype` в entity_type
- ✅ Создана архитектура providers/generators
- ✅ Рефакторинг ImageProcessor (GD resources)
- ✅ Переименование `generators` → `sprite_generators`
- ✅ Создание команд миграции и генерации

### v1.0 (Legacy) - Hardcoded IDs
- ❌ entity_type_id хардкод в контроллерах
- ❌ Логика генерации в контроллерах (ConveyorController, PipeController)
- ❌ ImageProcessor работал с файлами
- ❌ Нет единого интерфейса
