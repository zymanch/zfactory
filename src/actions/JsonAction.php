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

    /**
     * Cast numeric fields in array (fixes Yii asArray() returning strings)
     * @param array $row Single row
     * @param array $intFields Fields to cast to int
     * @param array $floatFields Fields to cast to float
     */
    protected function castNumericFields(array $row, array $intFields = [], array $floatFields = []): array
    {
        foreach ($intFields as $field) {
            if (isset($row[$field])) {
                $row[$field] = (int)$row[$field];
            }
        }
        foreach ($floatFields as $field) {
            if (isset($row[$field])) {
                $row[$field] = (float)$row[$field];
            }
        }
        return $row;
    }

    /**
     * Cast numeric fields in array of rows
     */
    protected function castNumericFieldsArray(array $rows, array $intFields = [], array $floatFields = []): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[] = $this->castNumericFields($row, $intFields, $floatFields);
        }
        return $result;
    }

    /**
     * Cast numeric fields in indexed array (preserves keys)
     */
    protected function castNumericFieldsIndexed(array $rows, array $intFields = [], array $floatFields = []): array
    {
        $result = [];
        foreach ($rows as $key => $row) {
            $result[$key] = $this->castNumericFields($row, $intFields, $floatFields);
        }
        return $result;
    }
}
