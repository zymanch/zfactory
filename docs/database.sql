-- --------------------------------------------------------
-- ZFactory Database Dump
-- --------------------------------------------------------
-- Host:                         localhost
-- Server version:               10.5.11-MariaDB
-- Database:                     zfactory
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- --------------------------------------------------------
-- Table structure: landing (terrain types)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `landing` (
  `landing_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `is_buildable` enum('yes','no') NOT NULL DEFAULT 'yes',
  `name` varchar(256) NOT NULL,
  `folder` varchar(256) NOT NULL,
  `variations_count` int(11) NOT NULL DEFAULT 5,
  PRIMARY KEY (`landing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `landing` (`landing_id`, `is_buildable`, `name`, `folder`) VALUES
    (1, 'yes', 'Grass', 'grass'),
    (2, 'yes', 'Dirt', 'dirt'),
    (3, 'yes', 'Sand', 'sand'),
    (4, 'no', 'Water', 'water'),
    (5, 'no', 'Stone', 'stone'),
    (6, 'no', 'Lava', 'lava'),
    (7, 'yes', 'Snow', 'snow'),
    (8, 'no', 'Swamp', 'swamp'),
    (9, 'no', 'Sky', 'sky'),
    (10, 'no', 'Island Edge', 'island_edge');


-- --------------------------------------------------------
-- Table structure: landing_adjacency (natural terrain transitions)
-- --------------------------------------------------------
-- Note: This table is currently not used for atlas generation.
-- Atlas system now generates ALL possible transitions using landing_id directly.
CREATE TABLE IF NOT EXISTS `landing_adjacency` (
  `adjacency_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `landing_id_1` int(10) unsigned NOT NULL,
  `landing_id_2` int(10) unsigned NOT NULL,
  PRIMARY KEY (`adjacency_id`),
  UNIQUE KEY `idx_unique_pair` (`landing_id_1`, `landing_id_2`),
  CONSTRAINT `fk_landing_adjacency_1` FOREIGN KEY (`landing_id_1`) REFERENCES `landing` (`landing_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_landing_adjacency_2` FOREIGN KEY (`landing_id_2`) REFERENCES `landing` (`landing_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Natural transitions (max 3 per type, excluding sky and island_edge)
INSERT INTO `landing_adjacency` (`landing_id_1`, `landing_id_2`) VALUES
    (1, 2),   -- grass-dirt
    (1, 3),   -- grass-sand
    (1, 7),   -- grass-snow
    (1, 8),   -- grass-swamp
    (2, 3),   -- dirt-sand
    (2, 5),   -- dirt-stone
    (3, 4),   -- sand-water
    (4, 6),   -- water-lava
    (4, 8),   -- water-swamp
    (5, 6),   -- stone-lava
    (5, 7);   -- stone-snow


-- --------------------------------------------------------
-- Table structure: entity_type (entity definitions)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `entity_type` (
  `entity_type_id` int(10) unsigned NOT NULL PRIMARY KEY,
  `type` enum('building','transporter','manipulator','tree','relief','resource','eye','mining','storage') NOT NULL,
  `name` varchar(128) NOT NULL,
  `image_url` varchar(256) NOT NULL,
  `extension` varchar(4) NOT NULL DEFAULT 'svg',
  `max_durability` int(11) unsigned NOT NULL DEFAULT 100,
  `width` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `height` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `icon_url` varchar(256) DEFAULT NULL,
  `power` int(10) unsigned NOT NULL DEFAULT 1,
  `parent_entity_type_id` int(10) unsigned DEFAULT NULL,
  `orientation` enum('none','up','right','down','left') NOT NULL DEFAULT 'none',
  `animation_fps` decimal(5,2) DEFAULT NULL COMMENT 'Animation speed in frames per second. NULL = no animation',
  `description` text DEFAULT NULL COMMENT 'Описание entity на русском языке',
  `construction_ticks` int(11) NOT NULL DEFAULT 60 COMMENT 'Количество тиков для строительства'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `entity_type` (`entity_type_id`, `type`, `name`, `image_url`, `extension`, `max_durability`, `width`, `height`, `icon_url`, `power`, `parent_entity_type_id`, `orientation`, `animation_fps`, `description`) VALUES
    -- Transporters (with orientation variants) - animation: 4 FPS = 8 frames / 2 sec (resource travel time per tile)
    (100, 'transporter', 'Conveyor Belt', 'conveyor', 'png', 100, 1, 1, 'conveyor/normal.png', 100, NULL, 'right', 4.00, 'Транспортная лента для перемещения ресурсов'),
    (120, 'transporter', 'Conveyor Belt', 'conveyor_up', 'png', 100, 1, 1, 'conveyor_up/normal.png', 100, 100, 'up', 4.00, 'Транспортная лента (вверх)'),
    (121, 'transporter', 'Conveyor Belt', 'conveyor_down', 'png', 100, 1, 1, 'conveyor_down/normal.png', 100, 100, 'down', 4.00, 'Транспортная лента (вниз)'),
    (122, 'transporter', 'Conveyor Belt', 'conveyor_left', 'png', 100, 1, 1, 'conveyor_left/normal.png', 100, 100, 'left', 4.00, 'Транспортная лента (влево)'),
    -- Buildings - power=100 means baseline crafting speed
    (101, 'building', 'Small Furnace', 'furnace', 'png', 200, 2, 2, 'furnace/normal.png', 100, NULL, 'none', NULL, 'Небольшая печь для переплавки руды'),
    (103, 'building', 'Assembly Machine', 'assembler', 'png', 400, 3, 3, 'assembler/normal.png', 100, NULL, 'none', NULL, 'Сборочная машина для создания деталей'),
    (104, 'storage', 'Storage Chest', 'chest', 'png', 150, 1, 1, 'chest/normal.png', 1, NULL, 'none', NULL, 'Хранилище для ресурсов'),
    (105, 'building', 'Power Pole', 'power_pole', 'png', 100, 1, 1, 'power_pole/normal.png', 1, NULL, 'none', NULL, 'Электрический столб'),
    (106, 'building', 'Steam Engine', 'steam_engine', 'png', 350, 2, 3, 'steam_engine/normal.png', 1, NULL, 'none', NULL, 'Паровой генератор'),
    (107, 'building', 'Boiler', 'boiler', 'png', 250, 2, 2, 'boiler/normal.png', 100, NULL, 'none', NULL, 'Котел для переработки нефти'),
    -- Ore Drills (requires iron/copper deposits) - power=100 means baseline mining speed
    (102, 'mining', 'Small Ore Drill', 'drill', 'png', 300, 1, 1, 'drill/normal.png', 100, NULL, 'none', NULL, 'Небольшая буровая установка для добычи железа и меди'),
    (108, 'mining', 'Medium Ore Drill', 'drill_fast', 'png', 250, 2, 2, 'drill_fast/normal.png', 150, NULL, 'none', NULL, 'Средняя буровая установка для добычи железа и меди'),
    (506, 'mining', 'Large Ore Drill', 'drill_large', 'png', 400, 3, 3, 'drill_large/normal.png', 200, NULL, 'none', NULL, 'Большая буровая установка для добычи железа и меди'),
    -- Manipulators (with orientation variants) - power=100 means full swing in 30 ticks
    (200, 'manipulator', 'Short Manipulator', 'manipulator_short', 'png', 80, 1, 1, 'manipulator_short/normal.png', 100, NULL, 'right', NULL, 'Манипулятор с коротким захватом'),
    (210, 'manipulator', 'Short Manipulator', 'manipulator_short_up', 'png', 80, 1, 1, 'manipulator_short_up/normal.png', 100, 200, 'up', NULL, 'Манипулятор с коротким захватом (вверх)'),
    (211, 'manipulator', 'Short Manipulator', 'manipulator_short_down', 'png', 80, 1, 1, 'manipulator_short_down/normal.png', 100, 200, 'down', NULL, 'Манипулятор с коротким захватом (вниз)'),
    (212, 'manipulator', 'Short Manipulator', 'manipulator_short_left', 'png', 80, 1, 1, 'manipulator_short_left/normal.png', 100, 200, 'left', NULL, 'Манипулятор с коротким захватом (влево)'),
    (201, 'manipulator', 'Long Manipulator', 'manipulator_long', 'png', 80, 1, 1, 'manipulator_long/normal.png', 100, NULL, 'right', NULL, 'Манипулятор с длинным захватом'),
    (213, 'manipulator', 'Long Manipulator', 'manipulator_long_up', 'png', 80, 1, 1, 'manipulator_long_up/normal.png', 100, 201, 'up', NULL, 'Манипулятор с длинным захватом (вверх)'),
    (214, 'manipulator', 'Long Manipulator', 'manipulator_long_down', 'png', 80, 1, 1, 'manipulator_long_down/normal.png', 100, 201, 'down', NULL, 'Манипулятор с длинным захватом (вниз)'),
    (215, 'manipulator', 'Long Manipulator', 'manipulator_long_left', 'png', 80, 1, 1, 'manipulator_long_left/normal.png', 100, 201, 'left', NULL, 'Манипулятор с длинным захватом (влево)'),
    -- Crystal Towers (eye type - visibility radius = power)
    (400, 'eye', 'Small Crystal Tower', 'tower_crystal_small', 'png', 100, 1, 1, 'tower_crystal_small/normal.png', 7, NULL, 'none', NULL, 'Небольшая кристальная башня (радиус обзора: 7)'),
    (401, 'eye', 'Medium Crystal Tower', 'tower_crystal_medium', 'png', 200, 1, 2, 'tower_crystal_medium/normal.png', 15, NULL, 'none', NULL, 'Средняя кристальная башня (радиус обзора: 15)'),
    (402, 'eye', 'Large Crystal Tower', 'tower_crystal_large', 'png', 300, 2, 3, 'tower_crystal_large/normal.png', 30, NULL, 'none', NULL, 'Большая кристальная башня (радиус обзора: 30)'),
    -- Sawmills (requires trees)
    (500, 'mining', 'Small Sawmill', 'sawmill_small', 'png', 200, 1, 1, 'sawmill_small/normal.png', 100, NULL, 'none', NULL, 'Небольшая лесопилка для переработки древесины'),
    (501, 'mining', 'Medium Sawmill', 'sawmill_medium', 'png', 400, 3, 3, 'sawmill_medium/normal.png', 150, NULL, 'none', NULL, 'Средняя лесопилка для переработки древесины'),
    (502, 'mining', 'Large Sawmill', 'sawmill_large', 'png', 600, 5, 5, 'sawmill_large/normal.png', 200, NULL, 'none', NULL, 'Большая лесопилка для переработки древесины'),
    -- Stone Quarries (requires rocks)
    (503, 'mining', 'Small Stone Quarry', 'quarry_stone_small', 'png', 250, 1, 1, 'quarry_stone_small/normal.png', 100, NULL, 'none', NULL, 'Небольшая каменоломня для добычи камня'),
    (504, 'mining', 'Medium Stone Quarry', 'quarry_stone_medium', 'png', 500, 3, 3, 'quarry_stone_medium/normal.png', 150, NULL, 'none', NULL, 'Средняя каменоломня для добычи камня'),
    (505, 'mining', 'Large Stone Quarry', 'quarry_stone_large', 'png', 750, 5, 5, 'quarry_stone_large/normal.png', 200, NULL, 'none', NULL, 'Большая каменоломня для добычи камня'),
    -- Mines (requires silver/gold deposits)
    (507, 'mining', 'Small Mine', 'mine_small', 'png', 300, 1, 1, 'mine_small/normal.png', 100, NULL, 'none', NULL, 'Небольшая шахта для добычи серебра и золота'),
    (508, 'mining', 'Medium Mine', 'mine_medium', 'png', 600, 2, 2, 'mine_medium/normal.png', 150, NULL, 'none', NULL, 'Средняя шахта для добычи серебра и золота'),
    (509, 'mining', 'Large Mine', 'mine_large', 'png', 900, 3, 3, 'mine_large/normal.png', 200, NULL, 'none', NULL, 'Большая шахта для добычи серебра и золота'),
    -- Quarries (requires aluminum/titanium deposits)
    (510, 'mining', 'Small Quarry', 'quarry_small', 'png', 350, 1, 1, 'quarry_small/normal.png', 100, NULL, 'none', NULL, 'Небольшой карьер для добычи алюминия и титана'),
    (511, 'mining', 'Medium Quarry', 'quarry_medium', 'png', 700, 2, 2, 'quarry_medium/normal.png', 150, NULL, 'none', NULL, 'Средний карьер для добычи алюминия и титана'),
    (512, 'mining', 'Large Quarry', 'quarry_large', 'png', 1050, 3, 3, 'quarry_large/normal.png', 200, NULL, 'none', NULL, 'Большой карьер для добычи алюминия и титана');

-- --------------------------------------------------------
-- Table structure: deposit_type (natural resource types)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `deposit_type` (
  `deposit_type_id` int(10) unsigned NOT NULL PRIMARY KEY,
  `type` enum('tree','rock','ore') NOT NULL,
  `name` varchar(128) NOT NULL,
  `image_url` varchar(256) NOT NULL COMMENT 'Folder name for sprites',
  `resource_id` int(10) unsigned NOT NULL COMMENT 'Which resource this deposit contains',
  `resource_amount` int(10) unsigned NOT NULL DEFAULT 100,
  `width` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT 'Visual sprite width in tiles',
  `height` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT 'Visual sprite height in tiles',
  CONSTRAINT `fk_deposit_type_resource` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`resource_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `deposit_type` (`deposit_type_id`, `type`, `name`, `image_url`, `resource_id`, `resource_amount`, `width`, `height`) VALUES
    -- Trees (deposit_type_id matches old entity_type_id for migration)
    (1, 'tree', 'Pine Tree', 'tree_pine', 1, 100, 1, 2),
    (2, 'tree', 'Oak Tree', 'tree_oak', 1, 120, 1, 2),
    (3, 'tree', 'Dead Tree', 'tree_dead', 1, 50, 1, 2),
    (4, 'tree', 'Birch Tree', 'tree_birch', 1, 110, 1, 2),
    (5, 'tree', 'Spruce Tree', 'tree_spruce', 1, 105, 1, 2),
    (6, 'tree', 'Maple Tree', 'tree_maple', 1, 115, 1, 2),
    (7, 'tree', 'Willow Tree', 'tree_willow', 1, 95, 1, 2),
    (8, 'tree', 'Ash Tree', 'tree_ash', 1, 130, 1, 2),
    -- Rocks (deposit_type_id matches old entity_type_id for migration)
    (10, 'rock', 'Small Rock', 'rock_small', 5, 100, 1, 1),
    (11, 'rock', 'Medium Rock', 'rock_medium', 5, 200, 1, 1),
    (12, 'rock', 'Large Rock', 'rock_large', 5, 300, 1, 1),
    -- Ores (deposit_type_id matches old entity_type_id for migration)
    (300, 'ore', 'Iron Ore Deposit', 'ore_iron', 2, 500, 1, 1),
    (301, 'ore', 'Copper Ore Deposit', 'ore_copper', 3, 500, 1, 1),
    (302, 'ore', 'Aluminum Ore Deposit', 'ore_aluminum', 14, 400, 1, 1),
    (303, 'ore', 'Titanium Ore Deposit', 'ore_titanium', 15, 400, 1, 1),
    (304, 'ore', 'Silver Ore Deposit', 'ore_silver', 16, 600, 1, 1),
    (305, 'ore', 'Gold Ore Deposit', 'ore_gold', 17, 700, 1, 1);


-- --------------------------------------------------------
-- Table structure: deposit (deposit instances on map)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `deposit` (
  `deposit_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `deposit_type_id` int(10) unsigned NOT NULL,
  `x` int(10) unsigned NOT NULL COMMENT 'Tile X coordinate (always 1x1 for calculations)',
  `y` int(10) unsigned NOT NULL COMMENT 'Tile Y coordinate (always 1x1 for calculations)',
  `resource_amount` int(10) unsigned NOT NULL COMMENT 'Current amount of resources',
  PRIMARY KEY (`deposit_id`),
  KEY `idx_deposit_position` (`x`, `y`),
  CONSTRAINT `fk_deposit_type` FOREIGN KEY (`deposit_type_id`) REFERENCES `deposit_type` (`deposit_type_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------
-- Table structure: entity (entity instances)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `entity` (
  `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity_type_id` int(10) unsigned DEFAULT NULL,
  `state` enum('built','blueprint') NOT NULL DEFAULT 'built',
  `durability` int(11) unsigned NOT NULL DEFAULT 100,
  `x` int(10) unsigned NOT NULL,
  `y` int(10) unsigned NOT NULL,
  `construction_progress` tinyint(3) unsigned NOT NULL DEFAULT 100 COMMENT 'Прогресс строительства 0-100%',
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------
-- Table structure: map (terrain instances)
-- Map size: 100x75 tiles = 7500 records
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `map` (
  `map_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `landing_id` int(10) unsigned NOT NULL,
  `x` int(10) unsigned NOT NULL,
  `y` int(10) unsigned NOT NULL,
  PRIMARY KEY (`map_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------
-- Table structure: resource (game resources)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `resource` (
  `resource_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `icon_url` varchar(256) NOT NULL,
  `type` enum('raw','liquid','crafted','deposit') NOT NULL DEFAULT 'raw',
  PRIMARY KEY (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `resource` (`resource_id`, `name`, `icon_url`, `type`) VALUES
    -- Raw resources
    (1, 'Wood', 'wood.svg', 'raw'),
    (2, 'Iron Ore', 'iron_ore.svg', 'raw'),
    (3, 'Copper Ore', 'copper_ore.svg', 'raw'),
    (4, 'Coal', 'coal.svg', 'raw'),
    (5, 'Stone', 'stone.svg', 'raw'),
    (6, 'Raw Crystal', 'raw_crystal.svg', 'raw'),
    (7, 'Crude Oil', 'crude_oil.svg', 'raw'),
    -- Deposit resources (for deposits in nature before extraction)
    (10, 'Aluminum Deposit', 'aluminum_deposit.svg', 'deposit'),
    (11, 'Titanium Deposit', 'titanium_deposit.svg', 'deposit'),
    (12, 'Silver Deposit', 'silver_deposit.svg', 'deposit'),
    (13, 'Gold Deposit', 'gold_deposit.svg', 'deposit'),
    -- New ores (extracted from deposits)
    (14, 'Aluminum Ore', 'aluminum_ore.svg', 'raw'),
    (15, 'Titanium Ore', 'titanium_ore.svg', 'raw'),
    (16, 'Silver Ore', 'silver_ore.svg', 'raw'),
    (17, 'Gold Ore', 'gold_ore.svg', 'raw'),
    -- Liquid resources
    (20, 'Refined Fuel', 'refined_fuel.svg', 'liquid'),
    (21, 'Lubricant', 'lubricant.svg', 'liquid'),
    (22, 'Heavy Oil', 'heavy_oil.svg', 'liquid'),
    (23, 'Light Oil', 'light_oil.svg', 'liquid'),
    -- Crafted resources
    (100, 'Iron Ingot', 'iron_ingot.svg', 'crafted'),
    (101, 'Copper Ingot', 'copper_ingot.svg', 'crafted'),
    (102, 'Iron Plate', 'iron_plate.svg', 'crafted'),
    (103, 'Copper Plate', 'copper_plate.svg', 'crafted'),
    (104, 'Copper Wire', 'copper_wire.svg', 'crafted'),
    (105, 'Screw', 'screw.svg', 'crafted'),
    (106, 'Gear', 'gear.svg', 'crafted'),
    (107, 'Rotor', 'rotor.svg', 'crafted'),
    (108, 'Crystal', 'crystal.svg', 'crafted'),
    (109, 'Steel Plate', 'steel_plate.svg', 'crafted'),
    (110, 'Circuit', 'circuit.svg', 'crafted'),
    (111, 'Motor', 'motor.svg', 'crafted'),
    (112, 'Charcoal', 'charcoal.svg', 'crafted'),
    (113, 'Fuel Cell', 'fuel_cell.svg', 'crafted');


-- --------------------------------------------------------
-- Table structure: entity_resource (links entities to resources)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `entity_resource` (
  `entity_resource_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` int(10) unsigned NOT NULL,
  `resource_id` int(10) unsigned NOT NULL,
  `amount` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`entity_resource_id`),
  KEY `idx_entity_resource_entity` (`entity_id`),
  KEY `idx_entity_resource_resource` (`resource_id`),
  UNIQUE KEY `idx_entity_resource_unique` (`entity_id`, `resource_id`),
  CONSTRAINT `fk_entity_resource_entity` FOREIGN KEY (`entity_id`) REFERENCES `entity` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_entity_resource_resource` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`resource_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------
-- Table structure: recipe (crafting recipes)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `recipe` (
  `recipe_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `output_resource_id` int(10) unsigned NOT NULL,
  `output_amount` int(10) unsigned NOT NULL DEFAULT 1,
  `input1_resource_id` int(10) unsigned NOT NULL,
  `input1_amount` int(10) unsigned NOT NULL DEFAULT 1,
  `input2_resource_id` int(10) unsigned DEFAULT NULL,
  `input2_amount` int(10) unsigned DEFAULT NULL,
  `input3_resource_id` int(10) unsigned DEFAULT NULL,
  `input3_amount` int(10) unsigned DEFAULT NULL,
  `ticks` int(10) unsigned NOT NULL DEFAULT 60,
  PRIMARY KEY (`recipe_id`),
  CONSTRAINT `fk_recipe_output` FOREIGN KEY (`output_resource_id`) REFERENCES `resource` (`resource_id`),
  CONSTRAINT `fk_recipe_input1` FOREIGN KEY (`input1_resource_id`) REFERENCES `resource` (`resource_id`),
  CONSTRAINT `fk_recipe_input2` FOREIGN KEY (`input2_resource_id`) REFERENCES `resource` (`resource_id`),
  CONSTRAINT `fk_recipe_input3` FOREIGN KEY (`input3_resource_id`) REFERENCES `resource` (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Note: All ticks are multiples of 30 for optimized logic tick (30 ticks = 1 logic update at 60fps)
INSERT INTO `recipe` (`recipe_id`, `output_resource_id`, `output_amount`, `input1_resource_id`, `input1_amount`, `input2_resource_id`, `input2_amount`, `input3_resource_id`, `input3_amount`, `ticks`) VALUES
    -- Extraction recipes (30 ticks = 0.5s) - deposits are stored in entity, extracted during crafting
    (21, 1, 1, 1, 1, NULL, NULL, NULL, NULL, 30),      -- Wood Deposit -> Wood (Sawmill)
    (22, 5, 1, 5, 1, NULL, NULL, NULL, NULL, 30),      -- Stone Deposit -> Stone (Stone Quarry)
    (23, 16, 1, 12, 1, NULL, NULL, NULL, NULL, 30),    -- Silver Deposit -> Silver Ore (Mine)
    (24, 17, 1, 13, 1, NULL, NULL, NULL, NULL, 30),    -- Gold Deposit -> Gold Ore (Mine)
    (25, 14, 1, 10, 1, NULL, NULL, NULL, NULL, 30),    -- Aluminum Deposit -> Aluminum Ore (Quarry)
    (26, 15, 1, 11, 1, NULL, NULL, NULL, NULL, 30),    -- Titanium Deposit -> Titanium Ore (Quarry)
    -- Furnace recipes
    (3, 100, 1, 2, 3, 4, 1, NULL, NULL, 60),           -- 3 Iron Ore + 1 Coal -> 1 Iron Ingot (1s)
    (4, 101, 1, 3, 3, 4, 1, NULL, NULL, 60),           -- 3 Copper Ore + 1 Coal -> 1 Copper Ingot (1s)
    (5, 109, 1, 100, 2, 4, 1, NULL, NULL, 90),         -- 2 Iron Ingot + 1 Coal -> 1 Steel Plate (1.5s)
    (6, 112, 1, 1, 1, NULL, NULL, NULL, NULL, 30),     -- 1 Wood -> 1 Charcoal (0.5s)
    -- Assembly recipes
    (7, 102, 2, 100, 1, NULL, NULL, NULL, NULL, 30),   -- 1 Iron Ingot -> 2 Iron Plate (0.5s)
    (8, 103, 2, 101, 1, NULL, NULL, NULL, NULL, 30),   -- 1 Copper Ingot -> 2 Copper Plate (0.5s)
    (9, 104, 4, 101, 2, NULL, NULL, NULL, NULL, 30),   -- 2 Copper Ingot -> 4 Copper Wire (0.5s)
    (10, 105, 4, 102, 2, NULL, NULL, NULL, NULL, 30),  -- 2 Iron Plate -> 4 Screw (0.5s)
    (11, 106, 1, 102, 2, NULL, NULL, NULL, NULL, 30),  -- 2 Iron Plate -> 1 Gear (0.5s)
    (12, 107, 1, 106, 2, 105, 4, NULL, NULL, 60),      -- 2 Gear + 4 Screw -> 1 Rotor (1s)
    (13, 110, 1, 104, 2, 102, 1, NULL, NULL, 60),      -- 2 Copper Wire + 1 Iron Plate -> 1 Circuit (1s)
    (14, 111, 1, 107, 1, 110, 2, 104, 1, 90),          -- 1 Rotor + 2 Circuit + 1 Copper Wire -> 1 Motor (1.5s)
    (15, 108, 1, 6, 1, NULL, NULL, NULL, NULL, 60),    -- 1 Raw Crystal -> 1 Crystal (1s)
    (16, 113, 1, 20, 2, 110, 1, NULL, NULL, 120),      -- 2 Refined Fuel + 1 Circuit -> 1 Fuel Cell (2s)
    -- Boiler recipes
    (17, 22, 1, 7, 1, NULL, NULL, NULL, NULL, 60),     -- 1 Crude Oil -> 1 Heavy Oil (1s)
    (18, 23, 1, 22, 2, NULL, NULL, NULL, NULL, 30),    -- 2 Heavy Oil -> 1 Light Oil (0.5s)
    (19, 20, 1, 23, 2, NULL, NULL, NULL, NULL, 30),    -- 2 Light Oil -> 1 Refined Fuel (0.5s)
    (20, 21, 1, 22, 3, NULL, NULL, NULL, NULL, 60);    -- 3 Heavy Oil -> 1 Lubricant (1s)


-- --------------------------------------------------------
-- Table structure: entity_type_recipe (links entity types to recipes)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `entity_type_recipe` (
  `entity_type_id` int(10) unsigned NOT NULL,
  `recipe_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`entity_type_id`, `recipe_id`),
  CONSTRAINT `fk_etr_entity_type` FOREIGN KEY (`entity_type_id`) REFERENCES `entity_type` (`entity_type_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_etr_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipe` (`recipe_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `entity_type_recipe` (`entity_type_id`, `recipe_id`) VALUES
    -- Small Furnace (101)
    (101, 3), (101, 4), (101, 5), (101, 6),
    -- Assembly Machine (103)
    (103, 7), (103, 8), (103, 9), (103, 10), (103, 11), (103, 12), (103, 13), (103, 14), (103, 15), (103, 16),
    -- Boiler (107)
    (107, 17), (107, 18), (107, 19), (107, 20),
    -- Sawmills (500-502) - Wood extraction
    (500, 21), (501, 21), (502, 21),
    -- Stone Quarries (503-505) - Stone extraction
    (503, 22), (504, 22), (505, 22),
    -- Mines (507-509) - Silver/Gold extraction
    (507, 23), (507, 24), (508, 23), (508, 24), (509, 23), (509, 24),
    -- Quarries (510-512) - Aluminum/Titanium extraction
    (510, 25), (510, 26), (511, 25), (511, 26), (512, 25), (512, 26);


/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
