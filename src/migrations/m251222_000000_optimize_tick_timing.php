<?php

use yii\db\Migration;

/**
 * Migration: Optimize tick timing for logic/animation separation
 *
 * Updates:
 * - entity_type.power: 1 → 100 for conveyors, manipulators, and production buildings
 *   (formulas expect power=100 as baseline)
 * - recipe.ticks: adjusted to multiples of 30 (logic tick runs every 30 frames)
 */
class m251222_000000_optimize_tick_timing extends Migration
{
    public function safeUp()
    {
        // 1. Update power values for conveyors (power=100 means 1 tile per 60 ticks)
        $this->update('entity_type', ['power' => 100], ['entity_type_id' => [100, 120, 121, 122]]);

        // 2. Update power values for manipulators (power=100 means full swing in 30 ticks)
        $this->update('entity_type', ['power' => 100], ['entity_type_id' => [200, 201, 210, 211, 212, 213, 214, 215]]);

        // 3. Update power values for production buildings (power=100 means baseline craft speed)
        $this->update('entity_type', ['power' => 100], ['entity_type_id' => [101, 103, 107]]);  // Furnace, Assembler, Boiler

        // 4. Update power values for mining buildings
        $this->update('entity_type', ['power' => 100], ['entity_type_id' => 102]);  // Mining Drill
        $this->update('entity_type', ['power' => 150], ['entity_type_id' => 108]);  // Fast Mining Drill (1.5x speed)

        // 5. Update recipe ticks to multiples of 30
        // Assembly: 20 → 30 (Copper Wire, Screw)
        $this->update('recipe', ['ticks' => 30], ['recipe_id' => [9, 10]]);

        // Assembly: 40 → 30 (Iron Plate, Copper Plate)
        $this->update('recipe', ['ticks' => 30], ['recipe_id' => [7, 8]]);

        // Assembly: 45 → 60 (Crystal)
        $this->update('recipe', ['ticks' => 60], ['recipe_id' => 15]);

        // Assembly: 50 → 60 (Circuit)
        $this->update('recipe', ['ticks' => 60], ['recipe_id' => 13]);

        // Assembly: 80 → 90 (Motor)
        $this->update('recipe', ['ticks' => 90], ['recipe_id' => 14]);

        // Assembly: 100 → 120 (Fuel Cell)
        $this->update('recipe', ['ticks' => 120], ['recipe_id' => 16]);

        // Boiler: 40 → 30 (Light Oil)
        $this->update('recipe', ['ticks' => 30], ['recipe_id' => 18]);

        // Boiler: 50 → 60 (Lubricant)
        $this->update('recipe', ['ticks' => 60], ['recipe_id' => 20]);
    }

    public function safeDown()
    {
        // Revert power values
        $this->update('entity_type', ['power' => 1], ['entity_type_id' => [100, 120, 121, 122]]);
        $this->update('entity_type', ['power' => 1], ['entity_type_id' => [200, 201, 210, 211, 212, 213, 214, 215]]);
        $this->update('entity_type', ['power' => 1], ['entity_type_id' => [101, 102, 103, 107, 108]]);

        // Revert recipe ticks
        $this->update('recipe', ['ticks' => 20], ['recipe_id' => [9, 10]]);
        $this->update('recipe', ['ticks' => 40], ['recipe_id' => [7, 8, 18]]);
        $this->update('recipe', ['ticks' => 45], ['recipe_id' => 15]);
        $this->update('recipe', ['ticks' => 50], ['recipe_id' => [13, 20]]);
        $this->update('recipe', ['ticks' => 80], ['recipe_id' => 14]);
        $this->update('recipe', ['ticks' => 100], ['recipe_id' => 16]);
    }
}
