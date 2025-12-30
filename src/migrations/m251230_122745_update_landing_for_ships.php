<?php

use yii\db\Migration;

/**
 * Class m251230_122745_update_landing_for_ships
 */
class m251230_122745_update_landing_for_ships extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add 'type' column to landing table (if not exists)
        $tableSchema = $this->db->getTableSchema('{{%landing}}');
        if (!isset($tableSchema->columns['type'])) {
            $this->addColumn('{{%landing}}', 'type',
                "ENUM('sky', 'island', 'ship', 'bridge') NOT NULL DEFAULT 'island' AFTER landing_id"
            );
        }

        // Update existing island landings (id 1-8, 10)
        $this->update('{{%landing}}', ['type' => 'island'], ['landing_id' => [1,2,3,4,5,6,7,8,10]]);

        // Move Sky from id=9 to id=11 (if not already done)
        $skyAt9 = $this->db->createCommand('SELECT * FROM {{%landing}} WHERE landing_id = 9')->queryOne();
        $skyAt11 = $this->db->createCommand('SELECT * FROM {{%landing}} WHERE landing_id = 11')->queryOne();

        if ($skyAt9 && !$skyAt11) {
            // Update existing Sky landing (id=9) to set its type
            $this->update('{{%landing}}', ['type' => 'sky'], ['landing_id' => 9]);

            // Delete the existing Sky at id=9
            $this->delete('{{%landing}}', ['landing_id' => 9]);

            // Insert Sky at id=11 (moved from id=9)
            $this->insert('{{%landing}}', [
                'landing_id' => 11,
                'type' => 'sky',
                'name' => 'Sky',
                'folder' => 'sky',
                'variations_count' => 1,
            ]);
        }

        // Insert Bridge landing at id=9 (if not exists)
        $bridgeExists = $this->db->createCommand('SELECT * FROM {{%landing}} WHERE landing_id = 9')->queryOne();
        if (!$bridgeExists) {
            $this->insert('{{%landing}}', [
                'landing_id' => 9,
                'type' => 'bridge',
                'name' => 'Мост',
                'folder' => 'bridge',
                'variations_count' => 1,
            ]);
        }

        // Insert Ship Edge landing (landing_id=12) if not exists
        $shipEdgeExists = $this->db->createCommand('SELECT * FROM {{%landing}} WHERE landing_id = 12')->queryOne();
        if (!$shipEdgeExists) {
            $this->insert('{{%landing}}', [
                'landing_id' => 12,
                'type' => 'ship',
                'name' => 'Ship Edge',
                'folder' => 'ship_edge',
                'variations_count' => 1,
            ]);
        }

        // Insert 5 ship floor types (landing_id=13-17) if not exist
        $shipFloorTypes = [
            ['id' => 13, 'name' => 'Деревянный пол корабля', 'folder' => 'ship_floor_wood'],
            ['id' => 14, 'name' => 'Железный пол корабля', 'folder' => 'ship_floor_iron'],
            ['id' => 15, 'name' => 'Стальной пол корабля', 'folder' => 'ship_floor_steel'],
            ['id' => 16, 'name' => 'Титановый пол корабля', 'folder' => 'ship_floor_titanium'],
            ['id' => 17, 'name' => 'Кристаллический пол корабля', 'folder' => 'ship_floor_crystal'],
        ];

        foreach ($shipFloorTypes as $floor) {
            $exists = $this->db->createCommand('SELECT * FROM {{%landing}} WHERE landing_id = :id', [':id' => $floor['id']])->queryOne();
            if (!$exists) {
                $this->insert('{{%landing}}', [
                    'landing_id' => $floor['id'],
                    'type' => 'ship',
                    'name' => $floor['name'],
                    'folder' => $floor['folder'],
                    'variations_count' => 1,
                ]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m251230_122745_update_landing_for_ships cannot be reverted.\n";

        return false;
    }
}
