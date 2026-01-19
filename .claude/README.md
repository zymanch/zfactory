# Project Agents

Specialized AI agents for ZFactory development.

---

## 🚨 CRITICAL RULES - READ FIRST

### ⛔ Database Safety

**NEVER EVER run these commands:**
```bash
php yii migrate/fresh         # ❌ DROPS ENTIRE DATABASE!
php yii migrate/fresh --interactive=0  # ❌ DROPS WITHOUT CONFIRMATION!
```

**Why:** This destroys all data including user progress, entities, map state, resources.

**What to use instead:**
- ✅ `php yii migrate` - Run new migrations only
- ✅ `php yii migrate/down` - Rollback specific migration
- ✅ Restore from `dump.sql` if database corrupted

**Before any database operation:**
1. Check if command is destructive
2. Ask user for explicit confirmation
3. Never assume it's safe

**This rule was violated multiple times. DO NOT REPEAT THIS MISTAKE.**

---

## Available Agents

### 1. entity-architect
**Specialist in creating new entity types** (buildings, machines, extractors, conveyors)

**Use when:**
- Adding new building/machine to game
- Creating extraction facilities (drills, sawmills, mines)
- Implementing multi-tile entities
- Setting up entity behaviors and costs

**Workflow:**
1. Creates database migration
2. Generates PHP EntityType class
3. Creates Generator for AI sprites
4. Configures behavior rules
5. Guides through sprite generation

**Example:**
```
Действуй как entity-architect - создай advanced furnace 3×3
```

---

### 2. recipe-balancer
**Specialist in game economy balancing** and production chain optimization

**Use when:**
- Balancing new recipes
- Analyzing production chains
- Calculating optimal building ratios
- Adjusting costs and timing
- Fixing economic bottlenecks

**Workflow:**
1. Maps full production chain
2. Calculates production rates
3. Identifies bottlenecks
4. Recommends balance changes
5. Creates migration for adjustments

**Example:**
```
Загрузи recipe-balancer - проанализируй железную цепочку
```

---

### 3. ai-sprite-wizard
**Specialist in sprite generation** via FLUX.1 Dev (ComfyUI)

**Use when:**
- Generating sprites for new entities
- Creating landing variations
- Re-generating existing sprites
- Creating texture atlases
- Color shifting for variants

**Workflow:**
1. Crafts optimal FLUX.1 prompts
2. Generates all sprite states (7 + 9 construction frames)
3. Creates texture atlases
4. Handles special techniques (rotation, mirroring, color shift)

**Example:**
```
ai-sprite-wizard сессия - создай спрайты для drilling platform
```

---

### 4. pixi-renderer
**Specialist in PixiJS optimization** and visual effects

**Use when:**
- FPS drops or performance issues
- Adding new visual effects
- Optimizing rendering pipeline
- Implementing particle systems
- Debugging draw calls

**Workflow:**
1. Profiles rendering performance
2. Identifies bottlenecks
3. Implements optimizations (batching, culling, pooling)
4. Creates visual effects
5. Tests and validates FPS improvements

**Example:**
```
pixi-renderer - optimize entity rendering, FPS drops to 30
```

---

### 5. game-mechanic
**Specialist in game systems development** (mechanics like electricity, pipes, heat)

**Use when:**
- Designing new game mechanic
- Implementing network systems (BFS algorithms)
- Creating client-side managers
- Integrating backend + frontend
- Balancing mechanic parameters

**Workflow:**
1. Designs mechanic rules and data structures
2. Implements backend algorithms (network detection, state management)
3. Creates frontend managers and renderers
4. Integrates with existing systems
5. Balances and tests gameplay

**Example:**
```
Давай начнем сессию как game-mechanic - pollution system
```

---

### 6. js-test-writer
**Specialist in JavaScript testing** (Vitest unit/integration tests)

**Use when:**
- Adding tests for new modules
- Achieving test coverage goals
- Writing integration tests
- Creating test mocks and fixtures
- Setting up testing infrastructure

**Workflow:**
1. Analyzes code to test
2. Creates unit tests (isolated, mocked)
3. Writes integration tests (workflows)
4. Provides test utilities and mocks
5. Reports coverage metrics

**Example:**
```
js-test-writer - напиши тесты для ElectricitySystemManager
```

---

### 7. maria
**Specialist in MariaDB database design** and optimization

**Use when:**
- Designing database schema
- Optimizing slow queries
- Planning database changes for features
- Performance tuning

**Example:**
```
maria - optimize entity queries for large regions
```

---

## Agent Session Modes

### Quick Agent Mode (0-20 iterations)
- Work in current session
- Full context available
- Memory indicator at end of response

**Activation:**
```
Действуй как {agent_name} в этой сессии
Загрузи {agent_name} агента
```

### Persistent Agent Mode (20+ iterations)
- Creates task file (T-XXX) with full history
- Works through Task tool
- Preserves context across sessions
- Suggested automatically at 10, 15, 20... iterations

**Switching:**
Agent suggests when approaching 10+ iterations:
```
Уже 15 итераций, переключиться на Persistent Mode?
```

---

## Integration

Agents work together:
- **entity-architect** + **ai-sprite-wizard**: Create entity with sprites
- **entity-architect** + **recipe-balancer**: Balance new entity costs/recipes
- **game-mechanic** + **pixi-renderer**: Implement mechanic with visuals
- **game-mechanic** + **js-test-writer**: Test mechanic logic
- **recipe-balancer** + **maria**: Optimize recipe queries

---

## Memory Indicator

Quick Mode shows memory at end of each response:
```
[entity-architect agent mode, left: 145k tokens]
```

If missing → agent rules degraded, reload with:
```
Ты ещё помнишь entity-architect?
```

---

## Files

```
.claude/agents/
├── entity-architect.md     # 11.5 KB
├── recipe-balancer.md      # 11.6 KB
├── ai-sprite-wizard.md     # 12.6 KB
├── pixi-renderer.md        # 15.3 KB
├── game-mechanic.md        # 16.8 KB
├── js-test-writer.md       # 20.0 KB
└── maria.md                #  7.8 KB
```

Total: 7 agents, ~95 KB of specialized knowledge
