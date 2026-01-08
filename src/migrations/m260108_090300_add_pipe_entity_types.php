<?php

use yii\db\Migration;

/**
 * Adds pipe entity types: horizontal/vertical pipes, tanks, underground pipes
 */
class m260108_090300_add_pipe_entity_types extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->batchInsert('entity_type',
            ['entity_type_id', 'type', 'name', 'image_url', 'extension', 'max_durability', 'width', 'height', 'icon_url', 'power', 'parent_entity_type_id', 'orientation', 'animation_fps', 'description', 'construction_ticks'],
            [
                // Horizontal pipe (base)
                [131, 'transporter', 'Pipe', 'pipe', 'png', 100, 1, 1, 'pipe/normal.png', 100, NULL, 'none', NULL, 'Труба для транспортировки жидкостей', 60],

                // Vertical pipe (rotation of horizontal)
                [132, 'transporter', 'Pipe', 'pipe_vertical', 'png', 100, 1, 1, 'pipe_vertical/normal.png', 100, 131, 'none', NULL, 'Труба для транспортировки жидкостей (вертикальная)', 60],

                // Small storage tank (2x2)
                [135, 'storage', 'Small Tank', 'tank_small', 'png', 300, 2, 2, 'tank_small/normal.png', 5000, NULL, 'none', NULL, 'Маленький резервуар для жидкостей (5000 единиц)', 180],

                // Large storage tank (3x3)
                [136, 'storage', 'Large Tank', 'tank_large', 'png', 500, 3, 3, 'tank_large/normal.png', 25000, NULL, 'none', NULL, 'Большой резервуар для жидкостей (25000 единиц)', 300],

                // Underground pipe entrance
                [140, 'transporter', 'Underground Pipe (In)', 'underground_pipe_in', 'png', 150, 1, 1, 'underground_pipe_in/normal.png', 100, NULL, 'none', NULL, 'Вход подземной трубы', 90],

                // Underground pipe exit
                [141, 'transporter', 'Underground Pipe (Out)', 'underground_pipe_out', 'png', 150, 1, 1, 'underground_pipe_out/normal.png', 100, NULL, 'none', NULL, 'Выход подземной трубы', 90],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('entity_type', ['entity_type_id' => [131, 132, 135, 136, 140, 141]]);
    }
}
