<?php

namespace commands\actions\game;

use commands\actions\Base;

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
