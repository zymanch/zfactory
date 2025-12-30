<?php

use yii\db\Migration;

/**
 * Class m251230_122834_create_ship_floor_entity_types
 */
class m251230_122834_create_ship_floor_entity_types extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add converts_to_landing_id field to entity_type table (if not exists)
        $tableSchema = $this->db->getTableSchema('{{%entity_type}}');
        if (!isset($tableSchema->columns['converts_to_landing_id'])) {
            $this->addColumn('{{%entity_type}}', 'converts_to_landing_id',
                $this->integer()->unsigned()->null()->after('max_durability')
            );

            $this->addForeignKey(
                'fk_entity_type_converts_landing',
                '{{%entity_type}}',
                'converts_to_landing_id',
                '{{%landing}}',
                'landing_id',
                'SET NULL',
                'CASCADE'
            );
        }

        // Create 5 ship floor entity types (converts to landing when durability reaches max)
        $shipFloorEntities = [
            [
                'entity_type_id' => 600,
                'type' => 'building',
                'name' => 'Деревянный пол корабля',
                'image_url' => 'ship_floor_wood',
                'extension' => 'svg',
                'max_durability' => 100,
                'width' => 1,
                'height' => 1,
                'icon_url' => 'ship_floor_wood/normal.svg',
                'power' => 1,
                'orientation' => 'none',
                'construction_ticks' => 30,
                'converts_to_landing_id' => 13,
            ],
            [
                'entity_type_id' => 601,
                'type' => 'building',
                'name' => 'Железный пол корабля',
                'image_url' => 'ship_floor_iron',
                'extension' => 'svg',
                'max_durability' => 150,
                'width' => 1,
                'height' => 1,
                'icon_url' => 'ship_floor_iron/normal.svg',
                'power' => 1,
                'orientation' => 'none',
                'construction_ticks' => 40,
                'converts_to_landing_id' => 14,
            ],
            [
                'entity_type_id' => 602,
                'type' => 'building',
                'name' => 'Стальной пол корабля',
                'image_url' => 'ship_floor_steel',
                'extension' => 'svg',
                'max_durability' => 200,
                'width' => 1,
                'height' => 1,
                'icon_url' => 'ship_floor_steel/normal.svg',
                'power' => 1,
                'orientation' => 'none',
                'construction_ticks' => 50,
                'converts_to_landing_id' => 15,
            ],
            [
                'entity_type_id' => 603,
                'type' => 'building',
                'name' => 'Титановый пол корабля',
                'image_url' => 'ship_floor_titanium',
                'extension' => 'svg',
                'max_durability' => 300,
                'width' => 1,
                'height' => 1,
                'icon_url' => 'ship_floor_titanium/normal.svg',
                'power' => 1,
                'orientation' => 'none',
                'construction_ticks' => 60,
                'converts_to_landing_id' => 16,
            ],
            [
                'entity_type_id' => 604,
                'type' => 'building',
                'name' => 'Кристаллический пол корабля',
                'image_url' => 'ship_floor_crystal',
                'extension' => 'svg',
                'max_durability' => 500,
                'width' => 1,
                'height' => 1,
                'icon_url' => 'ship_floor_crystal/normal.svg',
                'power' => 1,
                'orientation' => 'none',
                'construction_ticks' => 80,
                'converts_to_landing_id' => 17,
            ],
        ];

        foreach ($shipFloorEntities as $entity) {
            $exists = $this->db->createCommand('SELECT * FROM {{%entity_type}} WHERE entity_type_id = :id', [':id' => $entity['entity_type_id']])->queryOne();
            if (!$exists) {
                $this->insert('{{%entity_type}}', $entity);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m251230_122834_create_ship_floor_entity_types cannot be reverted.\n";

        return false;
    }
}
