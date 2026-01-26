<?php

use yii\db\Migration;

/**
 * Adds width_overflow and height_overflow fields to entity_type table
 * for manipulator animation system
 */
class m260125_120000_add_manipulator_overflow_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add overflow columns
        $this->addColumn('{{%entity_type}}', 'width_overflow', $this->integer()->defaultValue(0)->after('width'));
        $this->addColumn('{{%entity_type}}', 'height_overflow', $this->integer()->defaultValue(0)->after('height'));

        // Set overflow values for SHORT manipulators (right/left)
        $this->update('{{%entity_type}}', ['width_overflow' => 2], [
            'entity_type_id' => [200, 212]
        ]);

        // Set overflow values for SHORT manipulators (up/down)
        $this->update('{{%entity_type}}', ['height_overflow' => 2], [
            'entity_type_id' => [210, 211]
        ]);

        // Set overflow values for LONG manipulators (right/left)
        $this->update('{{%entity_type}}', ['width_overflow' => 4], [
            'entity_type_id' => [201, 215]
        ]);

        // Set overflow values for LONG manipulators (up/down)
        $this->update('{{%entity_type}}', ['height_overflow' => 4], [
            'entity_type_id' => [213, 214]
        ]);

        // Set overflow values for FILTERED 1F manipulators (right/left)
        $this->update('{{%entity_type}}', ['width_overflow' => 2], [
            'entity_type_id' => [216, 416]
        ]);

        // Set overflow values for FILTERED 1F manipulators (up/down)
        $this->update('{{%entity_type}}', ['height_overflow' => 2], [
            'entity_type_id' => [316, 516]
        ]);

        // Set overflow values for FILTERED 5F manipulators (right/left)
        $this->update('{{%entity_type}}', ['width_overflow' => 2], [
            'entity_type_id' => [220, 420]
        ]);

        // Set overflow values for FILTERED 5F manipulators (up/down)
        $this->update('{{%entity_type}}', ['height_overflow' => 2], [
            'entity_type_id' => [320, 520]
        ]);

        // Set overflow values for COUNTING 1F manipulators (right/left)
        $this->update('{{%entity_type}}', ['width_overflow' => 2], [
            'entity_type_id' => [221, 421]
        ]);

        // Set overflow values for COUNTING 1F manipulators (up/down)
        $this->update('{{%entity_type}}', ['height_overflow' => 2], [
            'entity_type_id' => [321, 521]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%entity_type}}', 'height_overflow');
        $this->dropColumn('{{%entity_type}}', 'width_overflow');
    }
}
