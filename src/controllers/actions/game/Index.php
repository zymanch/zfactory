<?php

namespace controllers\actions\game;

use controllers\actions\Base;

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
