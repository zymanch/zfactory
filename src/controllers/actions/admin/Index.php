<?php

namespace controllers\actions\admin;

use controllers\actions\Base;

class Index extends Base
{
    public function run()
    {
        return $this->render('index');
    }
}
