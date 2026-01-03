<?php

namespace actions\admin;

use actions\JsonAction;
use models\Region;

class Regions extends JsonAction
{
    public function run()
    {
        $query = Region::find();

        // Apply filters
        if ($name = \Yii::$app->request->get('name')) {
            $query->andFilterWhere(['like', 'name', $name]);
        }
        if ($difficulty = \Yii::$app->request->get('difficulty')) {
            $query->andWhere(['difficulty' => $difficulty]);
        }

        $regions = $query->asArray()->all();

        return $this->success(['regions' => $regions]);
    }
}
