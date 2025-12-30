<?php

use yii\db\Migration;

/**
 * Populate ship_attach_x and ship_attach_y for all regions
 * ship_attach_x = MAX(map.x for region) + 1
 * ship_attach_y = (MIN(map.y for region) + MAX(map.y for region)) / 2
 */
class m251230_130000_populate_ship_attach_coordinates extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Get all regions
        $regions = $this->db->createCommand('SELECT region_id FROM {{%region}}')->queryAll();

        foreach ($regions as $region) {
            $regionId = $region['region_id'];

            // Get max X coordinate for this region
            $maxX = $this->db->createCommand(
                'SELECT MAX(x) as max_x FROM {{%map}} WHERE region_id = :region_id',
                [':region_id' => $regionId]
            )->queryScalar();

            // Get min and max Y coordinates for this region
            $minMaxY = $this->db->createCommand(
                'SELECT MIN(y) as min_y, MAX(y) as max_y FROM {{%map}} WHERE region_id = :region_id',
                [':region_id' => $regionId]
            )->queryOne();

            // Calculate ship attach coordinates
            $shipAttachX = $maxX !== null ? (int)$maxX + 1 : 0;
            $shipAttachY = 0;

            if ($minMaxY && $minMaxY['min_y'] !== null && $minMaxY['max_y'] !== null) {
                $shipAttachY = (int)round(((int)$minMaxY['min_y'] + (int)$minMaxY['max_y']) / 2);
            }

            // Update region
            $this->update('{{%region}}', [
                'ship_attach_x' => $shipAttachX,
                'ship_attach_y' => $shipAttachY,
            ], ['region_id' => $regionId]);

            echo "Region {$regionId}: ship_attach_x={$shipAttachX}, ship_attach_y={$shipAttachY}\n";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Reset ship attach coordinates to NULL
        $this->update('{{%region}}', [
            'ship_attach_x' => null,
            'ship_attach_y' => null,
        ]);

        return true;
    }
}
