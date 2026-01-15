<?php

use yii\db\Migration;

/**
 * Migrates data from splitter_state table to entity_resource.last_output_direction
 */
class m260115_050000_migrate_splitter_state extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Check if splitter_state table exists
        $tableExists = $this->db->schema->getTableSchema('{{%splitter_state}}') !== null;

        if (!$tableExists) {
            echo "⚠ splitter_state table does not exist, skipping migration\n";
            return;
        }

        // Get count of splitter states to migrate
        $count = $this->db->createCommand(
            "SELECT COUNT(*) FROM {{%splitter_state}}"
        )->queryScalar();

        if ($count == 0) {
            echo "⚠ No splitter states to migrate\n";
            return;
        }

        echo "→ Migrating $count splitter states...\n";

        // Migrate splitter_state data to entity_resource
        $this->execute("
            INSERT INTO {{%entity_resource}} (entity_id, resource_id, amount, last_output_direction)
            SELECT
                ss.entity_id,
                1 AS resource_id,
                0 AS amount,
                ss.last_output_direction
            FROM {{%splitter_state}} ss
            LEFT JOIN {{%entity_resource}} er ON er.entity_id = ss.entity_id
            WHERE er.entity_id IS NULL
            ON DUPLICATE KEY UPDATE
                last_output_direction = VALUES(last_output_direction)
        ");

        echo "✓ Migrated $count splitter states to entity_resource\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Check if splitter_state table exists
        $tableExists = $this->db->schema->getTableSchema('{{%splitter_state}}') !== null;

        if (!$tableExists) {
            echo "⚠ splitter_state table does not exist, cannot restore data\n";
            return;
        }

        // Restore data back to splitter_state
        $this->execute("
            INSERT INTO {{%splitter_state}} (entity_id, last_output_direction)
            SELECT entity_id, last_output_direction
            FROM {{%entity_resource}}
            WHERE last_output_direction IS NOT NULL
            ON DUPLICATE KEY UPDATE
                last_output_direction = VALUES(last_output_direction)
        ");

        // Clear last_output_direction from entity_resource
        $this->execute("
            UPDATE {{%entity_resource}}
            SET last_output_direction = NULL
            WHERE last_output_direction IS NOT NULL
        ");

        echo "✓ Restored data back to splitter_state\n";
    }
}
