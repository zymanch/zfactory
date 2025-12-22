/**
 * BuildingState - State of a building, mining drill, or storage
 */
export class BuildingState {
    constructor(entity, entityType, game) {
        this.entityId = entity.entity_id;
        this.x = parseInt(entity.x);
        this.y = parseInt(entity.y);
        this.type = entityType.type; // 'building' | 'mining' | 'storage'
        this.power = parseInt(entityType.power) || 100;

        // Resources inside: Map<resourceId, amount>
        this.resources = new Map();

        // Recipes (from game data)
        this.recipeIds = [];
        this.inputResourceIds = new Set();
        this.outputResourceIds = new Set();

        // Initialize recipes from game data
        this.initRecipes(entityType.entity_type_id, game);

        // Crafting state
        this.craftingRecipeId = null;
        this.craftingTicksRemaining = 0;

        // For storage: max slots = power
        this.maxSlots = this.type === 'storage' ? this.power : null;
    }

    /**
     * Initialize available recipes and input/output resource sets
     */
    initRecipes(entityTypeId, game) {
        const recipeIds = game.entityTypeRecipes?.[entityTypeId] || [];
        this.recipeIds = recipeIds;

        for (const recipeId of recipeIds) {
            const recipe = game.recipes?.[recipeId];
            if (!recipe) continue;

            // Output resources
            if (recipe.output_resource_id) {
                this.outputResourceIds.add(parseInt(recipe.output_resource_id));
            }

            // Input resources
            if (recipe.input1_resource_id) {
                this.inputResourceIds.add(parseInt(recipe.input1_resource_id));
            }
            if (recipe.input2_resource_id) {
                this.inputResourceIds.add(parseInt(recipe.input2_resource_id));
            }
            if (recipe.input3_resource_id) {
                this.inputResourceIds.add(parseInt(recipe.input3_resource_id));
            }
        }
    }

    /**
     * Check if currently crafting
     */
    isCrafting() {
        return this.craftingRecipeId !== null;
    }

    /**
     * Get amount of specific resource
     */
    getResourceAmount(resourceId) {
        return this.resources.get(resourceId) || 0;
    }

    /**
     * Add resource to building
     */
    addResource(resourceId, amount) {
        const current = this.getResourceAmount(resourceId);
        this.resources.set(resourceId, current + amount);
    }

    /**
     * Remove resource from building
     * @returns {number} Amount actually removed
     */
    removeResource(resourceId, amount) {
        const current = this.getResourceAmount(resourceId);
        const removed = Math.min(current, amount);

        if (removed >= current) {
            this.resources.delete(resourceId);
        } else {
            this.resources.set(resourceId, current - removed);
        }

        return removed;
    }

    /**
     * Check if can accept resource
     * @returns {'yes' | 'no'}
     */
    canAcceptResource(resourceId, game) {
        // Keys in game.resources are strings
        const resource = game.resources?.[String(resourceId)];
        if (!resource) return 'no';

        switch (this.type) {
            case 'building':
                // Only accept input resources, max 10 of each
                if (!this.inputResourceIds.has(resourceId)) return 'no';
                if (this.getResourceAmount(resourceId) >= 10) return 'no';
                return 'yes';

            case 'mining':
                // Mining drills don't accept resources from outside
                return 'no';

            case 'storage':
                // Accept any resource if there's space
                const maxStack = parseInt(resource.max_stack) || 100;
                const current = this.getResourceAmount(resourceId);

                // Check if existing stack has room
                if (current > 0 && current < maxStack) return 'yes';

                // Check if there's a free slot
                if (this.maxSlots && this.resources.size >= this.maxSlots) return 'no';

                return 'yes';

            default:
                return 'no';
        }
    }

    /**
     * Check if can give resource to requester
     * @returns {{resourceId: number, amount: number} | null}
     */
    canGiveResource(requesterType, game) {
        if (requesterType !== 'manipulator') return null;

        switch (this.type) {
            case 'building':
                // Only give output resources
                for (const resourceId of this.outputResourceIds) {
                    const amount = this.getResourceAmount(resourceId);
                    if (amount > 0) {
                        return { resourceId, amount: 1 };
                    }
                }
                return null;

            case 'mining':
                // Give non-deposit resources (output only, not deposits)
                for (const [resourceId, amount] of this.resources) {
                    if (amount <= 0) continue;
                    // Keys in game.resources are strings
                    const resource = game.resources?.[String(resourceId)];
                    if (resource && resource.type !== 'deposit') {
                        return { resourceId, amount: 1 };
                    }
                }
                return null;

            case 'storage':
                // Give any resource
                for (const [resourceId, amount] of this.resources) {
                    if (amount > 0) {
                        return { resourceId, amount: 1 };
                    }
                }
                return null;

            default:
                return null;
        }
    }

    /**
     * Load resources from entity_resource data
     */
    loadResources(entityResources) {
        this.resources.clear();
        for (const er of entityResources) {
            if (er.entity_id === this.entityId) {
                this.resources.set(parseInt(er.resource_id), parseInt(er.amount));
            }
        }
    }

    /**
     * Load crafting state from saved data
     */
    loadCraftingState(data) {
        if (data) {
            this.craftingRecipeId = data.recipe_id;
            this.craftingTicksRemaining = data.ticks_remaining;
        }
    }

    /**
     * Get data for saving resources
     */
    getResourceSaveData() {
        const data = [];
        for (const [resourceId, amount] of this.resources) {
            if (amount > 0) {
                data.push({
                    entity_id: this.entityId,
                    resource_id: resourceId,
                    amount: amount
                });
            }
        }
        return data;
    }

    /**
     * Get data for saving crafting state
     */
    getCraftingSaveData() {
        if (!this.craftingRecipeId) return null;

        return {
            entity_id: this.entityId,
            recipe_id: this.craftingRecipeId,
            ticks_remaining: this.craftingTicksRemaining
        };
    }

    /**
     * Get crafting progress (0.0 - 1.0)
     */
    getCraftingProgress(game) {
        if (!this.craftingRecipeId) return null;

        const recipe = game.recipes?.[this.craftingRecipeId];
        if (!recipe) return null;

        const totalTicks = this.calculateCraftTime(recipe.ticks);
        const elapsed = totalTicks - this.craftingTicksRemaining;

        return {
            progress: elapsed / totalTicks,
            recipeId: this.craftingRecipeId,
            ticksRemaining: this.craftingTicksRemaining
        };
    }

    /**
     * Calculate craft time adjusted by power
     */
    calculateCraftTime(baseTicks) {
        return Math.ceil(baseTicks * (100 / this.power));
    }
}

export default BuildingState;
