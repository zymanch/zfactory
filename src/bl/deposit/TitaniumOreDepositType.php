<?php

namespace bl\deposit;

use bl\deposit\generators\TitaniumOreGenerator;
use models\DepositType;

/**
 * Titanium Ore Deposit Type
 */
class TitaniumOreDepositType extends DepositType
{
    /**
     * Get generator for this deposit type
     * @return TitaniumOreGenerator
     */
    public function getGenerator(): TitaniumOreGenerator
    {
        return new TitaniumOreGenerator();
    }
}
