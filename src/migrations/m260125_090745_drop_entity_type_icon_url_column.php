<?php

use yii\db\Migration;

/**
 * Drop icon_url column from entity_type table
 * All icons will be extracted from atlases instead of using separate icon files
 */
class m260125_090745_drop_entity_type_icon_url_column extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('entity_type', 'icon_url');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->addColumn('entity_type', 'icon_url', $this->string(255)->after('height'));
    }
}
