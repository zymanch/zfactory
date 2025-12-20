<?php

use yii\db\Migration;

/**
 * Class m251214_050249_init
 */
class m251214_050249_init extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `landing` (
             `landing_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `is_walk` enum('yes','no') NOT NULL DEFAULT 'yes',
            `image_url` varchar(256) NOT NULL,
            PRIMARY KEY (`landing_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $this->execute("CREATE TABLE IF NOT EXISTS `map` (
             `map_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `landing_id` int(10) unsigned NOT NULL,
            `x` int(10) unsigned NOT NULL,
            `y` int(10) unsigned NOT NULL,
            PRIMARY KEY (`map_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $this->execute("CREATE TABLE IF NOT EXISTS `entity` (
            `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `entity_type_id` int(10) unsigned NOT NULL,
            `x` int(10) unsigned NOT NULL,
            `y` int(10) unsigned NOT NULL,
            PRIMARY KEY (`entity_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $this->execute("CREATE TABLE IF NOT EXISTS `entity_type` (
             `entity_type_id` int(10) unsigned NOT NULL,
            `type` enum('building','tree','relief') NOT NULL,
            `name` varchar(128) NOT NULL,
            `image_url` varchar(256) NOT NULL,
            PRIMARY KEY (`entity_type_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m251214_050249_init cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251214_050249_init cannot be reverted.\n";

        return false;
    }
    */
}
