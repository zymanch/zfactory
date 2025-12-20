<?php

use yii\db\Migration;

/**
 * Adds resources to ore entities
 */
class m251219_140000_add_ore_resources extends Migration
{
    public function safeUp()
    {
        // Get all Iron Ore entities (entity_type_id = 300) and add Iron Ore resource (resource_id = 2)
        $ironOreEntities = (new \yii\db\Query())
            ->select('entity_id')
            ->from('entity')
            ->where(['entity_type_id' => 300])
            ->column();

        foreach ($ironOreEntities as $entityId) {
            $this->insert('entity_resource', [
                'entity_id' => $entityId,
                'resource_id' => 2, // Iron Ore resource
                'amount' => rand(1000, 5000),
            ]);
        }

        // Get all Copper Ore entities (entity_type_id = 301) and add Copper Ore resource (resource_id = 3)
        $copperOreEntities = (new \yii\db\Query())
            ->select('entity_id')
            ->from('entity')
            ->where(['entity_type_id' => 301])
            ->column();

        foreach ($copperOreEntities as $entityId) {
            $this->insert('entity_resource', [
                'entity_id' => $entityId,
                'resource_id' => 3, // Copper Ore resource
                'amount' => rand(1000, 5000),
            ]);
        }
    }

    public function safeDown()
    {
        $this->delete('entity_resource', ['resource_id' => [2, 3]]);
    }
}
