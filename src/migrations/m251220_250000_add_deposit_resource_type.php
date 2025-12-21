<?php

use yii\db\Migration;

/**
 * Add 'deposit' type for abstract immovable resources (Iron Deposit, Copper Deposit)
 */
class m251220_250000_add_deposit_resource_type extends Migration
{
    public function safeUp()
    {
        // Add 'deposit' to resource.type enum
        $this->execute("ALTER TABLE resource MODIFY COLUMN type ENUM('raw','liquid','crafted','deposit') NOT NULL");

        // Update Iron Deposit and Copper Deposit to new type
        $this->update('resource', ['type' => 'deposit'], ['resource_id' => 8]);
        $this->update('resource', ['type' => 'deposit'], ['resource_id' => 9]);
    }

    public function safeDown()
    {
        // Revert to raw type
        $this->update('resource', ['type' => 'raw'], ['resource_id' => 8]);
        $this->update('resource', ['type' => 'raw'], ['resource_id' => 9]);

        // Remove 'deposit' from enum
        $this->execute("ALTER TABLE resource MODIFY COLUMN type ENUM('raw','liquid','crafted') NOT NULL");
    }
}
