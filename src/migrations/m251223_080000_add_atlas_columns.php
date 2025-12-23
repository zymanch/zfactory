<?php

use yii\db\Migration;

class m251223_080000_add_atlas_columns extends Migration
{
    public function safeUp()
    {
        // Колонка variations_count уже существует в landing, пропускаем

        // Добавляем колонку для Z-индекса в атласе
        $this->addColumn('{{%landing_adjacency}}', 'atlas_z',
            $this->integer()->notNull()->defaultValue(0)->after('landing_id_2'));

        echo "Column atlas_z added successfully.\n";
    }

    public function safeDown()
    {
        $this->dropColumn('{{%landing_adjacency}}', 'atlas_z');
        $this->dropColumn('{{%landing}}', 'variations_count');
    }
}
