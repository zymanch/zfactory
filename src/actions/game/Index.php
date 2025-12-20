<?php

namespace actions\game;

use actions\Base;

/**
 * Main game page
 */
class Index extends Base
{
    public function run()
    {
        return $this->render('index');
    }
}
