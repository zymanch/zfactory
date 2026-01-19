<?php

use yii\db\Migration;

/**
 * Initial database migration
 * Creates all tables and populates reference data
 */
class m000000_000001_init_database extends Migration
{
    public function safeUp()
    {
        // ============ CREATE TABLES ============

        // Landing (terrain types)
        $this->createTable('landing', [
            'landing_id' => $this->primaryKey()->unsigned(),
            'is_buildable' => "enum('yes','no') NOT NULL DEFAULT 'yes'",
            'fluid_type' => "enum('none','water','lava') NOT NULL DEFAULT 'none'",
            'name' => $this->string(256)->notNull(),
            'folder' => $this->string(256)->notNull(),
            'variations_count' => $this->integer()->notNull()->defaultValue(5),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

        // Landing adjacency (terrain transitions)
        $this->createTable('landing_adjacency', [
            'adjacency_id' => $this->primaryKey()->unsigned(),
            'landing_id_1' => $this->integer()->unsigned()->notNull(),
            'landing_id_2' => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->createIndex('idx_unique_pair', 'landing_adjacency', ['landing_id_1', 'landing_id_2'], true);
        $this->addForeignKey('fk_landing_adjacency_1', 'landing_adjacency', 'landing_id_1', 'landing', 'landing_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_landing_adjacency_2', 'landing_adjacency', 'landing_id_2', 'landing', 'landing_id', 'CASCADE', 'CASCADE');

        // Resource (game resources)
        $this->createTable('resource', [
            'resource_id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(128)->notNull(),
            'icon_url' => $this->string(256)->notNull(),
            'type' => "enum('raw','liquid','crafted','deposit','science') NOT NULL DEFAULT 'raw'",
            'max_stack' => $this->integer()->unsigned()->notNull()->defaultValue(100),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

        // Region
        $this->createTable('region', [
            'region_id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(100)->notNull(),
            'description' => $this->text(),
            'width' => $this->integer()->unsigned()->notNull(),
            'height' => $this->integer()->unsigned()->notNull(),
            'seed' => $this->integer()->unsigned()->null(),
            'is_starter' => $this->boolean()->notNull()->defaultValue(false),
            'ship_attach_x' => $this->integer()->null()->comment('Island X coordinate where ship attaches'),
            'ship_attach_y' => $this->integer()->null()->comment('Island Y coordinate where ship attaches'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->createIndex('idx_region_coordinates', 'region', ['ship_attach_x', 'ship_attach_y']);

        // Entity Type
        $this->createTable('entity_type', [
            'entity_type_id' => $this->integer()->unsigned()->notNull(),
            'type' => "enum('building','conveyor','pipe','electricity','manipulator','tree','relief','resource','eye','mining','storage','ship') NOT NULL",
            'subtype' => $this->string(64)->null(),
            'name' => $this->string(128)->notNull(),
            'folder' => $this->string(256)->notNull(),
            'extension' => $this->string(4)->notNull()->defaultValue('png'),
            'max_durability' => $this->integer()->unsigned()->notNull()->defaultValue(100),
            'converts_to_landing_id' => $this->integer()->unsigned()->null()->comment('Landing ID after entity is demolished'),
            'width' => $this->integer(3)->unsigned()->notNull()->defaultValue(1),
            'height' => $this->integer(3)->unsigned()->notNull()->defaultValue(1),
            'icon_url' => $this->string(256)->null(),
            'power' => $this->integer()->unsigned()->notNull()->defaultValue(1),
            'center_position_px' => $this->string(16)->null()->comment('Center position in pixels for multi-tile entities (format: x,y)'),
            'parent_entity_type_id' => $this->integer()->unsigned()->null(),
            'orientation' => "enum('none','up','right','down','left') NOT NULL DEFAULT 'none'",
            'animation_fps' => $this->decimal(5, 2)->null()->comment('Animation speed in frames per second'),
            'description' => $this->text()->null(),
            'construction_ticks' => $this->integer()->notNull()->defaultValue(60),
            'storage_type' => "enum('none','unlimited','limited') NOT NULL DEFAULT 'none'",
            'storage_resource_count' => $this->integer()->unsigned()->null()->comment('Total max resources'),
            'storage_per_resource' => $this->integer()->unsigned()->null()->comment('Max per resource type'),
            'resource_types' => "SET('solid','liquid') DEFAULT NULL",
            'input_connections' => "SET('up','up_1','up_2','down','down_1','down_2','left','left_1','left_2','right','right_1','right_2') DEFAULT NULL",
            'output_connections' => "SET('up','up_1','up_2','down','down_1','down_2','left','left_1','left_2','right','right_1','right_2') DEFAULT NULL",
            'PRIMARY KEY (entity_type_id)',
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

        // Deposit Type
        $this->createTable('deposit_type', [
            'deposit_type_id' => $this->integer()->unsigned()->notNull(),
            'type' => "enum('tree','rock','ore') NOT NULL",
            'name' => $this->string(128)->notNull(),
            'folder' => $this->string(256)->notNull()->comment('Folder name for sprites'),
            'resource_id' => $this->integer()->unsigned()->notNull(),
            'resource_amount' => $this->integer()->unsigned()->notNull()->defaultValue(100),
            'width' => $this->integer(3)->unsigned()->notNull()->defaultValue(1),
            'height' => $this->integer(3)->unsigned()->notNull()->defaultValue(1),
            'PRIMARY KEY (deposit_type_id)',
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->addForeignKey('fk_deposit_type_resource', 'deposit_type', 'resource_id', 'resource', 'resource_id', 'CASCADE');

        // Recipe
        $this->createTable('recipe', [
            'recipe_id' => $this->primaryKey()->unsigned(),
            'output_resource_id' => $this->integer()->unsigned()->notNull(),
            'output_amount' => $this->integer()->unsigned()->notNull()->defaultValue(1),
            'input1_resource_id' => $this->integer()->unsigned()->notNull(),
            'input1_amount' => $this->integer()->unsigned()->notNull()->defaultValue(1),
            'input2_resource_id' => $this->integer()->unsigned()->null(),
            'input2_amount' => $this->integer()->unsigned()->null(),
            'input3_resource_id' => $this->integer()->unsigned()->null(),
            'input3_amount' => $this->integer()->unsigned()->null(),
            'ticks' => $this->integer()->unsigned()->notNull()->defaultValue(60),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->addForeignKey('fk_recipe_output', 'recipe', 'output_resource_id', 'resource', 'resource_id');
        $this->addForeignKey('fk_recipe_input1', 'recipe', 'input1_resource_id', 'resource', 'resource_id');
        $this->addForeignKey('fk_recipe_input2', 'recipe', 'input2_resource_id', 'resource', 'resource_id');
        $this->addForeignKey('fk_recipe_input3', 'recipe', 'input3_resource_id', 'resource', 'resource_id');

        // Entity Type Recipe
        $this->createTable('entity_type_recipe', [
            'entity_type_id' => $this->integer()->unsigned()->notNull(),
            'recipe_id' => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->addPrimaryKey('pk_entity_type_recipe', 'entity_type_recipe', ['entity_type_id', 'recipe_id']);
        $this->addForeignKey('fk_etr_entity_type', 'entity_type_recipe', 'entity_type_id', 'entity_type', 'entity_type_id', 'CASCADE');
        $this->addForeignKey('fk_etr_recipe', 'entity_type_recipe', 'recipe_id', 'recipe', 'recipe_id', 'CASCADE');

        // Entity Type Cost
        $this->createTable('entity_type_cost', [
            'entity_type_cost_id' => $this->primaryKey()->unsigned(),
            'entity_type_id' => $this->integer()->unsigned()->notNull(),
            'resource_id' => $this->integer()->unsigned()->notNull(),
            'quantity' => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->createIndex('idx_entity_type', 'entity_type_cost', 'entity_type_id');
        $this->createIndex('unique_type_resource', 'entity_type_cost', ['entity_type_id', 'resource_id'], true);
        $this->addForeignKey('fk_entity_type_cost_entity_type', 'entity_type_cost', 'entity_type_id', 'entity_type', 'entity_type_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_entity_type_cost_resource', 'entity_type_cost', 'resource_id', 'resource', 'resource_id', 'CASCADE', 'CASCADE');

        // Technology
        $this->createTable('technology', [
            'technology_id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(128)->notNull(),
            'description' => $this->text(),
            'icon' => $this->string(256),
            'tier' => $this->integer()->unsigned()->notNull()->defaultValue(1),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->createIndex('idx_technology_tier', 'technology', 'tier');

        // Technology Dependency
        $this->createTable('technology_dependency', [
            'technology_id' => $this->integer()->unsigned()->notNull(),
            'required_technology_id' => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->addPrimaryKey('pk_technology_dependency', 'technology_dependency', ['technology_id', 'required_technology_id']);
        $this->addForeignKey('fk_tech_dep_technology', 'technology_dependency', 'technology_id', 'technology', 'technology_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_tech_dep_required', 'technology_dependency', 'required_technology_id', 'technology', 'technology_id', 'CASCADE', 'CASCADE');

        // Technology Cost
        $this->createTable('technology_cost', [
            'technology_id' => $this->integer()->unsigned()->notNull(),
            'resource_id' => $this->integer()->unsigned()->notNull(),
            'quantity' => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->addPrimaryKey('pk_technology_cost', 'technology_cost', ['technology_id', 'resource_id']);
        $this->addForeignKey('fk_tech_cost_technology', 'technology_cost', 'technology_id', 'technology', 'technology_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_tech_cost_resource', 'technology_cost', 'resource_id', 'resource', 'resource_id', 'CASCADE', 'CASCADE');

        // Technology Unlock Entity Type
        $this->createTable('technology_unlock_entity_type', [
            'technology_id' => $this->integer()->unsigned()->notNull(),
            'entity_type_id' => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->addPrimaryKey('pk_tech_unlock_entity', 'technology_unlock_entity_type', ['technology_id', 'entity_type_id']);
        $this->addForeignKey('fk_tech_unlock_entity_tech', 'technology_unlock_entity_type', 'technology_id', 'technology', 'technology_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_tech_unlock_entity_type', 'technology_unlock_entity_type', 'entity_type_id', 'entity_type', 'entity_type_id', 'CASCADE', 'CASCADE');

        // Technology Unlock Recipe
        $this->createTable('technology_unlock_recipe', [
            'technology_id' => $this->integer()->unsigned()->notNull(),
            'recipe_id' => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->addPrimaryKey('pk_tech_unlock_recipe', 'technology_unlock_recipe', ['technology_id', 'recipe_id']);
        $this->addForeignKey('fk_tech_unlock_recipe_tech', 'technology_unlock_recipe', 'technology_id', 'technology', 'technology_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_tech_unlock_recipe_recipe', 'technology_unlock_recipe', 'recipe_id', 'recipe', 'recipe_id', 'CASCADE', 'CASCADE');

        // User
        $this->createTable('user', [
            'user_id' => $this->primaryKey()->unsigned(),
            'current_region_id' => $this->integer()->unsigned()->notNull()->defaultValue(1),
            'ship_view_radius' => $this->integer()->unsigned()->notNull()->defaultValue(400),
            'ship_jump_distance' => $this->integer()->unsigned()->notNull()->defaultValue(278),
            'username' => $this->string(64)->notNull()->unique(),
            'password' => $this->string(255)->notNull(),
            'email' => $this->string(128)->notNull()->unique(),
            'is_admin' => $this->boolean()->notNull()->defaultValue(false),
            'build_panel' => $this->text()->null()->comment('JSON array of entity_type_ids for 10 slots'),
            'camera_x' => $this->integer()->notNull()->defaultValue(0),
            'camera_y' => $this->integer()->notNull()->defaultValue(0),
            'zoom' => $this->float()->notNull()->defaultValue(1),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->createIndex('idx_user_current_region', 'user', 'current_region_id');
        $this->addForeignKey('fk_user_current_region', 'user', 'current_region_id', 'region', 'region_id', 'CASCADE', 'CASCADE');

        // User Technology
        $this->createTable('user_technology', [
            'user_id' => $this->integer()->unsigned()->notNull(),
            'technology_id' => $this->integer()->unsigned()->notNull(),
            'researched_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->addPrimaryKey('pk_user_technology', 'user_technology', ['user_id', 'technology_id']);
        $this->addForeignKey('fk_user_tech_user', 'user_technology', 'user_id', 'user', 'user_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_user_tech_technology', 'user_technology', 'technology_id', 'technology', 'technology_id', 'CASCADE', 'CASCADE');
        $this->createIndex('idx_user_technology_user', 'user_technology', 'user_id');

        // User Resource
        $this->createTable('user_resource', [
            'user_resource_id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'resource_id' => $this->integer()->unsigned()->notNull(),
            'quantity' => $this->integer()->unsigned()->notNull()->defaultValue(0),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->createIndex('idx_user', 'user_resource', 'user_id');
        $this->createIndex('unique_user_resource', 'user_resource', ['user_id', 'resource_id'], true);
        $this->addForeignKey('fk_user_resource_user', 'user_resource', 'user_id', 'user', 'user_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_user_resource_resource', 'user_resource', 'resource_id', 'resource', 'resource_id', 'CASCADE', 'CASCADE');

        // Deposit
        $this->createTable('deposit', [
            'deposit_id' => $this->primaryKey()->unsigned(),
            'deposit_type_id' => $this->integer()->unsigned()->notNull(),
            'region_id' => $this->integer()->unsigned()->notNull()->defaultValue(1),
            'x' => $this->integer()->unsigned()->notNull(),
            'y' => $this->integer()->unsigned()->notNull(),
            'resource_amount' => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->createIndex('idx_deposit_position', 'deposit', ['x', 'y']);
        $this->createIndex('idx_deposit_region', 'deposit', 'region_id');
        $this->addForeignKey('fk_deposit_type', 'deposit', 'deposit_type_id', 'deposit_type', 'deposit_type_id', 'CASCADE');
        $this->addForeignKey('fk_deposit_region', 'deposit', 'region_id', 'region', 'region_id', 'CASCADE', 'CASCADE');

        // Entity
        $this->createTable('entity', [
            'entity_id' => $this->primaryKey()->unsigned(),
            'entity_type_id' => $this->integer()->unsigned()->null(),
            'region_id' => $this->integer()->unsigned()->notNull()->defaultValue(1),
            'state' => "enum('built','blueprint') NOT NULL DEFAULT 'built'",
            'durability' => $this->integer()->unsigned()->notNull()->defaultValue(100),
            'x' => $this->integer()->unsigned()->notNull(),
            'y' => $this->integer()->unsigned()->notNull(),
            'construction_progress' => $this->integer(3)->unsigned()->notNull()->defaultValue(100),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->createIndex('idx_entity_region', 'entity', 'region_id');
        $this->addForeignKey('fk_entity_region', 'entity', 'region_id', 'region', 'region_id', 'CASCADE', 'CASCADE');

        // Map
        $this->createTable('map', [
            'map_id' => $this->primaryKey()->unsigned(),
            'landing_id' => $this->integer()->unsigned()->notNull(),
            'region_id' => $this->integer()->unsigned()->notNull()->defaultValue(1),
            'x' => $this->integer()->unsigned()->notNull(),
            'y' => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->createIndex('idx_map_region', 'map', 'region_id');
        $this->addForeignKey('fk_map_region', 'map', 'region_id', 'region', 'region_id', 'CASCADE', 'CASCADE');

        // Entity Resource
        $this->createTable('entity_resource', [
            'entity_resource_id' => $this->primaryKey()->unsigned(),
            'entity_id' => $this->integer()->unsigned()->notNull(),
            'resource_id' => $this->integer()->unsigned()->notNull(),
            'amount' => $this->integer()->unsigned()->notNull()->defaultValue(0),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->createIndex('idx_entity_resource_entity', 'entity_resource', 'entity_id');
        $this->createIndex('idx_entity_resource_resource', 'entity_resource', 'resource_id');
        $this->createIndex('idx_entity_resource_unique', 'entity_resource', ['entity_id', 'resource_id'], true);
        $this->addForeignKey('fk_entity_resource_entity', 'entity_resource', 'entity_id', 'entity', 'entity_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_entity_resource_resource', 'entity_resource', 'resource_id', 'resource', 'resource_id', 'CASCADE', 'CASCADE');

        // Entity Crafting
        $this->createTable('entity_crafting', [
            'entity_id' => $this->integer()->unsigned()->notNull(),
            'recipe_id' => $this->integer()->unsigned()->notNull(),
            'ticks_remaining' => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');
        $this->addPrimaryKey('pk_entity_crafting', 'entity_crafting', 'entity_id');
        $this->addForeignKey('fk_entity_crafting_entity', 'entity_crafting', 'entity_id', 'entity', 'entity_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_entity_crafting_recipe', 'entity_crafting', 'recipe_id', 'recipe', 'recipe_id', 'CASCADE', 'CASCADE');

        // Pipe System
        $this->createTable('pipe_system', [
            'pipe_system_id' => $this->primaryKey()->unsigned(),
            'region_id' => $this->integer()->unsigned()->notNull(),
            'resource_id' => $this->integer()->unsigned()->null()->comment('Fluid type (NULL = empty)'),
            'current_amount' => $this->integer()->unsigned()->defaultValue(0),
            'max_capacity' => $this->integer()->unsigned()->defaultValue(0),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->createIndex('idx_region', 'pipe_system', 'region_id');

        // Pipe System Member
        $this->createTable('pipe_system_member', [
            'pipe_system_id' => $this->integer()->unsigned()->notNull(),
            'entity_id' => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addPrimaryKey('pk_pipe_system_member', 'pipe_system_member', ['pipe_system_id', 'entity_id']);
        $this->createIndex('idx_entity', 'pipe_system_member', 'entity_id');

        // Ship Entity
        $this->createTable('ship_entity', [
            'ship_entity_id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'entity_type_id' => $this->integer()->unsigned()->notNull(),
            'x' => $this->integer()->notNull(),
            'y' => $this->integer()->notNull(),
            'state' => "enum('built','blueprint') NOT NULL DEFAULT 'built'",
            'durability' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->createIndex('idx_ship_entity_user', 'ship_entity', 'user_id');
        $this->createIndex('idx_ship_entity_type', 'ship_entity', 'entity_type_id');
        $this->addForeignKey('fk_ship_entity_user', 'ship_entity', 'user_id', 'user', 'user_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_ship_entity_entity_type', 'ship_entity', 'entity_type_id', 'entity_type', 'entity_type_id', 'CASCADE', 'CASCADE');

        // Ship Entity Resource
        $this->createTable('ship_entity_resource', [
            'ship_entity_resource_id' => $this->primaryKey()->unsigned(),
            'ship_entity_id' => $this->integer()->unsigned()->notNull(),
            'resource_id' => $this->integer()->unsigned()->notNull(),
            'amount' => $this->integer()->notNull()->defaultValue(1),
            'status' => $this->string(50)->notNull()->defaultValue('idle'),
            'position_px' => $this->integer()->null(),
            'from_direction' => $this->string(20)->null(),
            'last_output_direction' => $this->string(20)->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->createIndex('idx_ship_entity_resource_ship_entity', 'ship_entity_resource', 'ship_entity_id');
        $this->createIndex('idx_ship_entity_resource_resource', 'ship_entity_resource', 'resource_id');
        $this->addForeignKey('fk_ship_entity_resource_ship_entity', 'ship_entity_resource', 'ship_entity_id', 'ship_entity', 'ship_entity_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_ship_entity_resource_resource', 'ship_entity_resource', 'resource_id', 'resource', 'resource_id', 'CASCADE', 'CASCADE');

        // Ship Entity Crafting
        $this->createTable('ship_entity_crafting', [
            'ship_entity_crafting_id' => $this->primaryKey()->unsigned(),
            'ship_entity_id' => $this->integer()->unsigned()->notNull(),
            'recipe_id' => $this->integer()->unsigned()->notNull(),
            'ticks_remaining' => $this->integer()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->createIndex('idx_ship_entity_crafting_ship_entity', 'ship_entity_crafting', 'ship_entity_id');
        $this->createIndex('idx_ship_entity_crafting_recipe', 'ship_entity_crafting', 'recipe_id');
        $this->addForeignKey('fk_ship_entity_crafting_ship_entity', 'ship_entity_crafting', 'ship_entity_id', 'ship_entity', 'ship_entity_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_ship_entity_crafting_recipe', 'ship_entity_crafting', 'recipe_id', 'recipe', 'recipe_id', 'CASCADE', 'CASCADE');

        // Ship Landing
        $this->createTable('ship_landing', [
            'ship_landing_id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'landing_id' => $this->integer()->unsigned()->notNull(),
            'x' => $this->integer()->notNull(),
            'y' => $this->integer()->notNull(),
            'variation' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->createIndex('idx_ship_landing_user', 'ship_landing', 'user_id');
        $this->createIndex('idx_ship_landing_landing', 'ship_landing', 'landing_id');
        $this->createIndex('idx_ship_landing_unique_coords', 'ship_landing', ['user_id', 'x', 'y'], true);
        $this->addForeignKey('fk_ship_landing_user', 'ship_landing', 'user_id', 'user', 'user_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_ship_landing_landing', 'ship_landing', 'landing_id', 'landing', 'landing_id', 'CASCADE', 'CASCADE');

        // ============ POPULATE REFERENCE DATA ============

        // Load data from JSON files
        $dataDir = __DIR__ . '/exports';

        // Landing
        $this->loadAndInsertJson($dataDir . '/landing.json', 'landing');

        // Landing Adjacency
        $this->loadAndInsertJson($dataDir . '/landing_adjacency.json', 'landing_adjacency');

        // Resource
        $this->loadAndInsertJson($dataDir . '/resource.json', 'resource');

        // Region
        $this->loadAndInsertJson($dataDir . '/region.json', 'region');

        // Entity Type
        $this->loadAndInsertJson($dataDir . '/entity_type.json', 'entity_type');

        // Deposit Type
        $this->loadAndInsertJson($dataDir . '/deposit_type.json', 'deposit_type');

        // Recipe
        $this->loadAndInsertJson($dataDir . '/recipe.json', 'recipe');

        // Entity Type Recipe
        $this->loadAndInsertJson($dataDir . '/entity_type_recipe.json', 'entity_type_recipe');

        // Entity Type Cost
        $this->loadAndInsertJson($dataDir . '/entity_type_cost.json', 'entity_type_cost');

        // Technology
        $this->loadAndInsertJson($dataDir . '/technology.json', 'technology');

        // Technology Dependency
        $this->loadAndInsertJson($dataDir . '/technology_dependency.json', 'technology_dependency');

        // Technology Cost
        $this->loadAndInsertJson($dataDir . '/technology_cost.json', 'technology_cost');

        // Technology Unlock Entity Type
        $this->loadAndInsertJson($dataDir . '/technology_unlock_entity_type.json', 'technology_unlock_entity_type');

        // Technology Unlock Recipe
        $this->loadAndInsertJson($dataDir . '/technology_unlock_recipe.json', 'technology_unlock_recipe');

        // User
        $this->loadAndInsertJson($dataDir . '/user.json', 'user');
    }

    private function loadAndInsertJson($file, $table)
    {
        if (!file_exists($file)) {
            echo "Warning: File not found: $file\n";
            return;
        }

        $data = json_decode(file_get_contents($file), true);
        if (empty($data)) {
            return;
        }

        $columns = array_keys($data[0]);

        // Batch insert in chunks of 100 rows
        $chunks = array_chunk($data, 100);
        foreach ($chunks as $chunk) {
            $this->batchInsert($table, $columns, array_map('array_values', $chunk));
        }
    }

    public function safeDown()
    {
        $this->dropTable('ship_landing');
        $this->dropTable('ship_entity_crafting');
        $this->dropTable('ship_entity_resource');
        $this->dropTable('ship_entity');
        $this->dropTable('pipe_system_member');
        $this->dropTable('pipe_system');
        $this->dropTable('entity_crafting');
        $this->dropTable('entity_resource');
        $this->dropTable('map');
        $this->dropTable('entity');
        $this->dropTable('deposit');
        $this->dropTable('user_resource');
        $this->dropTable('user_technology');
        $this->dropTable('user');
        $this->dropTable('technology_unlock_recipe');
        $this->dropTable('technology_unlock_entity_type');
        $this->dropTable('technology_cost');
        $this->dropTable('technology_dependency');
        $this->dropTable('technology');
        $this->dropTable('entity_type_cost');
        $this->dropTable('entity_type_recipe');
        $this->dropTable('recipe');
        $this->dropTable('deposit_type');
        $this->dropTable('entity_type');
        $this->dropTable('region');
        $this->dropTable('resource');
        $this->dropTable('landing_adjacency');
        $this->dropTable('landing');
    }
}
