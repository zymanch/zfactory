<?php

namespace bl\deposit;

use bl\deposit\generators\SilverOreGenerator;
use models\DepositType;

/**
 * Silver Ore Deposit Type
 */
class SilverOreDepositType extends DepositType
{
    /**
     * Get generator for this deposit type
     * @return SilverOreGenerator
     */
    public function getGenerator(): SilverOreGenerator
    {
        return new SilverOreGenerator();
    }
}
