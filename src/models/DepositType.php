<?php
namespace models;

use bl\deposit\generators\AbstractDepositGenerator;
use models\base;
use bl\deposit;

/**
 * @method AbstractDepositGenerator getGenerator()
 */
class DepositType extends base\BaseDepositType {

    /**
     * Instantiate specific deposit type class based on image_url
     * @return DepositType
     */
    public static function instantiate($row)
    {
        // Map image_url to specific deposit type classes
        $classMap = [
            'ore_iron' => deposit\IronOreDepositType::class,
            'ore_copper' => deposit\CopperOreDepositType::class,
            'ore_aluminum' => deposit\AluminumOreDepositType::class,
            'ore_titanium' => deposit\TitaniumOreDepositType::class,
            'ore_silver' => deposit\SilverOreDepositType::class,
            'ore_gold' => deposit\GoldOreDepositType::class,
        ];

        $className = $classMap[$row['image_url']] ?? null;

        if ($className === null) {
            // Return generic DepositType if no specific class found
            $className = new self;
        }

        // Create instance of specific class and copy attributes
        $instance = new $className();
        $instance->setAttributes($row, false);
        $instance->setIsNewRecord(false);

        return $instance;
    }
}