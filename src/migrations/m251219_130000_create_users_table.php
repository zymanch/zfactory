<?php

use yii\db\Migration;

/**
 * Migration: Create users table with build_panel storage
 */
class m251219_130000_create_users_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('user', [
            'user_id' => $this->primaryKey()->unsigned(),
            'username' => $this->string(64)->notNull()->unique(),
            'password' => $this->string(255)->notNull(),
            'email' => $this->string(128)->notNull()->unique(),
            'build_panel' => $this->text()->null()->comment('JSON array of entity_type_ids for 10 slots'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Insert default user
        $this->insert('user', [
            'user_id' => 1,
            'username' => 'player1',
            'password' => password_hash('player1', PASSWORD_DEFAULT),
            'email' => 'player1@zfactory.local',
            'build_panel' => json_encode([100, 101, 102, 103, 104, 105, 106, 107, null, null]),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('user');
    }
}
