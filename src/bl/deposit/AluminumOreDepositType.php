<?php

namespace bl\deposit;

use bl\deposit\generators\AluminumOreGenerator;
use models\DepositType;

/**
 * Aluminum Ore Deposit Type
 */
class AluminumOreDepositType extends DepositType
{
    /**
     * Get generator for this deposit type
     * @return AluminumOreGenerator
     */
    public function getGenerator(): AluminumOreGenerator
    {
        return new AluminumOreGenerator();
    }
}
