<?php

use yii\db\Migration;

/**
 * Add max_stack column to resource table
 * Defines maximum stack size for each resource type
 */
class m251221_000000_add_resource_max_stack extends Migration
{
    public function safeUp()
    {
        $this->addColumn('resource', 'max_stack', $this->integer()->unsigned()->notNull()->defaultValue(100)->after('type'));

        // Set different stack sizes for some resources
        $this->update('resource', ['max_stack' => 50], ['type' => 'crafted']);
        $this->update('resource', ['max_stack' => 200], ['type' => 'raw']);
        $this->update('resource', ['max_stack' => 100], ['type' => 'liquid']);
        $this->update('resource', ['max_stack' => 10000], ['type' => 'deposit']); // deposits are large
    }

    public function safeDown()
    {
        $this->dropColumn('resource', 'max_stack');
    }
}
