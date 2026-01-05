<?php

namespace bl\deposit;

use bl\deposit\generators\GoldOreGenerator;
use models\DepositType;

/**
 * Gold Ore Deposit Type
 */
class GoldOreDepositType extends DepositType
{
    /**
     * Get generator for this deposit type
     * @return GoldOreGenerator
     */
    public function getGenerator(): GoldOreGenerator
    {
        return new GoldOreGenerator();
    }
}
