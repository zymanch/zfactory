# Project CLAUDE.md

## Documentation Strategy

### 📘 Full Documentation (docs/)

```
docs/PROJECT.md      - Project overview, structure, commands
docs/DATABASE.md     - Complete DB schema, tables, fields
docs/GAME_ENGINE.md  - PixiJS engine, camera, rendering, API
docs/ADMIN.md        - Admin panel: map editor, deposits, regions
```

### 🎯 When to Read Full Documentation

**Working WITHOUT agents (regular mode):**
- **ОБЯЗАТЕЛЬНО**: Read ALL documentation at session start
- Use Read tool for all files in `docs/` folder

**Working WITH agents (agent mode):**
- Agents already contain necessary context for their domain
- Read full docs only when:
  - Task spans multiple agent domains (entity + recipe + sprites)
  - Non-standard cases outside agent workflows
  - Need to study complete system architecture
  - Debugging complex cross-system issues

**Examples requiring docs even with agents:**
```
❌ "entity-architect - create furnace"
   → Agent has enough context, no docs needed

✅ "Implement new resource system affecting entities, recipes, sprites, and API"
   → Read docs/DATABASE.md, docs/GAME_ENGINE.md (cross-functional)

✅ "Why does fog of war interact with electricity system?"
   → Read docs/GAME_ENGINE.md (architecture understanding)
```

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

## Project Agents

Проект содержит специализированных агентов в `.claude/agents/`:

| Agent | Назначение | Контекст внутри |
|-------|-----------|-----------------|
| **entity-architect** | Создание новых entity types | DB schema, PHP classes, behaviors, sprites workflow |
| **recipe-balancer** | Балансировка экономики | Production chains, rate calculations, balance formulas |
| **ai-sprite-wizard** | Генерация спрайтов FLUX.1 | ComfyUI API, prompt patterns, atlas generation |
| **pixi-renderer** | Оптимизация PixiJS | Layer system, batching, culling, effects |
| **game-mechanic** | Разработка механик | Network algorithms, manager patterns, integration |
| **js-test-writer** | JavaScript тестирование | Vitest setup, mocking patterns, coverage |
| **maria** | Database optimization | Schema design, query optimization, MariaDB tuning |

### What Agents Contain

**Embedded in each agent:**
- ✅ Domain-specific context (their area only)
- ✅ Workflows and patterns ("how to do")
- ✅ Rules and best practices
- ✅ Integration points with other agents
- ✅ Quick reference for their domain

**NOT in agents (use docs/ for):**
- ❌ Complete database schema (all tables, all fields)
- ❌ All API endpoints with full specs
- ❌ Complete file structure (all files, all classes)
- ❌ Historical context and architecture decisions
- ❌ Cross-domain complete workflows

### Usage

**Activate agent:**
```
Действуй как entity-architect в этой сессии
Загрузи recipe-balancer агента
ai-sprite-wizard сессия
```

**Agent modes:**
- **Quick Mode** (0-20 iterations): Work in current session, memory indicator at end
- **Persistent Mode** (20+ iterations): Task tool with history in T-XXX file

**Read .claude/README.md for complete agent guide**

### Decision Tree: Docs vs Agents

```
Question: Do I need full documentation?

├─ Working on single-domain task? (entity creation, balance, sprites, tests)
│  └─ NO DOCS → Use specialized agent (entity-architect, recipe-balancer, etc.)
│
├─ Task involves 2+ domains? (entity + recipes, mechanic + rendering, etc.)
│  └─ PARTIAL DOCS → Agent + relevant docs sections
│
├─ Architecture question? (why systems interact certain way, design decisions)
│  └─ FULL DOCS → Read docs/GAME_ENGINE.md, docs/PROJECT.md
│
├─ Database-wide changes? (new tables, schema refactoring)
│  └─ FULL DOCS → Read docs/DATABASE.md
│
└─ Not using any agent? (general task, exploration)
   └─ FULL DOCS → Read ALL docs at session start
```

**Examples:**

| Task | Strategy |
|------|----------|
| "Create advanced furnace 3×3" | ✅ **entity-architect only** |
| "Create furnace and balance its recipes" | ✅ **entity-architect** + **recipe-balancer** |
| "Add pollution mechanic (entities, rendering, DB)" | ⚠️ **game-mechanic** + read docs/DATABASE.md, docs/GAME_ENGINE.md |
| "Refactor API structure" | ⚠️ Read docs/GAME_ENGINE.md (full architecture) |
| "Fix bug in fog of war" | 📖 Read docs/GAME_ENGINE.md (no specific agent) |
| "Optimize all database queries" | 📖 **maria** + read docs/DATABASE.md |
