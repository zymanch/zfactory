<?php

namespace actions\admin;

use actions\Base;
use models\Region;

class EditMap extends Base
{
    public function run($region_id)
    {
        $region = Region::findOne($region_id);
        if (!$region) {
            throw new \yii\web\NotFoundHttpException('Region not found');
        }

        return $this->render('edit-map', [
            'region' => $region,
        ]);
    }
}
