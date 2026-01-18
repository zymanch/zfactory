# Atlas Generation - Quick Start

## Для нового entity type

### 1. Подготовить спрайты

```bash
# Создать папку для entity
mkdir public/assets/tiles/entities/{type}/{folder}

# Поместить базовый спрайт:
# - Buildings: sprite.png (W×H tiles, 64px per tile)
# - Conveyors: animation.png (8 frames side-by-side, 64×64 each)
# - Pipes: sprite.png (64×64)
```

### 2. Добавить в базу

```sql
INSERT INTO entity_type (
    entity_type_id, type, subtype, name, image_url,
    extension, max_durability, width, height, ...
)
VALUES (
    150, 'conveyor', 'splitter', 'Y-Splitter', 'splitter',
    'png', 100, 1, 1, ...
);
```

### 3. Зарегистрировать провайдер (если новый subtype)

Если это новый subtype, создать провайдер в `src/bl/entity/atlas_providers/{type}/{Subtype}AtlasProvider.php`:

```php
<?php
namespace bl\entity\atlas_providers\conveyor;

use bl\entity\atlas_providers\base\AbstractAtlasProvider;
use bl\entity\atlas_generators\ConveyorAtlasGenerator;
use models\EntityType;

class SplitterAtlasProvider extends AbstractAtlasProvider
{
    public function getAtlasGenerators(EntityType $entityType): array
    {
        $animationGd = $this->getSourceSprite($entityType);
        // Определить какие атласы нужны
        return [
            'normal_atlas' => new ConveyorAtlasGenerator($animationGd, 'normal', 'right')
        ];
    }

    public function getSourceSprite(EntityType $entityType)
    {
        $path = $this->getSpritePath($entityType, 'animation.png');
        if (!file_exists($path)) {
            throw new \Exception("Animation not found: {$path}");
        }
        return imagecreatefrompng($path);
    }
}
```

Зарегистрировать в `AtlasProviderRegistry::init()`:

```php
self::register('conveyor', 'splitter', SplitterAtlasProvider::class);
```

### 4. Сгенерировать атласы

```bash
php yii atlas/generate --entity_type_id=150
```

---

## Для существующих entity types

### Миграция спрайтов (одноразово)

```bash
# Скопировать normal.png → sprite.png
php yii entity/migrate-sprites
```

### Генерация всех атласов

```bash
php yii atlas/generate-all
```

---

## Типовые провайдеры (уже реализованы)

| Type | Subtype | Provider | Атласы |
|------|---------|----------|--------|
| building | none | BuildingAtlasProvider | atlas.png (7×2) |
| conveyor | conveyor | ConveyorAtlasProvider | 5× atlas (16×8 каждый) |
| pipe | pipe | PipeAtlasProvider | 4× atlas (16×1 каждый) |
| manipulator | short/long | BuildingAtlasProvider | atlas.png (7×2) |
| mining | none | BuildingAtlasProvider | atlas.png (7×2) |
| storage | none | BuildingAtlasProvider | atlas.png (7×2) |
| eye | none | BuildingAtlasProvider | atlas.png (7×2) |

**По умолчанию**: Если провайдер не найден, используется `BuildingAtlasProvider`.

---

## Debugging

### Проверить что провайдер зарегистрирован

```bash
php yii shell

>>> use bl\entity\atlas_providers\AtlasProviderRegistry;
>>> use models\EntityType;
>>> AtlasProviderRegistry::init();
>>> $entity = EntityType::findOne(100);
>>> $provider = AtlasProviderRegistry::getProvider($entity);
>>> echo get_class($provider);
```

### Проверить размеры атласа

```bash
>>> $generators = $provider->getAtlasGenerators($entity);
>>> foreach ($generators as $name => $gen) {
...     echo "$name: " . json_encode($gen->getDimensions()) . "\n";
... }
```

### Проверить синтаксис

```bash
php -l src/bl/entity/atlas_providers/{type}/{Name}AtlasProvider.php
```

---

## Структура папок

```
src/bl/entity/
├── sprite_generators/          # Создание sprite.png, animation.png
│   ├── base/
│   │   ├── SpriteGeneratorInterface.php
│   │   ├── AbstractSpriteGenerator.php
│   │   └── ImageProcessor.php     # GD трансформации
│   ├── building/
│   ├── conveyor/
│   └── pipe/
│
├── atlas_providers/            # Определяют какие атласы нужны
│   ├── base/
│   │   ├── AtlasProviderInterface.php
│   │   └── AbstractAtlasProvider.php
│   ├── building/
│   │   └── BuildingAtlasProvider.php
│   ├── conveyor/
│   │   └── ConveyorAtlasProvider.php
│   ├── pipe/
│   │   └── PipeAtlasProvider.php
│   └── AtlasProviderRegistry.php  # Регистрация провайдеров
│
└── atlas_generators/           # Генерируют атласы из GD ресурсов
    ├── base/
    │   └── AtlasGeneratorInterface.php
    ├── EntityAtlasGenerator.php   # Универсальный (7×2)
    ├── ConveyorAtlasGenerator.php # Конвейеры (16×8)
    └── PipeAtlasGenerator.php     # Трубы (16×1)
```

---

## Common Issues

### "No atlas provider registered for {type}:{subtype}"

**Решение**: Зарегистрировать провайдер в `AtlasProviderRegistry::init()`

### "Sprite not found for entity_type_id=X"

**Решение**:
1. Проверить что `sprite.png` или `animation.png` существует в папке entity
2. Для rotational variants проверить что parent entity имеет спрайт
3. Запустить `php yii entity/migrate-sprites` если используете старую структуру

### Atlas не генерируется

**Решение**:
1. Проверить синтаксис провайдера: `php -l ...`
2. Проверить что provider зарегистрирован
3. Проверить права на запись в папку `public/assets/tiles/entities/`
4. Проверить логи: `runtime/logs/app.log`

---

## See Also

- **Полная документация**: docs/ATLAS_GENERATION.md
- **Database schema**: docs/DATABASE.md (секция entity_type с subtype)
- **Commands**: src/commands/AtlasController.php, EntityController.php
