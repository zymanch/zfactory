<?php

use yii\db\Migration;

/**
 * Adds is_admin column to user table
 * for administrator access control
 */
class m260102_000000_add_is_admin_to_user extends Migration
{
    public function safeUp()
    {
        $this->addColumn('user', 'is_admin', $this->boolean()->notNull()->defaultValue(false)->after('email'));

        // Set first user as admin
        $this->update('user', ['is_admin' => true], ['user_id' => 1]);
    }

    public function safeDown()
    {
        $this->dropColumn('user', 'is_admin');
    }
}
