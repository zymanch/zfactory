<?php

use yii\db\Migration;

/**
 * Class m251230_123134_add_ship_attach_to_region
 */
class m251230_123134_add_ship_attach_to_region extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add ship_attach_x and ship_attach_y to region table
        $this->addColumn('{{%region}}', 'ship_attach_x',
            $this->integer()->null()->comment('Island X coordinate where ship attaches')
        );

        $this->addColumn('{{%region}}', 'ship_attach_y',
            $this->integer()->null()->comment('Island Y coordinate where ship attaches')
        );

        // Set default ship_attach coordinates for existing regions (center of island)
        // This will be manually configured later for each region
        $this->execute("
            UPDATE {{%region}}
            SET ship_attach_x = 0, ship_attach_y = 0
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m251230_123134_add_ship_attach_to_region cannot be reverted.\n";

        return false;
    }
}
