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
  `animation_fps` decimal(5,2) DEFAULT NULL COMMENT 'Animation speed in frames per second. NULL = no animation'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `entity_type` (`entity_type_id`, `type`, `name`, `image_url`, `extension`, `max_durability`, `width`, `height`, `icon_url`, `power`, `parent_entity_type_id`, `orientation`, `animation_fps`) VALUES
    -- Trees
    (1, 'tree', 'Pine Tree', 'tree_pine', 'png', 50, 1, 1, NULL, 1, NULL, 'none', NULL),
    (2, 'tree', 'Oak Tree', 'tree_oak', 'png', 60, 1, 1, NULL, 1, NULL, 'none', NULL),
    (3, 'tree', 'Dead Tree', 'tree_dead', 'png', 20, 1, 1, NULL, 1, NULL, 'none', NULL),
    -- Relief
    (10, 'relief', 'Small Rock', 'rock_small', 'png', 100, 1, 1, NULL, 1, NULL, 'none', NULL),
    (11, 'relief', 'Medium Rock', 'rock_medium', 'png', 200, 1, 1, NULL, 1, NULL, 'none', NULL),
    (12, 'relief', 'Large Rock', 'rock_large', 'png', 300, 1, 1, NULL, 1, NULL, 'none', NULL),
    -- Transporters (with orientation variants) - animation: 4 FPS = 8 frames / 2 sec (resource travel time per tile)
    (100, 'transporter', 'Conveyor Belt', 'conveyor', 'png', 100, 1, 1, 'conveyor/normal.png', 100, NULL, 'right', 4.00),
    (120, 'transporter', 'Conveyor Belt', 'conveyor_up', 'png', 100, 1, 1, 'conveyor_up/normal.png', 100, 100, 'up', 4.00),
    (121, 'transporter', 'Conveyor Belt', 'conveyor_down', 'png', 100, 1, 1, 'conveyor_down/normal.png', 100, 100, 'down', 4.00),
    (122, 'transporter', 'Conveyor Belt', 'conveyor_left', 'png', 100, 1, 1, 'conveyor_left/normal.png', 100, 100, 'left', 4.00),
    -- Buildings - power=100 means baseline crafting speed
    (101, 'building', 'Small Furnace', 'furnace', 'png', 200, 2, 2, 'furnace/normal.png', 100, NULL, 'none', NULL),
    (103, 'building', 'Assembly Machine', 'assembler', 'png', 400, 3, 3, 'assembler/normal.png', 100, NULL, 'none', NULL),
    (104, 'storage', 'Storage Chest', 'chest', 'png', 150, 1, 1, 'chest/normal.png', 1, NULL, 'none', NULL),
    (105, 'building', 'Power Pole', 'power_pole', 'png', 100, 1, 1, 'power_pole/normal.png', 1, NULL, 'none', NULL),
    (106, 'building', 'Steam Engine', 'steam_engine', 'png', 350, 2, 3, 'steam_engine/normal.png', 1, NULL, 'none', NULL),
    (107, 'building', 'Boiler', 'boiler', 'png', 250, 2, 2, 'boiler/normal.png', 100, NULL, 'none', NULL),
    -- Mining (requires resource entity to place on) - power=100 means baseline mining speed
    (102, 'mining', 'Mining Drill', 'drill', 'png', 300, 1, 1, 'drill/normal.png', 100, NULL, 'none', NULL),
    (108, 'mining', 'Fast Mining Drill', 'drill_fast', 'png', 250, 1, 1, 'drill_fast/normal.png', 150, NULL, 'none', NULL),
    -- Manipulators (with orientation variants) - power=100 means full swing in 30 ticks
    (200, 'manipulator', 'Short Manipulator', 'manipulator_short', 'png', 80, 1, 1, 'manipulator_short/normal.png', 100, NULL, 'right', NULL),
    (210, 'manipulator', 'Short Manipulator', 'manipulator_short_up', 'png', 80, 1, 1, 'manipulator_short_up/normal.png', 100, 200, 'up', NULL),
    (211, 'manipulator', 'Short Manipulator', 'manipulator_short_down', 'png', 80, 1, 1, 'manipulator_short_down/normal.png', 100, 200, 'down', NULL),
    (212, 'manipulator', 'Short Manipulator', 'manipulator_short_left', 'png', 80, 1, 1, 'manipulator_short_left/normal.png', 100, 200, 'left', NULL),
    (201, 'manipulator', 'Long Manipulator', 'manipulator_long', 'png', 80, 1, 1, 'manipulator_long/normal.png', 100, NULL, 'right', NULL),
    (213, 'manipulator', 'Long Manipulator', 'manipulator_long_up', 'png', 80, 1, 1, 'manipulator_long_up/normal.png', 100, 201, 'up', NULL),
    (214, 'manipulator', 'Long Manipulator', 'manipulator_long_down', 'png', 80, 1, 1, 'manipulator_long_down/normal.png', 100, 201, 'down', NULL),
    (215, 'manipulator', 'Long Manipulator', 'manipulator_long_left', 'png', 80, 1, 1, 'manipulator_long_left/normal.png', 100, 201, 'left', NULL),
    -- Resources
    (300, 'resource', 'Iron Ore', 'ore_iron', 'png', 9999, 1, 1, 'ore_iron/normal.png', 1, NULL, 'none', NULL),
    (301, 'resource', 'Copper Ore', 'ore_copper', 'png', 9999, 1, 1, 'ore_copper/normal.png', 1, NULL, 'none', NULL),
    -- Crystal Towers (eye type - visibility radius = power)
    (400, 'eye', 'Small Crystal Tower', 'tower_crystal_small', 'png', 100, 1, 1, 'tower_crystal_small/normal.png', 7, NULL, 'none', NULL),
    (401, 'eye', 'Medium Crystal Tower', 'tower_crystal_medium', 'png', 200, 1, 2, 'tower_crystal_medium/normal.png', 15, NULL, 'none', NULL),
    (402, 'eye', 'Large Crystal Tower', 'tower_crystal_large', 'png', 300, 2, 3, 'tower_crystal_large/normal.png', 30, NULL, 'none', NULL);

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
    -- Deposit resources (abstract, inside resource entities)
    (8, 'Iron Deposit', 'iron_deposit.svg', 'deposit'),
    (9, 'Copper Deposit', 'copper_deposit.svg', 'deposit'),
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
    -- Mining recipes (30 ticks = 0.5s)
    (1, 2, 1, 8, 1, NULL, NULL, NULL, NULL, 30),   -- 1 Iron Deposit -> 1 Iron Ore
    (2, 3, 1, 9, 1, NULL, NULL, NULL, NULL, 30),   -- 1 Copper Deposit -> 1 Copper Ore
    -- Furnace recipes
    (3, 100, 1, 2, 3, 4, 1, NULL, NULL, 60),       -- 3 Iron Ore + 1 Coal -> 1 Iron Ingot (1s)
    (4, 101, 1, 3, 3, 4, 1, NULL, NULL, 60),       -- 3 Copper Ore + 1 Coal -> 1 Copper Ingot (1s)
    (5, 109, 1, 100, 2, 4, 1, NULL, NULL, 90),     -- 2 Iron Ingot + 1 Coal -> 1 Steel Plate (1.5s)
    (6, 112, 1, 1, 1, NULL, NULL, NULL, NULL, 30), -- 1 Wood -> 1 Charcoal (0.5s)
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
    -- Mining Drill (102)
    (102, 1), (102, 2),
    -- Fast Mining Drill (108)
    (108, 1), (108, 2),
    -- Small Furnace (101)
    (101, 3), (101, 4), (101, 5), (101, 6),
    -- Assembly Machine (103)
    (103, 7), (103, 8), (103, 9), (103, 10), (103, 11), (103, 12), (103, 13), (103, 14), (103, 15), (103, 16),
    -- Boiler (107)
    (107, 17), (107, 18), (107, 19), (107, 20);


/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
