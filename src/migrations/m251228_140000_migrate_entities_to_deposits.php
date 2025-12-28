<?php

use yii\db\Migration;

/**
 * Переносит деревья, камни и руды из entity в deposit
 * - Деревья (entity_type_id 1-8) → deposit_type_id 1-8
 * - Камни (entity_type_id 10-12) → deposit_type_id 10-12
 * - Руды (entity_type_id 300-301) → deposit_type_id 300-301
 * - Конвертирует pixel coordinates → tile coordinates (÷64)
 * - Сохраняет resource_amount из entity_resource
 * - Удаляет старые entity и entity_type записи
 */
class m251228_140000_migrate_entities_to_deposits extends Migration
{
    const TILE_SIZE = 64; // pixels per tile

    public function safeUp()
    {
        // 1. Перенести деревья (entity_type_id 1-8)
        $this->migrateEntitiesToDeposits([1, 2, 3, 4, 5, 6, 7, 8]);

        // 2. Перенести камни (entity_type_id 10-12)
        $this->migrateEntitiesToDeposits([10, 11, 12]);

        // 3. Перенести руды (entity_type_id 300-301)
        $this->migrateEntitiesToDeposits([300, 301]);

        // 4. Удалить entity_resource записи для перенесенных entities
        $this->execute("
            DELETE er FROM entity_resource er
            INNER JOIN entity e ON er.entity_id = e.entity_id
            WHERE e.entity_type_id IN (1,2,3,4,5,6,7,8,10,11,12,300,301)
        ");

        // 5. Удалить entity записи
        $this->delete('entity', ['entity_type_id' => [1,2,3,4,5,6,7,8,10,11,12,300,301]]);

        // 6. Удалить entity_type записи (они больше не нужны)
        $this->delete('entity_type', ['entity_type_id' => [1,2,3,4,5,6,7,8,10,11,12,300,301]]);
    }

    public function safeDown()
    {
        echo "Откат миграции невозможен - данные удалены из entity.\n";
        return false;
    }

    /**
     * Переносит entities указанных типов в deposit
     * @param array $entityTypeIds
     */
    private function migrateEntitiesToDeposits($entityTypeIds)
    {
        $sql = "
            INSERT INTO deposit (deposit_type_id, x, y, resource_amount)
            SELECT
                e.entity_type_id AS deposit_type_id,
                FLOOR(e.x / :tileSize) AS x,
                FLOOR(e.y / :tileSize) AS y,
                COALESCE(
                    (SELECT er.amount FROM entity_resource er
                     WHERE er.entity_id = e.entity_id
                     LIMIT 1),
                    dt.resource_amount
                ) AS resource_amount
            FROM entity e
            LEFT JOIN deposit_type dt ON dt.deposit_type_id = e.entity_type_id
            WHERE e.entity_type_id IN (" . implode(',', $entityTypeIds) . ")
        ";

        $this->execute($sql, [':tileSize' => self::TILE_SIZE]);
    }
}
