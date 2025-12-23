<?php

namespace actions;

use yii\base\Action;

/**
 * Base class for console actions
 */
abstract class ConsoleAction extends Action
{
    /**
     * @var \yii\console\Controller
     */
    public $controller;

    /**
     * Outputs a string to stdout
     */
    protected function stdout($string, $color = null)
    {
        return $this->controller->stdout($string, $color);
    }

    /**
     * Run the action
     */
    abstract public function run();
}
