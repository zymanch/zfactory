<?php

namespace actions\admin;

use actions\JsonAction;
use models\User;

class Users extends JsonAction
{
    public function run()
    {
        $query = User::find();

        // Apply filters
        if ($username = \Yii::$app->request->get('username')) {
            $query->andFilterWhere(['like', 'username', $username]);
        }
        if ($email = \Yii::$app->request->get('email')) {
            $query->andFilterWhere(['like', 'email', $email]);
        }
        if (($isAdmin = \Yii::$app->request->get('is_admin')) !== null) {
            $query->andWhere(['is_admin' => $isAdmin === '1']);
        }

        $users = $query
            ->select(['user_id', 'username', 'email', 'is_admin', 'created_at', 'current_region_id'])
            ->asArray()
            ->all();

        return $this->success(['users' => $users]);
    }
}
