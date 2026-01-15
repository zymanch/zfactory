<?php

use yii\db\Migration;

/**
 * Drops obsolete tables after migrating to client-side pipe system calculation
 * These tables are no longer needed after PHASE 1-3 refactoring
 */
class m260115_100000_drop_obsolete_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Drop tables in correct order (respect foreign keys)

        // Drop pipe_system_member first (has FK to pipe_system)
        if ($this->db->schema->getTableSchema('{{%pipe_system_member}}') !== null) {
            $this->dropTable('{{%pipe_system_member}}');
            echo "✓ Dropped pipe_system_member table\n";
        } else {
            echo "⚠ pipe_system_member table does not exist\n";
        }

        // Drop pipe_system
        if ($this->db->schema->getTableSchema('{{%pipe_system}}') !== null) {
            $this->dropTable('{{%pipe_system}}');
            echo "✓ Dropped pipe_system table\n";
        } else {
            echo "⚠ pipe_system table does not exist\n";
        }

        // Drop underground_link
        if ($this->db->schema->getTableSchema('{{%underground_link}}') !== null) {
            $this->dropTable('{{%underground_link}}');
            echo "✓ Dropped underground_link table\n";
        } else {
            echo "⚠ underground_link table does not exist\n";
        }

        // Drop splitter_state
        if ($this->db->schema->getTableSchema('{{%splitter_state}}') !== null) {
            $this->dropTable('{{%splitter_state}}');
            echo "✓ Dropped splitter_state table\n";
        } else {
            echo "⚠ splitter_state table does not exist\n";
        }

        echo "✓ All obsolete tables have been dropped\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "⚠ Cannot recreate dropped tables - this migration is irreversible\n";
        echo "  If you need to restore, use database backup from before migration\n";
        return false;
    }
}
