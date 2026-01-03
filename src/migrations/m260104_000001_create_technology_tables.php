<?php

use yii\db\Migration;

/**
 * Создание таблиц для системы технологий (research tree)
 */
class m260104_000001_create_technology_tables extends Migration
{
    public function safeUp()
    {
        // Таблица технологий
        $this->createTable('technology', [
            'technology_id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(128)->notNull(),
            'description' => $this->text(),
            'icon' => $this->string(256),
            'tier' => $this->integer()->unsigned()->notNull()->defaultValue(1),
        ]);

        // Зависимости между технологиями
        $this->createTable('technology_dependency', [
            'technology_id' => $this->integer()->unsigned()->notNull(),
            'required_technology_id' => $this->integer()->unsigned()->notNull(),
        ]);
        $this->addPrimaryKey('pk_technology_dependency', 'technology_dependency', ['technology_id', 'required_technology_id']);
        $this->addForeignKey(
            'fk_tech_dep_technology',
            'technology_dependency',
            'technology_id',
            'technology',
            'technology_id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_tech_dep_required',
            'technology_dependency',
            'required_technology_id',
            'technology',
            'technology_id',
            'CASCADE',
            'CASCADE'
        );

        // Стоимость технологии (в научных пакетах)
        $this->createTable('technology_cost', [
            'technology_id' => $this->integer()->unsigned()->notNull(),
            'resource_id' => $this->integer()->unsigned()->notNull(),
            'quantity' => $this->integer()->unsigned()->notNull(),
        ]);
        $this->addPrimaryKey('pk_technology_cost', 'technology_cost', ['technology_id', 'resource_id']);
        $this->addForeignKey(
            'fk_tech_cost_technology',
            'technology_cost',
            'technology_id',
            'technology',
            'technology_id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_tech_cost_resource',
            'technology_cost',
            'resource_id',
            'resource',
            'resource_id',
            'CASCADE',
            'CASCADE'
        );

        // Разблокируемые рецепты
        $this->createTable('technology_unlock_recipe', [
            'technology_id' => $this->integer()->unsigned()->notNull(),
            'recipe_id' => $this->integer()->unsigned()->notNull(),
        ]);
        $this->addPrimaryKey('pk_tech_unlock_recipe', 'technology_unlock_recipe', ['technology_id', 'recipe_id']);
        $this->addForeignKey(
            'fk_tech_unlock_recipe_tech',
            'technology_unlock_recipe',
            'technology_id',
            'technology',
            'technology_id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_tech_unlock_recipe_recipe',
            'technology_unlock_recipe',
            'recipe_id',
            'recipe',
            'recipe_id',
            'CASCADE',
            'CASCADE'
        );

        // Разблокируемые типы зданий
        $this->createTable('technology_unlock_entity_type', [
            'technology_id' => $this->integer()->unsigned()->notNull(),
            'entity_type_id' => $this->integer()->unsigned()->notNull(),
        ]);
        $this->addPrimaryKey('pk_tech_unlock_entity', 'technology_unlock_entity_type', ['technology_id', 'entity_type_id']);
        $this->addForeignKey(
            'fk_tech_unlock_entity_tech',
            'technology_unlock_entity_type',
            'technology_id',
            'technology',
            'technology_id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_tech_unlock_entity_type',
            'technology_unlock_entity_type',
            'entity_type_id',
            'entity_type',
            'entity_type_id',
            'CASCADE',
            'CASCADE'
        );

        // Изученные технологии пользователя
        $this->createTable('user_technology', [
            'user_id' => $this->integer()->unsigned()->notNull(),
            'technology_id' => $this->integer()->unsigned()->notNull(),
            'researched_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->addPrimaryKey('pk_user_technology', 'user_technology', ['user_id', 'technology_id']);
        $this->addForeignKey(
            'fk_user_tech_user',
            'user_technology',
            'user_id',
            'user',
            'user_id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_user_tech_technology',
            'user_technology',
            'technology_id',
            'technology',
            'technology_id',
            'CASCADE',
            'CASCADE'
        );

        // Индексы
        $this->createIndex('idx_technology_tier', 'technology', 'tier');
        $this->createIndex('idx_user_technology_user', 'user_technology', 'user_id');
    }

    public function safeDown()
    {
        $this->dropTable('user_technology');
        $this->dropTable('technology_unlock_entity_type');
        $this->dropTable('technology_unlock_recipe');
        $this->dropTable('technology_cost');
        $this->dropTable('technology_dependency');
        $this->dropTable('technology');
    }
}
