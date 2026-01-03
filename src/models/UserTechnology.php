<?php

namespace models;

use models\base;
use Yii;

class UserTechnology extends base\BaseUserTechnology
{
    /**
     * Получить ID всех изученных технологий пользователя
     *
     * @param int $userId
     * @return array
     */
    public static function getResearchedIds(int $userId): array
    {
        return self::find()
            ->select('technology_id')
            ->where(['user_id' => $userId])
            ->column();
    }

    /**
     * Проверить, изучена ли технология пользователем
     *
     * @param int $userId
     * @param int $technologyId
     * @return bool
     */
    public static function isResearched(int $userId, int $technologyId): bool
    {
        return self::find()
            ->where(['user_id' => $userId, 'technology_id' => $technologyId])
            ->exists();
    }

    /**
     * Изучить технологию
     *
     * @param int $userId
     * @param int $technologyId
     * @return bool
     */
    public static function research(int $userId, int $technologyId): bool
    {
        // Уже изучена?
        if (self::isResearched($userId, $technologyId)) {
            return true;
        }

        $userTech = new self();
        $userTech->user_id = $userId;
        $userTech->technology_id = $technologyId;
        return $userTech->save();
    }

    /**
     * Получить все ID разблокированных entity_type для пользователя
     *
     * @param int $userId
     * @return array
     */
    public static function getUnlockedEntityTypeIds(int $userId): array
    {
        $researchedIds = self::getResearchedIds($userId);
        if (empty($researchedIds)) {
            return [];
        }

        return TechnologyUnlockEntityType::find()
            ->select('entity_type_id')
            ->where(['technology_id' => $researchedIds])
            ->column();
    }

    /**
     * Получить все ID разблокированных рецептов для пользователя
     *
     * @param int $userId
     * @return array
     */
    public static function getUnlockedRecipeIds(int $userId): array
    {
        $researchedIds = self::getResearchedIds($userId);
        if (empty($researchedIds)) {
            return [];
        }

        return TechnologyUnlockRecipe::find()
            ->select('recipe_id')
            ->where(['technology_id' => $researchedIds])
            ->column();
    }
}