# Database Migrations

## Initial Migration

The database is initialized using a single migration: `m000000_000001_init_database.php`

This migration:
1. Creates all database tables
2. Loads reference data from JSON files in `src/migrations/exports/` folder
3. Sets up foreign keys and indexes

## Reference Data (loaded from JSON)

The following tables are populated with static reference data during migration:

- `landing` - Terrain types (10 records)
- `landing_adjacency` - Terrain transitions (11 records)
- `resource` - Game resources (114 records)
- `region` - Game regions (1 record)
- `entity_type` - Entity definitions (122 records)
- `deposit_type` - Natural resource types (14 records)
- `recipe` - Crafting recipes (26 records)
- `entity_type_recipe` - Entity-recipe associations (56 records)
- `entity_type_cost` - Building costs (70 records)
- `technology` - Research technologies (8 records)
- `technology_dependency` - Tech tree dependencies (6 records)
- `technology_cost` - Technology costs (8 records)
- `technology_unlock_entity_type` - Entity unlocks (10 records)
- `technology_unlock_recipe` - Recipe unlocks (17 records)
- `user` - Default user (1 record)

## Dynamic Data (separate dump file)

Dynamic game data is stored in `/docs/dump.sql`:

- `entity` - Entity instances on map (~150 records)
- `map` - Map tiles (~78k records)
- `deposit` - Resource deposits (~1.7k records)
- `entity_resource` - Resources in buildings (~54 records)
- `entity_crafting` - Active crafting processes (~1 record)
- `pipe_system` - Fluid transport systems (when implemented)
- `pipe_system_member` - Pipe network members (when implemented)

## Usage

### Fresh Install

```bash
# 1. Run migration
php yii migrate

# 2. Load dynamic data
mysql -u root zfactory < docs/dump.sql
```

### Reset Database

```bash
# 1. Drop all tables
php yii migrate/down all

# 2. Rerun migration
php yii migrate

# 3. Reload data
mysql -u root zfactory < docs/dump.sql
```

## Exports Folder

The `src/migrations/exports/` folder contains JSON files with reference data. These files are:
- Generated from the current database state
- Used by the init migration to populate tables
- **Tracked in git** for version control

To regenerate JSON exports, query your database and save results as JSON in this folder.
