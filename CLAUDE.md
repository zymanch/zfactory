# Project CLAUDE.md

## Auto-load Documentation

**ОБЯЗАТЕЛЬНО**: В начале каждой сессии прочитай ВСЮ документацию проекта:

```
docs/PROJECT.md      - Описание проекта, структура, команды
docs/DATABASE.md     - Схема БД, таблицы, поля
docs/GAME_ENGINE.md  - PixiJS, камера, рендеринг, API
docs/ADMIN.md        - Admin Panel: редактор карты, депозиты, регионы
```

Используй Read tool для всех файлов в папке `docs/` перед началом работы.

## Quick Reference

### Database Tables
| Table       | Description                          |
|-------------|--------------------------------------|
| landing     | Типы тайлов ландшафта (10 типов)     |
| map         | Экземпляры тайлов (~6251, floating island) |
| entity_type | Типы объектов (20 типов)             |
| entity      | Экземпляры объектов на карте (~313)  |

### Floating Islands
- Карта имеет форму парящего острова с неровными краями
- `island_edge` (landing_id=10) - авто-генерируется под краями острова
- Sky (landing_id=9) - фон под островом

### Entity Fields
- `state`: 'built' | 'blueprint'
- `durability`: 0 to max_durability
- `x`, `y`: pixel coordinates

### Sprite States (5 per entity type)
```
public/assets/tiles/entities/{folder}/
├── normal.svg
├── damaged.svg         (durability < 50%)
├── blueprint.svg       (state = 'blueprint')
├── normal_selected.svg (hover)
└── damaged_selected.svg
```

### Key Files
- `resources/js/game.js` - Game engine source
- `src/actions/` - Standalone action classes
- `src/controllers/` - Thin controllers (use actions() method)
- `src/migrations/` - Database migrations

### Standalone Actions Pattern
Все екшены вынесены в отдельные классы в `src/actions/{controller}/`:
- Наследуются от `actions\Base` (view) или `actions\JsonAction` (API)
- Регистрируются в контроллере через `actions()` метод
- При создании нового екшена - сразу создавать отдельный класс

### SQL Files (ВАЖНО: обновляй ОБА при изменениях!)
- `docs/database.sql` - Структуры таблиц + данные landing, entity_type
- `docs/map.sql` - Данные entity и map

### Commands
```bash
npm run assets          # Build JS/CSS
php yii migrate         # Run migrations
composer run ar         # Generate models
```
