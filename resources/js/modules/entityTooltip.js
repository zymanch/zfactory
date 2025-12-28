import { EntityBehaviorFactory } from './entityBehaviors.js';

/**
 * EntityTooltip - displays entity info, durability and resources on hover
 * Uses EntityBehavior to determine if tooltip should be shown
 */
export class EntityTooltip {
    constructor(game) {
        this.game = game;
        this.tooltipEl = null;
        this.currentEntityKey = null;
        this.hideTimeout = null;
    }

    /**
     * Initialize tooltip
     */
    init() {
        this.createTooltipElement();
    }

    /**
     * Create tooltip DOM element
     */
    createTooltipElement() {
        this.tooltipEl = document.createElement('div');
        this.tooltipEl.id = 'entity-tooltip';
        this.tooltipEl.className = 'entity-tooltip';
        this.tooltipEl.style.cssText = `
            position: fixed;
            display: none;
            background: rgba(20, 20, 30, 0.95);
            border: 1px solid #4a4a5a;
            border-radius: 4px;
            padding: 8px 12px;
            color: #fff;
            font-size: 12px;
            z-index: 10000;
            pointer-events: none;
            min-width: 150px;
            max-width: 250px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.5);
        `;
        document.body.appendChild(this.tooltipEl);
    }

    /**
     * Check if entity should show hover info based on its behavior
     */
    shouldShowTooltip(entityTypeId) {
        const behavior = EntityBehaviorFactory.create(this.game, entityTypeId);
        return behavior ? behavior.shouldShowHoverInfo() : true;
    }

    /**
     * Show tooltip for entity
     */
    async show(entityKey, screenX, screenY) {
        if (this.hideTimeout) {
            clearTimeout(this.hideTimeout);
            this.hideTimeout = null;
        }

        this.currentEntityKey = entityKey;
        const entity = this.game.entityData.get(entityKey);
        if (!entity) return;

        const entityType = this.game.entityTypes[entity.entity_type_id];
        if (!entityType) return;

        // Check if this entity type should show tooltip
        if (!this.shouldShowTooltip(entity.entity_type_id)) {
            return;
        }

        // Build tooltip content
        let html = `<div class="tooltip-header" style="font-weight:bold;margin-bottom:6px;border-bottom:1px solid #4a4a5a;padding-bottom:4px;">${entityType.name}</div>`;

        // Coordinates
        const tileX = parseInt(entity.x);
        const tileY = parseInt(entity.y);
        html += `<div style="font-size:10px;color:#888;margin-bottom:6px;">Position: ${tileX}, ${tileY}</div>`;

        // Durability bar
        const durability = parseInt(entity.durability) || 0;
        const maxDurability = parseInt(entityType.max_durability) || 100;
        const durabilityPercent = Math.min(100, (durability / maxDurability) * 100);
        const durabilityColor = durabilityPercent > 50 ? '#4a9' : (durabilityPercent > 25 ? '#fa0' : '#f44');

        html += `
            <div style="margin-bottom:6px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
                    <span>Durability</span>
                    <span>${durability}/${maxDurability}</span>
                </div>
                <div style="background:#333;height:6px;border-radius:3px;overflow:hidden;">
                    <div style="width:${durabilityPercent}%;height:100%;background:${durabilityColor};"></div>
                </div>
            </div>
        `;

        // Resources (if any)
        const resources = await this.getEntityResources(entity.entity_id);
        if (resources && resources.length > 0) {
            html += `<div style="border-top:1px solid #4a4a5a;padding-top:6px;margin-top:6px;">`;
            html += `<div style="margin-bottom:4px;font-weight:bold;">Resources:</div>`;
            for (const res of resources) {
                html += `
                    <div style="display:flex;align-items:center;margin:2px 0;">
                        <img src="/assets/tiles/resources/${res.icon_url}" width="16" height="16" style="margin-right:6px;">
                        <span>${res.name}</span>
                        <span style="margin-left:auto;color:#8af;">${this.formatAmount(res.amount)}</span>
                    </div>
                `;
            }
            html += `</div>`;
        }

        // Construction progress (if building)
        const constructionHtml = this.getConstructionProgressHtml(entity, entityType);
        if (constructionHtml) {
            html += constructionHtml;
        }

        // Active crafting (if any)
        const craftingHtml = this.getCraftingProgressHtml(entity.entity_id);
        if (craftingHtml) {
            html += craftingHtml;
        }

        // Recipes (if any)
        const recipesHtml = this.getRecipesHtml(entity.entity_type_id);
        if (recipesHtml) {
            html += recipesHtml;
        }

        this.tooltipEl.innerHTML = html;
        this.tooltipEl.style.display = 'block';
        this.updatePosition(screenX, screenY);
    }

    /**
     * Update tooltip position
     */
    updatePosition(screenX, screenY) {
        if (!this.tooltipEl || this.tooltipEl.style.display === 'none') return;

        const rect = this.tooltipEl.getBoundingClientRect();
        const padding = 15;

        let x = screenX + padding;
        let y = screenY + padding;

        // Keep within viewport
        if (x + rect.width > window.innerWidth) {
            x = screenX - rect.width - padding;
        }
        if (y + rect.height > window.innerHeight) {
            y = screenY - rect.height - padding;
        }

        this.tooltipEl.style.left = x + 'px';
        this.tooltipEl.style.top = y + 'px';
    }

    /**
     * Hide tooltip
     */
    hide() {
        this.hideTimeout = setTimeout(() => {
            if (this.tooltipEl) {
                this.tooltipEl.style.display = 'none';
            }
            this.currentEntityKey = null;
        }, 100);
    }

    /**
     * Get entity resources from ResourceTransportManager (no server call needed)
     */
    getEntityResources(entityId) {
        const rt = this.game.resourceTransport;
        if (!rt) return [];

        // Check building
        const buildingState = rt.buildings.get(entityId);
        if (buildingState) {
            const resources = [];
            for (const [resourceId, amount] of buildingState.resources) {
                if (amount > 0) {
                    const resourceInfo = this.game.resources[String(resourceId)];
                    if (resourceInfo) {
                        resources.push({
                            resource_id: resourceId,
                            name: resourceInfo.name,
                            icon_url: resourceInfo.icon_url,
                            amount: amount
                        });
                    }
                }
            }
            return resources;
        }

        // Check transporter (conveyor)
        const transporterState = rt.transporters.get(entityId);
        if (transporterState && transporterState.resourceId) {
            const resourceInfo = this.game.resources[String(transporterState.resourceId)];
            if (resourceInfo) {
                return [{
                    resource_id: transporterState.resourceId,
                    name: resourceInfo.name,
                    icon_url: resourceInfo.icon_url,
                    amount: transporterState.resourceAmount || 1
                }];
            }
        }

        // Check manipulator
        const manipulatorState = rt.manipulators.get(entityId);
        if (manipulatorState && manipulatorState.resourceId) {
            const resourceInfo = this.game.resources[String(manipulatorState.resourceId)];
            if (resourceInfo) {
                return [{
                    resource_id: manipulatorState.resourceId,
                    name: resourceInfo.name,
                    icon_url: resourceInfo.icon_url,
                    amount: manipulatorState.resourceAmount || 1
                }];
            }
        }

        return [];
    }

    /**
     * Get construction progress HTML for entity
     */
    getConstructionProgressHtml(entity, entityType) {
        const constructionProgress = parseInt(entity.construction_progress) || 100;
        if (constructionProgress >= 100) {
            return null; // Fully built
        }

        const constructionTicks = parseInt(entityType.construction_ticks) || 60;
        const ticksPerSecond = 60;
        const totalSeconds = constructionTicks / ticksPerSecond;
        const remainingSeconds = Math.ceil((constructionTicks * (100 - constructionProgress) / 100) / ticksPerSecond);

        let html = `<div style="border-top:1px solid #4a4a5a;padding-top:6px;margin-top:6px;">`;
        html += `<div style="margin-bottom:4px;font-weight:bold;color:#4682b4;">Construction:</div>`;
        html += `<div style="display:flex;align-items:center;margin-bottom:4px;">`;
        html += `<span>Building...</span>`;
        html += `<span style="margin-left:auto;color:#aaa;">${remainingSeconds}s</span>`;
        html += `</div>`;
        html += `<div style="background:#333;height:8px;border-radius:4px;overflow:hidden;">`;
        html += `<div style="width:${constructionProgress}%;height:100%;background:linear-gradient(90deg,#4682b4,#6ca0dc);transition:width 0.1s;"></div>`;
        html += `</div>`;
        html += `</div>`;

        return html;
    }

    /**
     * Get crafting progress HTML for entity
     */
    getCraftingProgressHtml(entityId) {
        const buildingState = this.game.resourceTransport?.buildings.get(entityId);
        if (!buildingState || !buildingState.isCrafting()) {
            return null;
        }

        const recipeId = buildingState.craftingRecipeId;
        const recipe = this.game.recipes?.[String(recipeId)];
        if (!recipe) return null;

        // Calculate progress
        const totalTicks = buildingState.calculateCraftTime(parseInt(recipe.ticks));
        const elapsed = totalTicks - buildingState.craftingTicksRemaining;
        const progress = Math.min(100, Math.round((elapsed / totalTicks) * 100));

        // Get output resource info
        const outputResource = this.game.resources?.[String(recipe.output_resource_id)];
        const outputName = outputResource?.name || 'Unknown';
        const outputIcon = outputResource?.icon_url || '';

        // Time remaining
        const ticksPerSecond = 60;
        const secondsRemaining = Math.ceil(buildingState.craftingTicksRemaining / ticksPerSecond);

        let html = `<div style="border-top:1px solid #4a4a5a;padding-top:6px;margin-top:6px;">`;
        html += `<div style="margin-bottom:4px;font-weight:bold;color:#4a9;">Crafting:</div>`;
        html += `<div style="display:flex;align-items:center;margin-bottom:4px;">`;
        if (outputIcon) {
            html += `<img src="/assets/tiles/resources/${outputIcon}" width="16" height="16" style="margin-right:6px;">`;
        }
        html += `<span>${outputName}</span>`;
        html += `<span style="margin-left:auto;color:#aaa;">${secondsRemaining}s</span>`;
        html += `</div>`;
        html += `<div style="background:#333;height:8px;border-radius:4px;overflow:hidden;">`;
        html += `<div style="width:${progress}%;height:100%;background:linear-gradient(90deg,#4a9,#6c6);transition:width 0.1s;"></div>`;
        html += `</div>`;
        html += `</div>`;

        return html;
    }

    /**
     * Get recipes HTML for entity type
     */
    getRecipesHtml(entityTypeId) {
        const recipeIds = this.game.entityTypeRecipes?.[entityTypeId];
        if (!recipeIds || recipeIds.length === 0) {
            return null;
        }

        // Get entity type power for speed calculation
        const entityType = this.game.entityTypes?.[entityTypeId];
        const power = parseInt(entityType?.power) || 100;

        let html = `<div style="border-top:1px solid #4a4a5a;padding-top:6px;margin-top:6px;">`;
        html += `<div style="margin-bottom:4px;font-weight:bold;">Recipes:</div>`;

        for (const recipeId of recipeIds) {
            const recipe = this.game.recipes?.[recipeId];
            if (!recipe) continue;

            html += this.renderRecipe(recipe, power);
        }

        html += `</div>`;
        return html;
    }

    /**
     * Render single recipe as HTML
     * @param {object} recipe - Recipe data
     * @param {number} power - Entity type power (affects speed)
     */
    renderRecipe(recipe, power) {
        const v = this.game.config.assetVersion || 1;

        // Build inputs
        const inputs = [];
        if (recipe.input1_resource_id) {
            inputs.push({ id: recipe.input1_resource_id, amount: recipe.input1_amount });
        }
        if (recipe.input2_resource_id) {
            inputs.push({ id: recipe.input2_resource_id, amount: recipe.input2_amount });
        }
        if (recipe.input3_resource_id) {
            inputs.push({ id: recipe.input3_resource_id, amount: recipe.input3_amount });
        }

        // Build output
        const output = { id: recipe.output_resource_id, amount: recipe.output_amount };

        // Render
        let html = `<div style="display:flex;align-items:center;margin:4px 0;flex-wrap:wrap;">`;

        // Inputs
        for (let i = 0; i < inputs.length; i++) {
            const input = inputs[i];
            const res = this.game.resources?.[input.id];
            if (!res) continue;

            if (i > 0) {
                html += `<span style="margin:0 2px;color:#888;">+</span>`;
            }
            html += this.renderResourceIcon(res, input.amount, v);
        }

        // Arrow
        html += `<span style="margin:0 6px;color:#4a9;">→</span>`;

        // Output
        const outRes = this.game.resources?.[output.id];
        if (outRes) {
            html += this.renderResourceIcon(outRes, output.amount, v);
        }

        // Time: (ticks / 60) * (100 / power)
        const timeSeconds = this.formatRecipeTime(recipe.ticks, power);
        html += `<span style="margin-left:auto;color:#888;font-size:10px;">${timeSeconds}</span>`;

        html += `</div>`;
        return html;
    }

    /**
     * Format recipe time based on ticks and entity power
     * 60 ticks = 1 second at power=100
     * Higher power = faster (power=200 is 2x faster)
     * @param {number} ticks - Recipe ticks
     * @param {number} power - Entity power (default 100)
     * @returns {string} Formatted time
     */
    formatRecipeTime(ticks, power = 100) {
        const time = (ticks / 60) * (100 / power);
        // Remove decimal if whole number
        return time % 1 === 0 ? time.toString() : time.toFixed(1);
    }

    /**
     * Render resource icon with amount
     */
    renderResourceIcon(resource, amount, version) {
        const iconUrl = `/assets/tiles/resources/${resource.icon_url}?v=${version}`;
        return `
            <div style="display:flex;align-items:center;margin:0 2px;" title="${resource.name}">
                <img src="${iconUrl}" width="16" height="16" style="margin-right:2px;">
                <span style="color:#aaa;font-size:11px;">${amount}</span>
            </div>
        `;
    }

    /**
     * Format resource amount
     */
    formatAmount(amount) {
        if (amount >= 1000000) {
            return (amount / 1000000).toFixed(1) + 'M';
        }
        if (amount >= 1000) {
            return (amount / 1000).toFixed(1) + 'K';
        }
        return amount.toString();
    }

    /**
     * Invalidate cache for entity (no-op, data is now live from ResourceTransportManager)
     */
    invalidateCache(entityId) {
        // No longer needed - resources are read directly from ResourceTransportManager
    }

    /**
     * Clear all cache (no-op, data is now live from ResourceTransportManager)
     */
    clearCache() {
        // No longer needed - resources are read directly from ResourceTransportManager
    }
}

export default EntityTooltip;
