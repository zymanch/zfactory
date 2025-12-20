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
  `image_url` varchar(256) NOT NULL,
  PRIMARY KEY (`landing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `landing` (`landing_id`, `is_buildable`, `name`, `image_url`) VALUES
    (1, 'yes', 'Grass', 'grass.jpg'),
    (2, 'yes', 'Dirt', 'dirt.jpg'),
    (3, 'yes', 'Sand', 'sand.jpg'),
    (4, 'no', 'Water', 'water.jpg'),
    (5, 'no', 'Stone', 'stone.jpg'),
    (6, 'no', 'Lava', 'lava.jpg'),
    (7, 'yes', 'Snow', 'snow.jpg'),
    (8, 'no', 'Swamp', 'swamp.jpg'),
    (9, 'no', 'Sky', 'sky.jpg'),
    (10, 'no', 'Island Edge', 'island_edge.jpg');

-- --------------------------------------------------------
-- Table structure: entity_type (entity definitions)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `entity_type` (
  `entity_type_id` int(10) unsigned NOT NULL PRIMARY KEY,
  `type` enum('building','transporter','manipulator','tree','relief','resource','eye','mining') NOT NULL,
  `name` varchar(128) NOT NULL,
  `image_url` varchar(256) NOT NULL,
  `extension` varchar(4) NOT NULL DEFAULT 'svg',
  `max_durability` int(11) unsigned NOT NULL DEFAULT 100,
  `width` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `height` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `icon_url` varchar(256) DEFAULT NULL,
  `power` int(10) unsigned NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `entity_type` (`entity_type_id`, `type`, `name`, `image_url`, `extension`, `max_durability`, `width`, `height`, `icon_url`, `power`) VALUES
    -- Trees
    (1, 'tree', 'Pine Tree', 'tree_pine', 'svg', 50, 1, 1, NULL, 1),
    (2, 'tree', 'Oak Tree', 'tree_oak', 'svg', 60, 1, 1, NULL, 1),
    (3, 'tree', 'Dead Tree', 'tree_dead', 'svg', 20, 1, 1, NULL, 1),
    -- Relief
    (10, 'relief', 'Small Rock', 'rock_small', 'svg', 100, 1, 1, NULL, 1),
    (11, 'relief', 'Medium Rock', 'rock_medium', 'svg', 200, 1, 1, NULL, 1),
    (12, 'relief', 'Large Rock', 'rock_large', 'svg', 300, 1, 1, NULL, 1),
    -- Buildings
    (100, 'building', 'Conveyor Belt', 'conveyor', 'svg', 100, 1, 1, 'conveyor/icon.svg', 1),
    (101, 'building', 'Small Furnace', 'furnace', 'svg', 200, 2, 2, 'furnace/icon.svg', 1),
    (103, 'building', 'Assembly Machine', 'assembler', 'svg', 400, 3, 3, 'assembler/icon.svg', 1),
    (104, 'building', 'Storage Chest', 'chest', 'svg', 150, 1, 1, 'chest/icon.svg', 1),
    (105, 'building', 'Power Pole', 'power_pole', 'svg', 100, 1, 1, 'power_pole/icon.svg', 1),
    (106, 'building', 'Steam Engine', 'steam_engine', 'svg', 350, 2, 3, 'steam_engine/icon.svg', 1),
    (107, 'building', 'Boiler', 'boiler', 'svg', 250, 2, 2, 'boiler/icon.svg', 1),
    -- Mining (requires resource entity to place on)
    (102, 'mining', 'Mining Drill', 'drill', 'svg', 300, 1, 1, 'drill/icon.svg', 1),
    (108, 'mining', 'Fast Mining Drill', 'drill_fast', 'svg', 250, 1, 1, 'drill_fast/icon.svg', 1),
    -- Manipulators
    (200, 'manipulator', 'Short Manipulator', 'manipulator_short', 'svg', 80, 1, 1, 'manipulator_short/icon.svg', 1),
    (201, 'manipulator', 'Long Manipulator', 'manipulator_long', 'svg', 80, 1, 1, 'manipulator_long/icon.svg', 1),
    -- Resources
    (300, 'resource', 'Iron Ore', 'ore_iron', 'svg', 9999, 1, 1, 'ore_iron/icon.svg', 1),
    (301, 'resource', 'Copper Ore', 'ore_copper', 'svg', 9999, 1, 1, 'ore_copper/icon.svg', 1),
    -- Crystal Towers (eye type - visibility radius = power)
    (400, 'eye', 'Small Crystal Tower', 'tower_crystal_small', 'svg', 100, 1, 1, 'tower_crystal_small/icon.svg', 7),
    (401, 'eye', 'Medium Crystal Tower', 'tower_crystal_medium', 'svg', 200, 1, 2, 'tower_crystal_medium/icon.svg', 15),
    (402, 'eye', 'Large Crystal Tower', 'tower_crystal_large', 'svg', 300, 2, 3, 'tower_crystal_large/icon.svg', 30);

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
  `type` enum('raw','liquid','crafted') NOT NULL DEFAULT 'raw',
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


/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
