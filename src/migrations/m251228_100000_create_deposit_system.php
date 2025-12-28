<?php

use yii\db\Migration;

/**
 * Создаёт таблицы deposit_type и deposit для системы природных ресурсов
 * (деревья, камни, руды)
 */
class m251228_100000_create_deposit_system extends Migration
{
    public function safeUp()
    {
        // 1. Создать таблицу deposit_type
        $this->createTable('deposit_type', [
            'deposit_type_id' => $this->integer()->unsigned()->notNull(),
            'type' => "ENUM('tree','rock','ore') NOT NULL",
            'name' => $this->string(128)->notNull(),
            'description' => $this->text()->null(),
            'image_url' => $this->string(256)->notNull()->comment('Folder name for sprites'),
            'extension' => $this->string(4)->notNull()->defaultValue('png'),
            'max_durability' => $this->integer()->unsigned()->defaultValue(100),
            'width' => $this->tinyInteger()->unsigned()->defaultValue(1)->comment('Visual width in tiles'),
            'height' => $this->tinyInteger()->unsigned()->defaultValue(1)->comment('Visual height in tiles'),
            'icon_url' => $this->string(256)->null()->comment('Path to 64x64 icon'),
            'resource_id' => $this->integer()->unsigned()->notNull()->comment('Resource contained in deposit'),
            'resource_amount' => $this->integer()->unsigned()->defaultValue(100)->comment('Default amount'),
            'PRIMARY KEY (deposit_type_id)',
        ]);

        // 2. Создать таблицу deposit
        $this->createTable('deposit', [
            'deposit_id' => $this->primaryKey()->unsigned(),
            'deposit_type_id' => $this->integer()->unsigned()->notNull(),
            'x' => $this->integer()->unsigned()->notNull()->comment('Tile X coordinate (always 1x1)'),
            'y' => $this->integer()->unsigned()->notNull()->comment('Tile Y coordinate (always 1x1)'),
            'resource_amount' => $this->integer()->unsigned()->notNull(),
        ]);

        // 3. Добавить индексы
        $this->createIndex('idx_deposit_position', 'deposit', ['x', 'y']);
        $this->createIndex('idx_deposit_type', 'deposit', 'deposit_type_id');

        // 4. Добавить FK
        $this->addForeignKey(
            'fk_deposit_deposit_type',
            'deposit',
            'deposit_type_id',
            'deposit_type',
            'deposit_type_id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_deposit_type_resource',
            'deposit_type',
            'resource_id',
            'resource',
            'resource_id',
            'CASCADE',
            'CASCADE'
        );

        // 5. Заполнить deposit_type данными из существующих entity_type

        // Деревья (entity_type_id 1-8) → resource_id = 1 (Wood)
        $this->batchInsert('deposit_type', [
            'deposit_type_id', 'type', 'name', 'description', 'image_url', 'extension',
            'max_durability', 'width', 'height', 'icon_url', 'resource_id', 'resource_amount'
        ], [
            [1, 'tree', 'Pine Tree', 'Хвойное дерево', 'tree_pine', 'png', 50, 1, 1, 'tree_pine/normal.png', 1, 50],
            [2, 'tree', 'Oak Tree', 'Лиственное дерево', 'tree_oak', 'png', 60, 1, 1, 'tree_oak/normal.png', 1, 60],
            [3, 'tree', 'Dead Tree', 'Мертвое дерево', 'tree_dead', 'png', 20, 1, 1, 'tree_dead/normal.png', 1, 20],
            [4, 'tree', 'Birch Tree', 'Береза', 'tree_birch', 'png', 20, 1, 2, 'tree_birch/normal.png', 1, 20],
            [5, 'tree', 'Willow Tree', 'Ива', 'tree_willow', 'png', 25, 1, 3, 'tree_willow/normal.png', 1, 25],
            [6, 'tree', 'Maple Tree', 'Клен', 'tree_maple', 'png', 20, 1, 2, 'tree_maple/normal.png', 1, 20],
            [7, 'tree', 'Spruce Tree', 'Ель', 'tree_spruce', 'png', 25, 1, 3, 'tree_spruce/normal.png', 1, 25],
            [8, 'tree', 'Ash Tree', 'Ясень', 'tree_ash', 'png', 20, 1, 2, 'tree_ash/normal.png', 1, 20],
        ]);

        // Камни (entity_type_id 10-12) → resource_id = 5 (Stone)
        $this->batchInsert('deposit_type', [
            'deposit_type_id', 'type', 'name', 'description', 'image_url', 'extension',
            'max_durability', 'width', 'height', 'icon_url', 'resource_id', 'resource_amount'
        ], [
            [10, 'rock', 'Small Rock', 'Небольшой камень', 'rock_small', 'png', 100, 1, 1, 'rock_small/normal.png', 5, 100],
            [11, 'rock', 'Medium Rock', 'Средний камень', 'rock_medium', 'png', 200, 1, 1, 'rock_medium/normal.png', 5, 150],
            [12, 'rock', 'Large Rock', 'Большой камень', 'rock_large', 'png', 300, 1, 1, 'rock_large/normal.png', 5, 200],
        ]);

        // Руды (entity_type_id 300-301) → resource_id = 8,9 (Iron/Copper Deposit)
        $this->batchInsert('deposit_type', [
            'deposit_type_id', 'type', 'name', 'description', 'image_url', 'extension',
            'max_durability', 'width', 'height', 'icon_url', 'resource_id', 'resource_amount'
        ], [
            [300, 'ore', 'Iron Ore', 'Месторождение железной руды', 'ore_iron', 'png', 9999, 1, 1, 'ore_iron/normal.png', 8, 9999],
            [301, 'ore', 'Copper Ore', 'Месторождение медной руды', 'ore_copper', 'png', 9999, 1, 1, 'ore_copper/normal.png', 9, 9999],
        ]);
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_deposit_type_resource', 'deposit_type');
        $this->dropForeignKey('fk_deposit_deposit_type', 'deposit');
        $this->dropTable('deposit');
        $this->dropTable('deposit_type');
    }
}
