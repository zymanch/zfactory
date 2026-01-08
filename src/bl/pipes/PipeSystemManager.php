<?php

namespace bl\pipes;

use models\PipeSystem;
use models\PipeSystemMember;
use models\Entity;
use models\EntityResource;
use Yii;

/**
 * Manages fluid pipe systems using pooled approach with BFS algorithm
 */
class PipeSystemManager
{
    /**
     * Recalculates all pipe systems in a region
     * @param int $regionId
     * @return void
     */
    public static function recalculateSystems(int $regionId): void
    {
        // 1. Delete all old systems for this region
        PipeSystem::deleteAll(['region_id' => $regionId]);

        // 2. Get all pipe entities in the region (transporters and storage tanks)
        $pipes = Entity::find()
            ->joinWith('entityType')
            ->where([
                'or',
                ['entity_type.entity_type_id' => [131, 132, 140, 141]], // pipes and underground pipes
                ['entity_type.entity_type_id' => [135, 136]], // tanks
            ])
            ->andWhere(['entity.region_id' => $regionId])
            ->andWhere(['entity.state' => 'built']) // only built entities
            ->all();

        if (empty($pipes)) {
            return;
        }

        // 3. For each unprocessed pipe - create new system via BFS
        $processed = [];
        foreach ($pipes as $pipe) {
            if (isset($processed[$pipe->entity_id])) {
                continue;
            }

            $systemMembers = self::findConnectedPipes($pipe, $pipes, $processed);
            self::createSystem($regionId, $systemMembers);
        }
    }

    /**
     * BFS search for all connected pipes
     * @param Entity $startPipe
     * @param Entity[] $allPipes
     * @param array &$processed
     * @return Entity[]
     */
    private static function findConnectedPipes(Entity $startPipe, array $allPipes, array &$processed): array
    {
        $queue = [$startPipe];
        $system = [];
        $processed[$startPipe->entity_id] = true;

        while (!empty($queue)) {
            $pipe = array_shift($queue);
            $system[] = $pipe;

            // Find all pipes connected to current one (4 directions)
            $neighbors = self::getNeighborPipes($pipe, $allPipes);

            foreach ($neighbors as $neighbor) {
                if (!isset($processed[$neighbor->entity_id])) {
                    $processed[$neighbor->entity_id] = true;
                    $queue[] = $neighbor;
                }
            }
        }

        return $system;
    }

    /**
     * Finds neighbor pipes (checks physical connection in 4 directions)
     * @param Entity $pipe
     * @param Entity[] $allPipes
     * @return Entity[]
     */
    private static function getNeighborPipes(Entity $pipe, array $allPipes): array
    {
        $neighbors = [];

        // Check 4 directions (up, down, left, right)
        $directions = [
            ['dx' => 0, 'dy' => -64], // up (64px = 1 tile)
            ['dx' => 0, 'dy' => 64],  // down
            ['dx' => -64, 'dy' => 0], // left
            ['dx' => 64, 'dy' => 0],  // right
        ];

        foreach ($directions as $dir) {
            $x = $pipe->x + $dir['dx'];
            $y = $pipe->y + $dir['dy'];

            foreach ($allPipes as $otherPipe) {
                if ($otherPipe->x == $x && $otherPipe->y == $y) {
                    $neighbors[] = $otherPipe;
                    break;
                }
            }
        }

        return $neighbors;
    }

    /**
     * Creates a new pipe system
     * @param int $regionId
     * @param Entity[] $members
     * @return void
     */
    private static function createSystem(int $regionId, array $members): void
    {
        // Calculate total capacity
        $maxCapacity = 0;
        foreach ($members as $pipe) {
            $maxCapacity += $pipe->entityType->power;
        }

        // Determine current resource and amount
        $resourceId = null;
        $currentAmount = 0;

        foreach ($members as $pipe) {
            $resource = EntityResource::find()
                ->where(['entity_id' => $pipe->entity_id])
                ->one();

            if ($resource) {
                $resourceId = $resource->resource_id;
                $currentAmount += $resource->amount;
            }
        }

        // Create system
        $system = new PipeSystem();
        $system->region_id = $regionId;
        $system->resource_id = $resourceId;
        $system->current_amount = $currentAmount;
        $system->max_capacity = $maxCapacity;
        $system->save();

        // Add system members
        foreach ($members as $pipe) {
            $member = new PipeSystemMember();
            $member->pipe_system_id = $system->pipe_system_id;
            $member->entity_id = $pipe->entity_id;
            $member->save();
        }
    }

    /**
     * Add fluid to pipe system
     * @param int $pipeEntityId
     * @param int $resourceId
     * @param int $amount
     * @return bool success
     */
    public static function addFluid(int $pipeEntityId, int $resourceId, int $amount): bool
    {
        $member = PipeSystemMember::find()
            ->where(['entity_id' => $pipeEntityId])
            ->one();

        if (!$member) {
            return false;
        }

        $system = PipeSystem::findOne($member->pipe_system_id);

        // Check for mixing
        if ($system->resource_id && $system->resource_id != $resourceId) {
            return false; // Cannot mix fluids
        }

        // Check for overflow
        if ($system->current_amount + $amount > $system->max_capacity) {
            return false; // Overflow
        }

        // Add resource
        $system->resource_id = $resourceId;
        $system->current_amount += $amount;
        $system->save();

        return true;
    }

    /**
     * Take fluid from pipe system
     * @param int $pipeEntityId
     * @param int $resourceId
     * @param int $amount
     * @return int amount taken (may be less than requested)
     */
    public static function takeFluid(int $pipeEntityId, int $resourceId, int $amount): int
    {
        $member = PipeSystemMember::find()
            ->where(['entity_id' => $pipeEntityId])
            ->one();

        if (!$member) {
            return 0;
        }

        $system = PipeSystem::findOne($member->pipe_system_id);

        // Check resource type
        if ($system->resource_id != $resourceId) {
            return 0; // Wrong resource
        }

        // Take what we can
        $taken = min($amount, $system->current_amount);
        $system->current_amount -= $taken;

        // If system is empty - clear resource_id
        if ($system->current_amount == 0) {
            $system->resource_id = null;
        }

        $system->save();

        return $taken;
    }

    /**
     * Get system information for tooltip
     * @param int $pipeEntityId
     * @return array|null
     */
    public static function getSystemInfo(int $pipeEntityId): ?array
    {
        $member = PipeSystemMember::find()
            ->where(['entity_id' => $pipeEntityId])
            ->one();

        if (!$member) {
            return null;
        }

        $system = PipeSystem::findOne($member->pipe_system_id);

        return [
            'resource_id' => $system->resource_id,
            'resource_name' => $system->resource ? $system->resource->name : 'Empty',
            'current_amount' => $system->current_amount,
            'max_capacity' => $system->max_capacity,
            'fill_percent' => ($system->max_capacity > 0)
                ? round(($system->current_amount / $system->max_capacity) * 100, 1)
                : 0,
        ];
    }
}
