<?php

use yii\db\Migration;

/**
 * Fix bridge type from 'bridge' to 'island'
 * Remove 'bridge' from type ENUM
 */
class m251230_180000_fix_bridge_type extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Update bridge type to 'island'
        $this->update('{{%landing}}', ['type' => 'island'], ['landing_id' => 9]);

        // Remove 'bridge' from ENUM (restore original ENUM without 'bridge')
        $this->execute("
            ALTER TABLE {{%landing}}
            MODIFY COLUMN `type` ENUM('island','ship','sky') NOT NULL
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m251230_180000_fix_bridge_type cannot be reverted.\n";

        return false;
    }
}
