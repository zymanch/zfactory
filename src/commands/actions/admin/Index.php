<?php

namespace commands\actions\admin;

use commands\actions\Base;

class Index extends Base
{
    public function run()
    {
        return $this->render('index');
    }
}
