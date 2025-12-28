<?php

namespace actions\game;

use actions\JsonAction;
use models\Deposit;

/**
 * AJAX: Load deposits in viewport
 * Returns all deposits (trees, rocks, ores) for rendering on the deposit layer
 */
class Deposits extends JsonAction
{
    public function run()
    {
        // Get optional viewport parameters
        $x = \Yii::$app->request->get('x');
        $y = \Yii::$app->request->get('y');
        $width = \Yii::$app->request->get('width');
        $height = \Yii::$app->request->get('height');

        $query = Deposit::find()
            ->select(['deposit_id', 'deposit_type_id', 'x', 'y', 'resource_amount']);

        // If viewport parameters provided, filter by area
        if ($x !== null && $y !== null && $width !== null && $height !== null) {
            $query->where(['>=', 'x', (int)$x])
                ->andWhere(['<', 'x', (int)$x + (int)$width])
                ->andWhere(['>=', 'y', (int)$y])
                ->andWhere(['<', 'y', (int)$y + (int)$height]);
        }

        $deposits = $this->castNumericFieldsArray(
            $query->asArray()->all(),
            ['deposit_id', 'deposit_type_id', 'x', 'y', 'resource_amount']
        );

        return $this->success(['deposits' => $deposits]);
    }
}
