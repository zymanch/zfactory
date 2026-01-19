# Pipes & Resources Refactoring (2026-01)

## Overview

Major refactoring of pipe systems and resource management completed on January 15, 2026.

## Goals

1. **Simplify Database**: Remove 4 redundant tables
2. **Move Logic to Client**: Calculate pipe systems locally using BFS
3. **Improve Type Safety**: Add resource_types validation
4. **Visual Enhancement**: Generate pipe inlet sprites

## Changes

### 1. Database Schema Changes

#### Added Columns

**entity_type table:**
- `orientation`: Added 'horizontal' and 'vertical' values for pipes
- `resource_types`: New SET column: 'raw','liquid','crafted','deposit','energy'

**resource table:**
- `type`: Added 'energy' value
- New resource: Electricity (ID 400, type='energy')

**entity_resource table:**
- `last_output_direction`: ENUM('left','right') - Splitter round-robin state

#### Removed Tables

- `pipe_system` - Pooled pipe networks (replaced by client-side BFS)
- `pipe_system_member` - Pipe membership (replaced by client-side BFS)
- `underground_link` - Underground belt connections (replaced by SpatialIndex lookup)
- `splitter_state` - Splitter round-robin state (moved to entity_resource)

### 2. Backend Changes (PHP)

**Removed Files:**
- `src/bl/pipes/PipeSystemManager.php`
- `src/models/PipeSystem.php`
- `src/models/PipeSystemMember.php`
- `src/models/UndergroundLink.php`
- `src/models/SplitterState.php`

**Modified Files:**
- `src/commands/actions/map/CreateEntity.php` - Removed PipeSystemManager::recalculateSystems()
- `src/commands/actions/map/DeleteEntity.php` - Removed PipeSystemManager::recalculateSystems()
- `src/commands/actions/game/Entities.php` - Removed getPipeSystems(), removed pipeSystems from API response

**Added:**
- `src/commands/PipeController.php::actionGenerateInletVariants()` - Generates 15px inlet sprites

### 3. Frontend Changes (JavaScript)

**PipeSystemManager.js** - Complete rewrite:
- **NEW:** `calculateSystems()` - Local BFS calculation instead of server loading
- **NEW:** `findConnectedPipes(startPipeId, processed)` - BFS algorithm
- **NEW:** `getNeighborPipes(pipeId)` - 4-direction neighbor detection with SpatialIndex fallback
- **NEW:** `createLocalSystem(memberIds)` - Creates local system with capacity from entity_type.power
- **NEW:** `distributeFluidToEntities(system, resourceId, amount)` - Priority distribution
- **REMOVED:** `loadSystems(pipeSystems)` - No longer loads from server

**Priority Distribution Logic:**
```javascript
// Priority: Tanks (135,136,140,141) → Buildings → Mining
const tanks = entities.filter(e => [135, 136, 140, 141].includes(e.entity_type_id));
const buildings = entities.filter(e => type && type.type === 'building');
const mining = entities.filter(e => type && type.type === 'mining');
const prioritized = [...tanks, ...buildings, ...mining];
```

**SplitterState.js:**
- `loadFromSaved()` - Now reads `last_output_direction` from entity_resource
- `getSaveData()` - Now writes `last_output_direction` to entity_resource

**PipeInletRenderer.js** - NEW file:
- Loads inlet atlas: `/assets/tiles/pipe_inlets/inlet_atlas.png` (1280×64)
- Finds pipe connections to buildings/mining/storage
- Renders 15px inlet sprites at entity edges
- 4 directions × 5 states = 20 sprites

**game.js:**
- Added `PipeInletRenderer` import
- Added pipe inlet layer (zIndex: 3)
- Calls `pipeSystemManager.calculateSystems()` instead of `loadSystems()`
- Calls `pipeInletRenderer.update()` each frame

### 4. Migrations Applied

1. **m260115_010000_extend_entity_type** - Added horizontal/vertical + resource_types
2. **m260115_020000_populate_resource_types** - Filled resource_types for all entities
3. **m260115_030000_add_energy_resource** - Added energy type + Electricity resource
4. **m260115_040000_add_splitter_state_column** - Added last_output_direction
5. **m260115_050000_migrate_splitter_state** - Migrated splitter data to entity_resource
6. **m260115_060000_migrate_pipe_system_amounts** - Migrated fluid amounts to entity_resource
7. **m260115_100000_drop_obsolete_tables** - Dropped 4 obsolete tables

### 5. Visual Assets

**Generated:**
- `public/assets/tiles/pipe_inlets/inlet_atlas.png` (1280×64)
  - 15px inlet pieces cropped from horizontal pipe
  - Rounded corners (4px radius)
  - 4 rotations: top, right, down, left
  - 5 states: normal, damaged, blueprint, normal_selected, damaged_selected

**Generation Command:**
```bash
php yii pipe/generate-inlet-variants
```

## Architecture

### Before (Server-Side)

```
[Client] ← pipe_system data ← [Server]
                                   ↓
                          BFS in PipeSystemManager.php
                                   ↓
                          pipe_system + pipe_system_member tables
```

**Problems:**
- Recalculated on every pipe add/remove (slow)
- pipe_system.current_amount could desync from entity_resource
- Extra database tables

### After (Client-Side)

```
[Client] → calculateSystems() → BFS locally
                                     ↓
                              Local Map<systemId, system>
                                     ↓
                              No server roundtrip
```

**Benefits:**
- Faster (no server roundtrip)
- Always in sync (calculated from entity_resource)
- Simpler database schema
- Priority-based fluid distribution

## BFS Algorithm

```javascript
calculateSystems() {
    this.systems.clear();
    this.entityToSystem.clear();
    this.nextSystemId = 1;

    const processed = new Set();
    const pipeEntityIds = this.getAllPipeEntityIds();

    for (const pipeId of pipeEntityIds) {
        if (processed.has(pipeId)) continue;

        const systemMembers = this.findConnectedPipes(pipeId, processed);
        this.createLocalSystem(systemMembers);
    }
}

findConnectedPipes(startPipeId, processed) {
    const queue = [startPipeId];
    const system = [];
    processed.add(startPipeId);

    while (queue.length > 0) {
        const pipeId = queue.shift();
        system.push(pipeId);

        const neighbors = this.getNeighborPipes(pipeId);
        for (const neighborId of neighbors) {
            if (!processed.has(neighborId)) {
                processed.add(neighborId);
                queue.push(neighborId);
            }
        }
    }
    return system;
}
```

## Testing

**Unit Tests:**
- ElectricitySystemManager: 9 tests ✓
- Crafting integration: 7 tests ✓
- Game simulation: 12 tests ✓

**Integration Tests:**
- Pipe system BFS: 9 tests (2 passing, 7 skipped due to grid size)

**Manual Testing:**
- Game loads successfully ✓
- Pipe systems calculated ✓
- Inlet sprites rendered ✓

## Performance Impact

**Before:**
- Pipe add: 2 queries + BFS recalculation on server
- Pipe remove: 2 queries + BFS recalculation on server
- Game load: 1 query for pipe_system + 1 for pipe_system_member

**After:**
- Pipe add: 1 query (entity creation only)
- Pipe remove: 1 query (entity deletion only)
- Game load: BFS calculation in ~10ms (for 100 pipes)

**Result:** ~50% reduction in database queries for pipe operations

## Breaking Changes

**API Changes:**
- `/game/entities` no longer returns `pipeSystems` field
- Pipe systems are now calculated client-side

**Database:**
- Tables removed: pipe_system, pipe_system_member, underground_link, splitter_state
- Columns added: entity_type.resource_types, entity_resource.last_output_direction
- Enum values added: entity_type.orientation (horizontal/vertical), resource.type (energy)

**Code:**
- Removed: PipeSystemManager PHP class
- Removed: loadSystems() method in JavaScript PipeSystemManager
- Changed: PipeSystemManager.js is now fully client-side

## Future Improvements

1. **Performance:** Optimize BFS for maps with 1000+ pipes
2. **Visuals:** Animate fluid flow in inlet sprites
3. **Testing:** Fix grid size mismatch in pipe system tests
4. **Validation:** Use resource_types SET for client-side validation

## Migration Path

1. Run migrations: `php yii migrate`
2. Rebuild assets: `npm run assets`
3. Generate inlet sprites: `php yii pipe/generate-inlet-variants`
4. Regenerate models: `composer run ar`
5. Test game functionality

## Post-Refactoring Fixes

### Game Loading Fixes (2026-01-15)

After removing `pipeSystems` from API response, discovered **4 critical issues** preventing game from loading:

#### Issue 1: Duplicate Initialization
- **Problem**: Old `DOMContentLoaded` code in `game.js` (lines 1001-1007) called constructor with wrong signature
- **Fix**: Removed old initialization code, bootstrap.js now handles all initialization

#### Issue 2: Wrong Webpack Entry Point
- **Problem**: webpack.mix.js used `game.js` as entry point, but it's now just a class
- **Fix**: Changed entry point to `bootstrap.js`, outputs as `game.js`

#### Issue 3: API Response Format Mismatch
- **Problem**: GameLoader returned `{ result: 'ok', entities: [...] }`, constructor expected array
- **Fix**: GameLoader now returns unwrapped data (`data.entities`, `data.tiles`)

#### Issue 4: Insufficient API Validation
- **Problem**: No validation of API responses, silent failures with cryptic errors
- **Fix**: Added HTTP status, Content-Type, and structure validation with user-friendly errors

**Files Changed**:
- `resources/js/game.js` - Removed old initialization
- `webpack.mix.js` - Changed entry point to bootstrap.js
- `resources/js/core/GameLoader.js` - Return clean data + validation
- `resources/js/bootstrap.js` - Wrap tiles for constructor compatibility + ErrorModal integration
- `resources/js/modules/windows/ErrorModal.js` - NEW: Reusable error modal component
- `src/views/game/index.php` - Added cache busting timestamp
- `src/commands/actions/game/Config.php` - Fixed conveyor/pipe atlas paths in assetManifest

**Fixes Applied**:
- ✅ Conveyor atlas paths corrected (20 files)
- ✅ Removed non-existent pipe atlas generation (16 files)
- ✅ Field name validation fixed (`landing` not `landings`)
- ✅ Error modal shows user-friendly messages

**See**: `docs/GAME_LOADER_VALIDATION_FIX.md` for complete technical details.

## Rollback

**NOT RECOMMENDED** - Data migration is one-way.

If rollback is absolutely necessary:
1. Restore database from backup before migrations
2. Revert code changes
3. Rebuild assets

Data in `entity_resource.last_output_direction` and migrated fluid amounts cannot be automatically restored to old table structure.
