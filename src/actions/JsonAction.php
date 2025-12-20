<?php

namespace actions;

use Yii;
use yii\web\Response;

/**
 * Base action class for JSON API endpoints
 */
abstract class JsonAction extends Base
{
    /**
     * Initialize JSON response format
     */
    public function init()
    {
        parent::init();
        Yii::$app->response->format = Response::FORMAT_JSON;
    }

    /**
     * Return success response
     */
    protected function success($data = [])
    {
        return array_merge(['result' => 'ok'], $data);
    }

    /**
     * Return error response
     */
    protected function error($message, $details = null)
    {
        $response = ['result' => 'error', 'error' => $message];
        if ($details !== null) {
            $response['details'] = $details;
        }
        return $response;
    }

    /**
     * Get POST body params (works with JSON)
     */
    protected function getBodyParams()
    {
        return Yii::$app->request->getBodyParams();
    }

    /**
     * Get GET params
     */
    protected function getQueryParams()
    {
        return Yii::$app->request->getQueryParams();
    }
}
