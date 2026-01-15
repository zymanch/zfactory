<?php

use yii\db\Migration;

/**
 * Migrates fluid amounts from pipe_system to entity_resource
 * Distributes current_amount evenly across all pipe system members
 */
class m260115_060000_migrate_pipe_system_amounts extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Check if pipe_system tables exist
        $pipeSystemExists = $this->db->schema->getTableSchema('{{%pipe_system}}') !== null;
        $pipeMemberExists = $this->db->schema->getTableSchema('{{%pipe_system_member}}') !== null;

        if (!$pipeSystemExists || !$pipeMemberExists) {
            echo "⚠ pipe_system tables do not exist, skipping migration\n";
            return;
        }

        // Get pipe systems with fluids
        $systems = $this->db->createCommand("
            SELECT
                ps.pipe_system_id,
                ps.resource_id,
                ps.current_amount,
                GROUP_CONCAT(psm.entity_id) as entity_ids
            FROM {{%pipe_system}} ps
            JOIN {{%pipe_system_member}} psm ON ps.pipe_system_id = psm.pipe_system_id
            WHERE ps.current_amount > 0 AND ps.resource_id IS NOT NULL
            GROUP BY ps.pipe_system_id
        ")->queryAll();

        if (empty($systems)) {
            echo "⚠ No pipe systems with fluids to migrate\n";
            return;
        }

        echo "→ Migrating " . count($systems) . " pipe systems...\n";

        $totalMigrated = 0;

        foreach ($systems as $system) {
            $entityIds = explode(',', $system['entity_ids']);
            $amountPerEntity = floor($system['current_amount'] / count($entityIds));

            if ($amountPerEntity == 0) {
                echo "  ⚠ System {$system['pipe_system_id']}: amount too small, skipping\n";
                continue;
            }

            foreach ($entityIds as $entityId) {
                $this->execute("
                    INSERT INTO {{%entity_resource}} (entity_id, resource_id, amount)
                    VALUES (:eid, :rid, :amt)
                    ON DUPLICATE KEY UPDATE
                        amount = amount + VALUES(amount)
                ", [
                    ':eid' => $entityId,
                    ':rid' => $system['resource_id'],
                    ':amt' => $amountPerEntity
                ]);
            }

            $totalMigrated += $system['current_amount'];
            echo "  ✓ System {$system['pipe_system_id']}: distributed {$system['current_amount']} units to " . count($entityIds) . " pipes\n";
        }

        echo "✓ Migrated $totalMigrated total fluid units\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "⚠ Cannot restore pipe_system data automatically\n";
        echo "  Pipe systems will be recalculated on next game load\n";
    }
}
