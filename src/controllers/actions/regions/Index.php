<?php

namespace controllers\actions\regions;

use controllers\actions\Base;

/**
 * Regions map view
 */
class Index extends Base
{
    public function run()
    {
        return $this->render('index');
    }
}
