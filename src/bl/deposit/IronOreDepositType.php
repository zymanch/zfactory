<?php

namespace bl\deposit;

use bl\deposit\generators\IronOreGenerator;
use models\DepositType;

/**
 * Iron Ore Deposit Type
 */
class IronOreDepositType extends DepositType
{
    /**
     * Get generator for this deposit type
     * @return IronOreGenerator
     */
    public function getGenerator(): IronOreGenerator
    {
        return new IronOreGenerator();
    }
}
