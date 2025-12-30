<?php

namespace actions;

use Yii;
use yii\base\Action;

/**
 * Base action class for all standalone actions
 */
abstract class Base extends Action
{

    /**
     * Render a view
     */
    protected function render($view, $params = [])
    {
        return $this->controller->render($view, $params);
    }

    /**
     * Redirect to URL
     */
    protected function redirect($url, $statusCode = 302)
    {
        return $this->controller->redirect($url, $statusCode);
    }

    /**
     * Get request object
     */
    protected function getRequest()
    {
        return Yii::$app->request;
    }

    /**
     * Get response object
     */
    protected function getResponse()
    {
        return Yii::$app->response;
    }

    /**
     * Get current user
     */
    protected function getUser()
    {
        return Yii::$app->user->identity;
    }

    /**
     * Check if user is guest
     */
    protected function isGuest()
    {
        return Yii::$app->user->isGuest;
    }
}
