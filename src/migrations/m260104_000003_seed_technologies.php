<?php

use yii\db\Migration;

/**
 * Начальные технологии и их связи
 */
class m260104_000003_seed_technologies extends Migration
{
    public function safeUp()
    {
        // ============ ТЕХНОЛОГИИ ============
        // Tier 1: Red Science
        $this->batchInsert('technology', ['technology_id', 'name', 'description', 'icon', 'tier'], [
            [1, 'Automation', 'Basic automation with conveyors and manipulators', 'automation.svg', 1],
            [2, 'Stone Processing', 'Build furnaces for smelting', 'stone_processing.svg', 1],
        ]);

        // Tier 2: Green Science
        $this->batchInsert('technology', ['technology_id', 'name', 'description', 'icon', 'tier'], [
            [3, 'Logistics', 'Faster transportation systems', 'logistics.svg', 2],
            [4, 'Metallurgy', 'Advanced metal processing', 'metallurgy.svg', 2],
        ]);

        // Tier 3: Blue Science
        $this->batchInsert('technology', ['technology_id', 'name', 'description', 'icon', 'tier'], [
            [5, 'Advanced Logistics', 'Express conveyors and long manipulators', 'advanced_logistics.svg', 3],
            [6, 'Steel Production', 'Produce steel for advanced buildings', 'steel.svg', 3],
        ]);

        // Tier 4: Purple Science
        $this->batchInsert('technology', ['technology_id', 'name', 'description', 'icon', 'tier'], [
            [7, 'Electricity', 'Power generation and distribution', 'electricity.svg', 4],
            [8, 'Military', 'Defense towers and weapons', 'military.svg', 4],
        ]);

        // ============ ЗАВИСИМОСТИ ============
        // Tier 2 requires Tier 1
        $this->batchInsert('technology_dependency', ['technology_id', 'required_technology_id'], [
            [3, 1], // Logistics requires Automation
            [4, 2], // Metallurgy requires Stone Processing
        ]);

        // Tier 3 requires Tier 2
        $this->batchInsert('technology_dependency', ['technology_id', 'required_technology_id'], [
            [5, 3], // Advanced Logistics requires Logistics
            [6, 4], // Steel Production requires Metallurgy
        ]);

        // Tier 4 requires Tier 3
        $this->batchInsert('technology_dependency', ['technology_id', 'required_technology_id'], [
            [7, 6], // Electricity requires Steel Production
            [8, 6], // Military requires Steel Production
        ]);

        // ============ СТОИМОСТЬ (научные пакеты) ============
        // resource_id: 200=Red, 201=Green, 202=Blue, 203=Purple
        $this->batchInsert('technology_cost', ['technology_id', 'resource_id', 'quantity'], [
            // Tier 1: Red Science
            [1, 200, 10], // Automation: 10 Red
            [2, 200, 10], // Stone Processing: 10 Red

            // Tier 2: Green Science
            [3, 201, 20], // Logistics: 20 Green
            [4, 201, 20], // Metallurgy: 20 Green

            // Tier 3: Blue Science
            [5, 202, 30], // Advanced Logistics: 30 Blue
            [6, 202, 30], // Steel Production: 30 Blue

            // Tier 4: Purple Science
            [7, 203, 50], // Electricity: 50 Purple
            [8, 203, 50], // Military: 50 Purple
        ]);

        // ============ РАЗБЛОКИРУЕМЫЕ ЗДАНИЯ ============
        // entity_type_id: 100=Conveyor, 101=Furnace, 103=Assembler, 200=Short Manip, 201=Long Manip
        // 105=Power Pole, 106=Steam Engine, 107=Boiler, 400-402=Crystal Towers
        $this->batchInsert('technology_unlock_entity_type', ['technology_id', 'entity_type_id'], [
            // Automation: Conveyor, Short Manipulator
            [1, 100], // Conveyor Belt
            [1, 200], // Short Manipulator

            // Stone Processing: Small Furnace
            [2, 101], // Small Furnace

            // Logistics: (можно добавить Fast Conveyor когда появится)
            // пока пусто, так как Fast Conveyor ещё не создан

            // Metallurgy: Assembly Machine
            [4, 103], // Assembly Machine

            // Advanced Logistics: Long Manipulator
            [5, 201], // Long Manipulator

            // Electricity: Power infrastructure
            [7, 105], // Power Pole
            [7, 106], // Steam Engine
            [7, 107], // Boiler

            // Military: Crystal Towers
            [8, 400], // Small Crystal Tower
            [8, 401], // Medium Crystal Tower
            [8, 402], // Large Crystal Tower
        ]);

        // ============ РАЗБЛОКИРУЕМЫЕ РЕЦЕПТЫ ============
        // Базовые рецепты добычи доступны всегда (recipe_id 1, 2)
        // Остальные рецепты требуют технологий
        $this->batchInsert('technology_unlock_recipe', ['technology_id', 'recipe_id'], [
            // Stone Processing: базовые рецепты плавки
            [2, 3],  // Iron Ore + Coal → Iron Ingot
            [2, 4],  // Copper Ore + Coal → Copper Ingot
            [2, 17], // Wood → Charcoal

            // Metallurgy: продвинутые рецепты
            [4, 5],  // Iron Ingot + Coal → Steel Plate
            [4, 7],  // Iron Ingot → Iron Plate
            [4, 8],  // Copper Ingot → Copper Plate
            [4, 9],  // Copper Ingot → Copper Wire
            [4, 10], // Iron Plate → Screw
            [4, 11], // Iron Plate → Gear

            // Steel Production: сложные компоненты
            [6, 12], // Gear + Screw → Rotor
            [6, 13], // Copper Wire + Iron Plate → Circuit

            // Electricity: моторы и топливо
            [7, 14], // Rotor + Circuit + Copper Wire → Motor
            [7, 18], // Refined Fuel + Circuit → Fuel Cell
            [7, 19], // Crude Oil → Heavy Oil
            [7, 20], // Heavy Oil → Light Oil
            [7, 21], // Light Oil → Refined Fuel
            [7, 22], // Heavy Oil → Lubricant
        ]);
    }

    public function safeDown()
    {
        $this->delete('technology_unlock_recipe');
        $this->delete('technology_unlock_entity_type');
        $this->delete('technology_cost');
        $this->delete('technology_dependency');
        $this->delete('technology');
    }
}
