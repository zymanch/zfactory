<?php

use yii\db\Migration;

/**
 * Drop electricity_system and electricity_system_member tables
 * Transfer electricity data to entity_resource before dropping
 */
class m260111_100000_drop_electricity_tables extends Migration
{
    public function safeUp()
    {
        echo "Starting migration: transfer electricity to entity_resource and drop tables\n";

        // Get all electricity systems
        $systems = (new \yii\db\Query())
            ->select(['system_id', 'total_electricity', 'total_capacity'])
            ->from('{{%electricity_system}}')
            ->all();

        echo "Found " . count($systems) . " electricity systems\n";

        foreach ($systems as $system) {
            $systemId = (int)$system['system_id'];
            $totalElectricity = (int)$system['total_electricity'];

            if ($totalElectricity <= 0) {
                echo "System {$systemId}: No electricity to transfer\n";
                continue;
            }

            // Get all batteries in this system
            $batteries = (new \yii\db\Query())
                ->select(['esm.entity_id', 'et.power'])
                ->from(['esm' => '{{%electricity_system_member}}'])
                ->innerJoin(['e' => '{{%entity}}'], 'e.entity_id = esm.entity_id')
                ->innerJoin(['et' => '{{%entity_type}}'], 'et.entity_type_id = e.entity_type_id')
                ->where(['esm.system_id' => $systemId])
                ->andWhere(['esm.role' => 'battery'])
                ->all();

            if (empty($batteries)) {
                echo "System {$systemId}: No batteries found, electricity will be lost\n";
                continue;
            }

            echo "System {$systemId}: Distributing {$totalElectricity} electricity to " . count($batteries) . " batteries\n";

            $remainingElectricity = $totalElectricity;

            // Fill batteries sequentially
            foreach ($batteries as $battery) {
                if ($remainingElectricity <= 0) break;

                $entityId = (int)$battery['entity_id'];
                $capacity = (int)$battery['power'];

                // Check if battery already has electricity in entity_resource
                $existingElectricity = (new \yii\db\Query())
                    ->select(['amount'])
                    ->from('{{%entity_resource}}')
                    ->where(['entity_id' => $entityId, 'resource_id' => 400])
                    ->andWhere(['position_px' => null]) // Not transport state
                    ->scalar();

                $currentAmount = $existingElectricity ? (int)$existingElectricity : 0;
                $freeSpace = $capacity - $currentAmount;

                if ($freeSpace <= 0) {
                    echo "  Battery {$entityId}: Already full ({$currentAmount}/{$capacity})\n";
                    continue;
                }

                $toTransfer = min($remainingElectricity, $freeSpace);
                $newAmount = $currentAmount + $toTransfer;

                if ($existingElectricity) {
                    // Update existing record
                    $this->update(
                        '{{%entity_resource}}',
                        ['amount' => $newAmount],
                        ['entity_id' => $entityId, 'resource_id' => 400, 'position_px' => null]
                    );
                } else {
                    // Insert new record
                    $this->insert('{{%entity_resource}}', [
                        'entity_id' => $entityId,
                        'resource_id' => 400,
                        'amount' => $newAmount,
                        'position_px' => null,
                        'from_direction' => null,
                        'status' => null
                    ]);
                }

                echo "  Battery {$entityId}: Added {$toTransfer} electricity ({$currentAmount} -> {$newAmount}/{$capacity})\n";

                $remainingElectricity -= $toTransfer;
            }

            if ($remainingElectricity > 0) {
                echo "System {$systemId}: WARNING - {$remainingElectricity} electricity could not be transferred (batteries full)\n";
            }
        }

        echo "\nDropping electricity system tables...\n";

        // Drop foreign keys first
        $this->dropForeignKey('fk-electricity_system_member-entity_id', '{{%electricity_system_member}}');
        $this->dropForeignKey('fk-electricity_system_member-system_id', '{{%electricity_system_member}}');
        $this->dropIndex('idx-electricity_system_member-entity_id', '{{%electricity_system_member}}');
        $this->dropIndex('idx-electricity_system_member-system_id', '{{%electricity_system_member}}');

        $this->dropTable('{{%electricity_system_member}}');

        $this->dropForeignKey('fk-electricity_system-region_id', '{{%electricity_system}}');
        $this->dropIndex('idx-electricity_system-region_id', '{{%electricity_system}}');

        $this->dropTable('{{%electricity_system}}');

        echo "Migration completed successfully\n";
    }

    public function safeDown()
    {
        echo "Cannot reverse this migration - electricity data transfer is irreversible\n";
        return false;
    }
}
