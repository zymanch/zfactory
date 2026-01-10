<?php

use yii\db\Migration;

/**
 * Add electricity resources and 9 electricity entity types
 */
class m260110_000003_add_electricity_resources_and_types extends Migration
{
    public function safeUp()
    {
        // Cleanup any existing data (in correct order due to FK constraints)
        $this->delete('{{%entity_type_recipe}}', ['IN', 'recipe_id', [500, 501, 502]]);
        $this->delete('{{%recipe}}', ['IN', 'recipe_id', [500, 501, 502]]);
        $this->delete('{{%entity_type}}', ['IN', 'entity_type_id', [900, 901, 902, 910, 911, 912, 920, 921, 922]]);

        // 1. Add resources (use INSERT ON DUPLICATE KEY UPDATE to avoid conflicts)
        $this->execute("
            INSERT INTO {{%resource}} (resource_id, name, icon_url, max_stack) VALUES
            (400, 'Electricity', 'electricity.png', 1000),
            (401, 'Sunlight', 'sunlight.png', 1)
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                icon_url = VALUES(icon_url),
                max_stack = VALUES(max_stack)
        ");

        // 2. Add entity types
        $this->batchInsert('{{%entity_type}}',
            ['entity_type_id', 'type', 'name', 'folder', 'extension', 'max_durability', 'width', 'height', 'icon_url', 'power', 'parent_entity_type_id', 'orientation', 'animation_fps', 'description', 'construction_ticks'],
            [
                // Pylons (power = radius in tiles)
                [900, 'electricity', 'Small Pylon', 'pylon_small', 'png', 100, 1, 1, 'pylon_small/normal.png', 7, NULL, 'none', NULL, 'Малый пилон передачи электричества (радиус: 7)', 60],
                [901, 'electricity', 'Medium Pylon', 'pylon_medium', 'png', 200, 2, 2, 'pylon_medium/normal.png', 15, NULL, 'none', NULL, 'Средний пилон передачи электричества (радиус: 15)', 120],
                [902, 'electricity', 'Large Pylon', 'pylon_large', 'png', 300, 3, 3, 'pylon_large/normal.png', 30, NULL, 'none', NULL, 'Большой пилон передачи электричества (радиус: 30)', 180],

                // Batteries (power = storage capacity)
                [910, 'electricity', 'Small Battery', 'battery_small', 'png', 150, 1, 1, 'battery_small/normal.png', 100, NULL, 'none', NULL, 'Малая батарея (емкость: 100)', 90],
                [911, 'electricity', 'Medium Battery', 'battery_medium', 'png', 250, 2, 2, 'battery_medium/normal.png', 500, NULL, 'none', NULL, 'Средняя батарея (емкость: 500)', 150],
                [912, 'electricity', 'Large Battery', 'battery_large', 'png', 400, 3, 3, 'battery_large/normal.png', 2000, NULL, 'none', NULL, 'Большая батарея (емкость: 2000)', 240],

                // Generators (power = production rate per tick)
                [920, 'electricity', 'Coal Generator', 'generator_coal', 'png', 300, 2, 2, 'generator_coal/normal.png', 10, NULL, 'none', NULL, 'Угольный генератор (выход: 10 электричества)', 150],
                [921, 'electricity', 'Small Solar Panel', 'solar_panel_small', 'png', 100, 1, 1, 'solar_panel_small/normal.png', 5, NULL, 'none', NULL, 'Малая солнечная панель (выход: 5 электричества)', 120],
                [922, 'electricity', 'Large Solar Panel', 'solar_panel_large', 'png', 200, 3, 3, 'solar_panel_large/normal.png', 25, NULL, 'none', NULL, 'Большая солнечная панель (выход: 25 электричества)', 200],
            ]
        );
    }

    public function safeDown()
    {
        // Delete entity types
        $this->delete('{{%entity_type}}', ['IN', 'entity_type_id', [900, 901, 902, 910, 911, 912, 920, 921, 922]]);

        // Delete resources
        $this->delete('{{%resource}}', ['IN', 'resource_id', [400, 401]]);
    }
}
