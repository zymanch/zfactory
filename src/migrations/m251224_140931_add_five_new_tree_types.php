<?php

use yii\db\Migration;

/**
 * Add 5 new tree types with heights 2-3
 */
class m251224_140931_add_five_new_tree_types extends Migration
{
    public function safeUp()
    {
        // Delete tree_birch if it was partially inserted with wrong ID
        $this->delete('entity_type', ['image_url' => 'tree_birch']);

        $trees = [
            [
                'entity_type_id' => 4,
                'type' => 'tree',
                'name' => 'Birch Tree',
                'image_url' => 'tree_birch',
                'extension' => 'svg',
                'width' => 1,
                'height' => 2,
                'max_durability' => 20,
            ],
            [
                'entity_type_id' => 5,
                'type' => 'tree',
                'name' => 'Willow Tree',
                'image_url' => 'tree_willow',
                'extension' => 'svg',
                'width' => 1,
                'height' => 3,
                'max_durability' => 25,
            ],
            [
                'entity_type_id' => 6,
                'type' => 'tree',
                'name' => 'Maple Tree',
                'image_url' => 'tree_maple',
                'extension' => 'svg',
                'width' => 1,
                'height' => 2,
                'max_durability' => 20,
            ],
            [
                'entity_type_id' => 7,
                'type' => 'tree',
                'name' => 'Spruce Tree',
                'image_url' => 'tree_spruce',
                'extension' => 'svg',
                'width' => 1,
                'height' => 3,
                'max_durability' => 25,
            ],
            [
                'entity_type_id' => 8,
                'type' => 'tree',
                'name' => 'Ash Tree',
                'image_url' => 'tree_ash',
                'extension' => 'svg',
                'width' => 1,
                'height' => 2,
                'max_durability' => 20,
            ],
        ];

        foreach ($trees as $tree) {
            $this->insert('entity_type', $tree);
        }
    }

    public function safeDown()
    {
        $this->delete('entity_type', ['image_url' => [
            'tree_birch',
            'tree_willow',
            'tree_maple',
            'tree_spruce',
            'tree_ash',
        ]]);
    }
}
