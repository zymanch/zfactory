<?php

use yii\db\Migration;

/**
 * Add 'electricity' type to entity_type.type ENUM and split 'transporter' into 'conveyor'/'pipe'/'electricity'
 */
class m260110_000001_add_electricity_to_entity_type extends Migration
{
    public function safeUp()
    {
        // 1. Add new types to ENUM (including electricity)
        $this->alterColumn('{{%entity_type}}', 'type',
            "ENUM('building','conveyor','pipe','electricity','manipulator','tree','relief','resource','eye','mining','storage','refinery','hq','ship') NOT NULL"
        );

        // 2. Update existing transporters to specific types

        // Conveyors: basic (100, 120-130), splitters (800-811), underground (812-835)
        $conveyorIds = array_merge(
            [100], // Base conveyor
            range(120, 130), // Variants + dual + fast
            range(800, 811), // Splitters (3 types × 4 orientations)
            range(812, 835)  // Underground conveyors (6 types × 4 orientations)
        );

        $this->update('{{%entity_type}}',
            ['type' => 'conveyor'],
            ['IN', 'entity_type_id', $conveyorIds]
        );

        // Pipes: horizontal/vertical pipes (131-132), underground pipes (140-141)
        $pipeIds = [131, 132, 140, 141];

        $this->update('{{%entity_type}}',
            ['type' => 'pipe'],
            ['IN', 'entity_type_id', $pipeIds]
        );

        // Note: Tanks (135, 136) remain as 'storage' type
    }

    public function safeDown()
    {
        // Revert all conveyors and pipes back to 'transporter'
        $conveyorIds = array_merge(
            [100],
            range(120, 130),
            range(800, 811),
            range(812, 835)
        );

        $pipeIds = [131, 132, 140, 141];

        $allTransporterIds = array_merge($conveyorIds, $pipeIds);

        $this->update('{{%entity_type}}',
            ['type' => 'transporter'],
            ['IN', 'entity_type_id', $allTransporterIds]
        );

        // Remove electricity type from ENUM
        $this->alterColumn('{{%entity_type}}', 'type',
            "ENUM('building','transporter','manipulator','tree','relief','resource','eye','mining','storage','refinery','hq','ship') NOT NULL"
        );
    }
}
