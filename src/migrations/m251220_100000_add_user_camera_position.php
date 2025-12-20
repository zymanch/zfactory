<?php

use yii\db\Migration;

/**
 * Adds camera_x, camera_y, zoom columns to user table
 * for persistent camera position between sessions
 */
class m251220_100000_add_user_camera_position extends Migration
{
    public function safeUp()
    {
        $this->addColumn('user', 'camera_x', $this->integer()->notNull()->defaultValue(0)->after('build_panel'));
        $this->addColumn('user', 'camera_y', $this->integer()->notNull()->defaultValue(0)->after('camera_x'));
        $this->addColumn('user', 'zoom', $this->float()->notNull()->defaultValue(1)->after('camera_y'));
    }

    public function safeDown()
    {
        $this->dropColumn('user', 'zoom');
        $this->dropColumn('user', 'camera_y');
        $this->dropColumn('user', 'camera_x');
    }
}
