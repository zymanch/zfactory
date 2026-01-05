<?php

namespace commands\actions\regions;

use commands\actions\Base;

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
