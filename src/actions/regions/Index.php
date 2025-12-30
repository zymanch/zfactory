<?php

namespace actions\regions;

use actions\Base;

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
