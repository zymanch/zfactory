<?php

use yii\db\Migration;

/**
 * Исправляет координаты deposits - возвращает их обратно в пиксели умножением на 64
 * так как они были неправильно преобразованы в миграции m251228_140000
 */
class m251228_150000_fix_deposit_coordinates extends Migration
{
    public function safeUp()
    {
        // Координаты в entity уже были в тайлах, но миграция разделила их на 64
        // Возвращаем обратно умножением текущих значений на 64
        $this->execute("UPDATE deposit SET x = x * 64, y = y * 64");

        echo "Исправлено координаты deposits (умножены на 64)\n";
    }

    public function safeDown()
    {
        // Откат - делим обратно на 64
        $this->execute("UPDATE deposit SET x = FLOOR(x / 64), y = FLOOR(y / 64)");

        return true;
    }
}
