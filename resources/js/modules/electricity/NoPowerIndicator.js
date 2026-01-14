/**
 * Manages "no power" indicators on buildings without electricity
 * Shows warning sprite on entities that need electricity but don't have it
 */
export class NoPowerIndicator {
    constructor(game) {
        this.game = game;
        this.indicators = new Map(); // entity_id => sprite
        this.texture = null;

        // Sprite pool for performance
        this.spritePool = [];
    }

    /**
     * Initialize manager
     */
    async init() {
        // Get no_power texture (already loaded)
        this.texture = this.game.graphics.getTexture('no_power');

        console.log('[NoPowerIndicator] Initialized');
    }

    /**
     * Show "no power" indicator on entity
     * @param {Object} entity - Entity data
     */
    show(entity) {
        // Don't show if already showing
        if (this.indicators.has(entity.entity_id)) return;

        // Get sprite container for entity
        const spriteData = this.game.loadedEntities.get(entity.entity_id);
        if (!spriteData || !spriteData.sprite) return;

        // Reuse sprite from pool or create new one
        let sprite = this.spritePool.pop();
        if (!sprite) {
            sprite = this.game.graphics.createSprite(this.texture, {
                x: 0,
                y: -32
            });
            sprite.anchor.set(0.5, 0.5);
        }

        // Position at center of entity sprite
        sprite.x = 0;
        sprite.y = -32; // Slightly above center
        sprite.visible = true;

        // Add to entity container
        spriteData.sprite.addChild(sprite);
        this.indicators.set(entity.entity_id, sprite);
    }

    /**
     * Hide "no power" indicator for entity
     * @param {number} entityId
     */
    hide(entityId) {
        const sprite = this.indicators.get(entityId);
        if (!sprite) return;

        // Remove from parent and return to pool
        if (sprite.parent) {
            sprite.parent.removeChild(sprite);
        }
        sprite.visible = false;
        this.spritePool.push(sprite);

        this.indicators.delete(entityId);
    }

    /**
     * Check if entity needs electricity (has recipes with electricity input)
     * @param {Object} entity
     * @returns {boolean}
     */
    checkEntityNeedsElectricity(entity) {
        const entityType = this.game.entityTypes[entity.entity_type_id];
        if (!entityType) return false;

        // Get recipes for this entity type
        const recipeIds = entityType.recipes || [];

        for (const recipeId of recipeIds) {
            const recipe = this.game.recipes?.[recipeId];
            if (!recipe) continue;

            // Check if any input is electricity (resource_id 400)
            if (recipe.input1_resource_id === 400 ||
                recipe.input2_resource_id === 400 ||
                recipe.input3_resource_id === 400) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update all indicators (check which entities need power)
     * Called periodically to update indicator visibility
     */
    update() {
        // Check all loaded entities
        for (const [entityId, entityData] of this.game.entityData) {
            const entity = this.game.loadedEntities.get(entityId);
            if (!entity || entity.state !== 'built') {
                // Hide indicator if entity is not built
                this.hide(entityId);
                continue;
            }

            // Check if entity needs electricity
            const needsElectricity = this.checkEntityNeedsElectricity(entity);
            if (!needsElectricity) {
                this.hide(entityId);
                continue;
            }

            // Check if entity has electricity
            const hasElectricity = this.game.electricityManager.hasElectricity(entityId, 1);

            if (hasElectricity) {
                this.hide(entityId);
            } else {
                this.show(entity);
            }
        }
    }

    /**
     * Destroy manager
     */
    destroy() {
        // Remove all indicators
        for (const [entityId, sprite] of this.indicators) {
            if (sprite.parent) {
                sprite.parent.removeChild(sprite);
            }
            sprite.destroy();
        }
        this.indicators.clear();

        // Clear pool
        for (const sprite of this.spritePool) {
            sprite.destroy();
        }
        this.spritePool = [];
    }
}
