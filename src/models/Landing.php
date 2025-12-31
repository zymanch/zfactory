<?php

namespace models;

use bl\landing\LandingFactory;
use models\base;

class Landing extends base\BaseLanding
{
    /**
     * @inheritdoc
     * Creates an instance of the appropriate Landing subclass based on landing_id
     */
    public static function instantiate($row)
    {
        $landingId = $row['landing_id'] ?? null;

        if ($landingId !== null) {
            $class = LandingFactory::getClass((int)$landingId);
            if ($class !== null) {
                return new $class();
            }
        }

        return new static();
    }
}