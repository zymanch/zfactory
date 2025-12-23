<?php

use yii\db\Migration;

class m251223_075500_convert_landing_to_png extends Migration
{
    public function safeUp()
    {
        $this->execute("UPDATE landing SET image_url = REPLACE(image_url, '.jpg', '.png')");
    }

    public function safeDown()
    {
        $this->execute("UPDATE landing SET image_url = REPLACE(image_url, '.png', '.jpg')");
    }
}
