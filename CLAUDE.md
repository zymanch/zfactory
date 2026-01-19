# Project CLAUDE.md

## Documentation Strategy

### 📘 Documentation Structure

**Common Documentation** (docs/common/ - read at session start):
```
docs/common/
├── PROJECT.md         - Project overview, structure, commands
├── DATABASE.md        - Complete DB schema, tables, fields
├── ARCHITECTURE.md    - Core patterns, API structure, layers, asset loading
├── AUTHENTICATION.md  - Auth flow, user model, routes
├── ATLAS_SYSTEM.md    - Sprite generation workflow, atlas structure
├── ADMIN.md           - Admin panel: map editor, deposits, regions
├── REGIONS.md         - Region system
└── HISTORICAL/        - Old documentation (refactorings, fixes)
```

**Agent-Specific Documentation** (docs/agents/ - loaded by agents only):
```
docs/agents/
├── shake-manager.md           - Shake zones, visual effects, damage, stabilizers
├── electricity-systems.md     - Power networks, connectivity, BFS algorithm
├── pipe-systems.md            - Fluid transport, priority distribution
├── tile-rendering.md          - TileLayerManager, texture atlases, transitions
├── fog-of-war.md              - Visibility system, raycasting algorithm
├── game-modes.md              - GameModeManager, mode lifecycle
├── construction.md            - Blueprint construction, progress tracking
├── deposits.md                - DepositLayerManager, placement validation
├── resource-transport.md      - Tick-based movement, two-phase logic
└── entity-system.md           - Entity loading, tooltips, info window
```

### 🎯 When to Read Documentation

**At Session Start (regular mode without agents):**
- **ОБЯЗАТЕЛЬНО**: Read ONLY `docs/common/` folder
- Do NOT read `docs/agents/` (loaded by agents only)
- Do NOT read `docs/common/HISTORICAL/` (unless debugging old issues)

**Working WITH agents (agent mode):**
- Agents automatically load their specific docs from `docs/agents/`
- Read common docs only when:
  - Task spans multiple agent domains (entity + recipe + sprites)
  - Non-standard cases outside agent workflows
  - Need to study complete system architecture
  - Debugging complex cross-system issues

**Examples:**
```
❌ "shake-systems agent - add earthquake zone"
   → Agent has docs/agents/shake-manager.md, no common docs needed

✅ "Implement new resource system affecting entities, recipes, sprites, and API"
   → Read docs/common/DATABASE.md, docs/common/ARCHITECTURE.md (cross-functional)

✅ "Why does fog of war interact with electricity system?"
   → Read docs/common/ARCHITECTURE.md (architecture understanding)
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

| Agent | Назначение | Документация |
|-------|-----------|--------------|
| **entity-architect** | Создание новых entity types | DB schema, PHP classes, behaviors |
| **recipe-balancer** | Балансировка экономики | Production chains, balance formulas |
| **ai-sprite-wizard** | Генерация спрайтов FLUX.1 | ComfyUI API, prompt patterns |
| **pixi-renderer** | Оптимизация PixiJS | Layer system, batching, culling |
| **game-mechanic** | Разработка механик | Network algorithms, manager patterns |
| **transport-mechanic** | Система перемещения ресурсов | docs/agents/resource-transport.md |
| **shake-systems** | Зоны тряски и стабилизаторы | docs/agents/shake-manager.md |
| **electricity-systems** | Электросети и энергоснабжение | docs/agents/electricity-systems.md |
| **fluid-systems** | Трубопроводы и жидкости | docs/agents/pipe-systems.md |
| **fog-systems** | Туман войны и видимость | docs/agents/fog-of-war.md |
| **construction-systems** | Строительство и прогресс | docs/agents/construction.md |
| **js-test-writer** | JavaScript тестирование | Vitest setup, mocking patterns |
| **maria** | Database optimization | Schema design, query optimization |

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
Question: Do I need documentation?

├─ Working on single-domain task? (mechanics, entities, balance, sprites)
│  └─ USE AGENT → Specialized agent loads docs/agents/ automatically
│
├─ Task involves 2+ domains? (entity + recipes, mechanic + rendering)
│  └─ AGENT + COMMON DOCS → Agent + docs/common/ARCHITECTURE.md
│
├─ Architecture question? (why systems interact, design decisions)
│  └─ COMMON DOCS → Read docs/common/ARCHITECTURE.md, docs/common/PROJECT.md
│
├─ Database-wide changes? (new tables, schema refactoring)
│  └─ COMMON DOCS → Read docs/common/DATABASE.md
│
└─ Not using any agent? (general task, exploration)
   └─ COMMON DOCS → Read ALL docs/common/ at session start
```

**Examples:**

| Task | Strategy | Docs Read |
|------|----------|-----------|
| "Create advanced furnace 3×3" | ✅ **entity-architect only** | None (agent has context) |
| "Add earthquake zone" | ✅ **shake-systems only** | docs/agents/shake-manager.md (auto) |
| "Setup electricity network" | ✅ **electricity-systems only** | docs/agents/electricity-systems.md (auto) |
| "Create furnace and balance recipes" | ✅ **entity-architect** + **recipe-balancer** | None (agents collaborate) |
| "Add pollution mechanic (entities, rendering, DB)" | ⚠️ **game-mechanic** + common docs | docs/common/DATABASE.md, ARCHITECTURE.md |
| "Refactor API structure" | ⚠️ Read common docs | docs/common/ARCHITECTURE.md |
| "Fix bug in fog of war" | 📖 **fog-systems agent** | docs/agents/fog-of-war.md (auto) |
| "Optimize all database queries" | 📖 **maria** + common docs | docs/common/DATABASE.md |
