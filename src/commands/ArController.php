<?php

namespace commands;

use Fixturify\FixtureService;
use Fixturify\Storage\PDO;
use Fixturify\Storage\Yii2ActiveRecord;

class ArController extends \yii\console\Controller
{

    public $tables;
    public $dir;

    public function options($actionID)
    {
        return ['tables', 'dir'];
    }

    private function getTables()
    {
        if ($this->tables) {
            return $this->tables;
        }

        $tables = [
            'zfactory'             => [
                'entity',
                'entity_crafting',
                'entity_resource',
                'entity_transport',
                'entity_type',
                'entity_type_recipe',
                'landing',
                'map',
                'recipe',
                'resource',
                'user',
            ],

        ];
        $string = [];
        foreach ($tables as $database => $tables) {
            $string[] = $database . ':' . implode(',', $tables);
        }
        return implode(';',$string);
    }

    public function actionIndex()
    {
        $tables = $this->getTables();

        $helper = new \ActiveGenerator\generator\ScriptHelper();
        $helper->baseClass = 'yii\db\ActiveRecord';
        $helper->queryBaseClass = 'yii\db\ActiveQuery';
        $helper->namespace = 'models';
        $helper->prefix = 'Base';
        $helper->sub = 'base';
        $helper->path = \Yii::getAlias('@app/models');
        $helper->generate(
            \Yii::$app->db->masterPdo,
            $tables
        );
    }
}
