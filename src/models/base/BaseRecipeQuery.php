<?php

namespace models\base;

class BaseRecipeQuery extends \yii\db\ActiveQuery
{
    public function __construct($modelClass, $config = [])
    {
        parent::__construct($modelClass, $config);
    }
}
