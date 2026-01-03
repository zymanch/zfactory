<?php

namespace actions\admin;

use actions\Base;

class Index extends Base
{
    public function run()
    {
        return $this->render('index');
    }
}
