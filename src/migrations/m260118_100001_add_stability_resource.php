<?php

use yii\db\Migration;

/**
 * Adds Stability resource (resource_id=450) for stabilizer buildings
 */
class m260118_100001_add_stability_resource extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->insert('{{%resource}}', [
            'resource_id' => 450,
            'name' => 'Stability',
            'icon_url' => 'stability.png',
            'type' => 'crafted',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%resource}}', ['resource_id' => 450]);
    }
}
