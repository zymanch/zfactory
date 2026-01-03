<?php

namespace actions\admin;

use actions\JsonAction;
use models\Deposit;

class CreateDeposit extends JsonAction
{
    public function run()
    {
        $regionId = \Yii::$app->request->post('region_id');
        $depositTypeId = \Yii::$app->request->post('deposit_type_id');
        $x = \Yii::$app->request->post('x');
        $y = \Yii::$app->request->post('y');
        $resourceAmount = \Yii::$app->request->post('resource_amount');

        if (!$regionId || !$depositTypeId || $x === null || $y === null || !$resourceAmount) {
            return $this->error('Missing required parameters');
        }

        // No validation - create directly as per requirements
        $deposit = new Deposit();
        $deposit->region_id = $regionId;
        $deposit->deposit_type_id = $depositTypeId;
        $deposit->x = $x;
        $deposit->y = $y;
        $deposit->resource_amount = $resourceAmount;
        $deposit->save(false);

        return $this->success([
            'deposit' => [
                'deposit_id' => $deposit->deposit_id,
                'deposit_type_id' => $deposit->deposit_type_id,
                'x' => $deposit->x,
                'y' => $deposit->y,
                'resource_amount' => $deposit->resource_amount,
            ],
        ]);
    }
}
