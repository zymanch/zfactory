<?php

namespace bl\deposit;

use bl\deposit\generators\CopperOreGenerator;
use models\DepositType;

/**
 * Copper Ore Deposit Type
 */
class CopperOreDepositType extends DepositType
{
    /**
     * Get generator for this deposit type
     * @return CopperOreGenerator
     */
    public function getGenerator(): CopperOreGenerator
    {
        return new CopperOreGenerator();
    }
}
