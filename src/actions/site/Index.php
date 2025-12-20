<?php

namespace actions\site;

use actions\Base;

/**
 * Landing page with login button
 */
class Index extends Base
{
    public function run()
    {
        // If already logged in, redirect to game
        if (!$this->isGuest()) {
            return $this->redirect(['game/index']);
        }

        return $this->render('index');
    }
}
