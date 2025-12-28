import { GameMode } from '../modes/gameModeManager.js';

/**
 * EntityInfoWindow - окно информации о выбранном entity
 * Показывает: спрайт, здоровье, описание, ресурсы, рецепты, прогресс крафта
 */
export class EntityInfoWindow {
    constructor(game) {
        this.game = game;
        this.isOpen = false;
        this.currentEntityId = null;
        this.element = null;
        this.updateInterval = null;
    }

    /**
     * Initialize window UI
     */
    init() {
        this.createElement();
    }

    /**
     * Create window HTML element
     */
    createElement() {
        this.element = document.createElement('div');
        this.element.id = 'entity-info-window';
        this.element.className = 'game-window';
        this.element.style.cssText = `
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            max-height: 80vh;
            background: rgba(20, 20, 30, 0.95);
            border: 2px solid #4a4a5a;
            border-radius: 8px;
            padding: 0;
            z-index: 10000;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.7);
        `;

        this.element.innerHTML = `
            <div class="window-header" style="background: #2a2a3a; padding: 12px 15px; border-bottom: 1px solid #4a4a5a; display: flex; justify-content: space-between; align-items: center;">
                <span class="window-title" style="font-size: 16px; font-weight: bold; color: #fff;"></span>
                <button class="window-close" style="background: transparent; border: none; color: #fff; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
            </div>
            <div class="window-content" style="padding: 15px; overflow-y: auto; max-height: calc(80vh - 60px); color: #fff;"></div>
        `;

        document.body.appendChild(this.element);

        // Close button returns to NORMAL mode
        this.element.querySelector('.window-close').addEventListener('click', () => {
            this.game.gameModeManager.returnToNormalMode();
        });

        this.element.addEventListener('click', (e) => e.stopPropagation());
    }

    /**
     * Open window for entity
     */
    open(entityId) {
        this.currentEntityId = entityId;
        this.isOpen = true;
        this.renderContent();
        this.element.style.display = 'flex';
        this.element.style.flexDirection = 'column';

        // Start update interval for real-time progress
        this.startUpdateInterval();
    }

    /**
     * Close window (called by GameModeManager during deactivation)
     */
    close() {
        this.element.style.display = 'none';
        this.isOpen = false;
        this.currentEntityId = null;
        this.stopUpdateInterval();
    }

    /**
     * Start interval to update content in real-time
     */
    startUpdateInterval() {
        this.stopUpdateInterval();

        // Update every 100ms for smooth progress bars
        this.updateInterval = setInterval(() => {
            if (this.isOpen && this.currentEntityId) {
                this.renderContent();
            }
        }, 100);
    }

    /**
     * Stop update interval
     */
    stopUpdateInterval() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
    }

    /**
     * Render window content
     */
    renderContent() {
        const entityKey = `entity_${this.currentEntityId}`;
        const entity = this.game.entityData.get(entityKey);

        if (!entity) {
            this.close();
            return;
        }

        const entityType = this.game.entityTypes[entity.entity_type_id];
        if (!entityType) {
            this.close();
            return;
        }

        // Update title
        this.element.querySelector('.window-title').textContent = entityType.name;

        // Build content HTML
        let html = '';

        // Sprite
        const iconUrl = this.getEntityIconUrl(entityType);
        html += `
            <div style="text-align: center; margin-bottom: 15px;">
                <img src="${iconUrl}" style="width: 64px; height: 64px; image-rendering: pixelated;">
            </div>
        `;

        // Coordinates
        html += `<div style="font-size: 11px; color: #888; margin-bottom: 10px; text-align: center;">Позиция: ${entity.x}, ${entity.y}</div>`;

        // Durability bar
        html += this.renderDurabilityBar(entity, entityType);

        // Description
        if (entityType.description) {
            html += `
                <div style="margin: 15px 0; padding: 10px; background: rgba(0,0,0,0.3); border-radius: 4px; border-left: 3px solid #4a9;">
                    <div style="font-size: 12px; color: #ccc; line-height: 1.4;">${entityType.description}</div>
                </div>
            `;
        }

        // Construction progress (if building)
        if (entity.state === 'blueprint' || (entity.construction_progress !== undefined && entity.construction_progress < 100)) {
            html += this.renderConstructionProgress(entity, entityType);
        }

        // Resources
        html += this.renderResources(entity);

        // Active crafting
        html += this.renderActiveCrafting(entity);

        // Available recipes
        html += this.renderRecipes(entityType);

        // Type-specific content placeholder
        html += `<div id="entity-type-specific-content"></div>`;

        this.element.querySelector('.window-content').innerHTML = html;
    }

    /**
     * Get entity icon URL
     */
    getEntityIconUrl(entityType) {
        const v = this.game.config.assetVersion || 1;
        const iconUrl = entityType.icon_url || `${entityType.image_url}/normal.${entityType.extension || 'png'}`;
        return `/assets/tiles/entities/${iconUrl}?v=${v}`;
    }

    /**
     * Render durability bar
     */
    renderDurabilityBar(entity, entityType) {
        const durability = parseInt(entity.durability) || 0;
        const maxDurability = parseInt(entityType.max_durability) || 100;
        const durabilityPercent = Math.min(100, (durability / maxDurability) * 100);
        const durabilityColor = durabilityPercent > 50 ? '#4a9' : (durabilityPercent > 25 ? '#fa0' : '#f44');

        // Check if destructible
        const isDestructible = maxDurability > 0 && maxDurability < 9999;

        if (!isDestructible) {
            return `
                <div style="margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="font-size: 12px;">Здоровье</span>
                        <span style="font-size: 12px; color: #4a9;">Неразрушимый</span>
                    </div>
                    <div style="background: #333; height: 8px; border-radius: 4px; overflow: hidden;">
                        <div style="width: 100%; height: 100%; background: #4a9;"></div>
                    </div>
                </div>
            `;
        }

        return `
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="font-size: 12px;">Здоровье</span>
                    <span style="font-size: 12px;">${durability} / ${maxDurability}</span>
                </div>
                <div style="background: #333; height: 8px; border-radius: 4px; overflow: hidden;">
                    <div style="width: ${durabilityPercent}%; height: 100%; background: ${durabilityColor}; transition: width 0.3s;"></div>
                </div>
            </div>
        `;
    }

    /**
     * Render construction progress
     */
    renderConstructionProgress(entity, entityType) {
        const progress = parseInt(entity.construction_progress) || 0;
        const constructionTicks = parseInt(entityType.construction_ticks) || 60;
        const ticksPerSecond = 60;
        const totalSeconds = constructionTicks / ticksPerSecond;
        const remainingSeconds = Math.ceil((constructionTicks * (100 - progress) / 100) / ticksPerSecond);

        return `
            <div style="margin: 15px 0; padding: 10px; background: rgba(70, 130, 180, 0.2); border-radius: 4px; border-left: 3px solid #4682b4;">
                <div style="font-weight: bold; margin-bottom: 8px; color: #4682b4;">Строительство</div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 12px;">
                    <span>Прогресс</span>
                    <span>${progress}% (${remainingSeconds}s)</span>
                </div>
                <div style="background: #333; height: 10px; border-radius: 5px; overflow: hidden;">
                    <div style="width: ${progress}%; height: 100%; background: linear-gradient(90deg, #4682b4, #6ca0dc); transition: width 0.1s;"></div>
                </div>
            </div>
        `;
    }

    /**
     * Render resources
     */
    renderResources(entity) {
        const resources = this.getEntityResources(entity.entity_id);
        if (!resources || resources.length === 0) {
            return '';
        }

        const v = this.game.config.assetVersion || 1;
        let html = `
            <div style="margin: 15px 0; padding: 10px; background: rgba(0,0,0,0.3); border-radius: 4px;">
                <div style="font-weight: bold; margin-bottom: 8px;">Ресурсы:</div>
        `;

        for (const res of resources) {
            html += `
                <div style="display: flex; align-items: center; margin: 4px 0; font-size: 13px;">
                    <img src="/assets/tiles/resources/${res.icon_url}?v=${v}" width="20" height="20" style="margin-right: 8px;">
                    <span style="flex: 1;">${res.name}</span>
                    <span style="color: #8af; font-weight: bold;">${this.formatAmount(res.amount)}</span>
                </div>
            `;
        }

        html += `</div>`;
        return html;
    }

    /**
     * Get entity resources
     */
    getEntityResources(entityId) {
        const rt = this.game.resourceTransport;
        if (!rt) return [];

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

        return [];
    }

    /**
     * Render active crafting
     */
    renderActiveCrafting(entity) {
        const buildingState = this.game.resourceTransport?.buildings.get(entity.entity_id);
        if (!buildingState || !buildingState.isCrafting()) {
            return '';
        }

        const recipeId = buildingState.craftingRecipeId;
        const recipe = this.game.recipes?.[String(recipeId)];
        if (!recipe) return '';

        const totalTicks = buildingState.calculateCraftTime(parseInt(recipe.ticks));
        const elapsed = totalTicks - buildingState.craftingTicksRemaining;
        const progress = Math.min(100, Math.round((elapsed / totalTicks) * 100));

        const outputResource = this.game.resources?.[String(recipe.output_resource_id)];
        const outputName = outputResource?.name || 'Unknown';
        const outputIcon = outputResource?.icon_url || '';

        const ticksPerSecond = 60;
        const secondsRemaining = Math.ceil(buildingState.craftingTicksRemaining / ticksPerSecond);

        const v = this.game.config.assetVersion || 1;

        return `
            <div style="margin: 15px 0; padding: 10px; background: rgba(0,150,0,0.2); border-radius: 4px; border-left: 3px solid #4a9;">
                <div style="font-weight: bold; margin-bottom: 8px; color: #4a9;">Крафт:</div>
                <div style="display: flex; align-items: center; margin-bottom: 8px;">
                    <img src="/assets/tiles/resources/${outputIcon}?v=${v}" width="20" height="20" style="margin-right: 8px;">
                    <span style="flex: 1;">${outputName}</span>
                    <span style="color: #aaa;">${secondsRemaining}s</span>
                </div>
                <div style="background: #333; height: 10px; border-radius: 5px; overflow: hidden;">
                    <div style="width: ${progress}%; height: 100%; background: linear-gradient(90deg, #4a9, #6c6); transition: width 0.1s;"></div>
                </div>
            </div>
        `;
    }

    /**
     * Render available recipes
     */
    renderRecipes(entityType) {
        const recipeIds = this.game.entityTypeRecipes?.[entityType.entity_type_id];
        if (!recipeIds || recipeIds.length === 0) {
            return '';
        }

        const power = parseInt(entityType.power) || 100;
        const v = this.game.config.assetVersion || 1;

        let html = `
            <div style="margin: 15px 0; padding: 10px; background: rgba(0,0,0,0.3); border-radius: 4px;">
                <div style="font-weight: bold; margin-bottom: 8px;">Доступные рецепты:</div>
        `;

        for (const recipeId of recipeIds) {
            const recipe = this.game.recipes?.[recipeId];
            if (!recipe) continue;

            html += this.renderRecipe(recipe, power, v);
        }

        html += `</div>`;
        return html;
    }

    /**
     * Render single recipe
     */
    renderRecipe(recipe, power, assetVersion) {
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

        const output = { id: recipe.output_resource_id, amount: recipe.output_amount };

        let html = `<div style="display: flex; align-items: center; margin: 6px 0; flex-wrap: wrap; font-size: 13px;">`;

        // Inputs
        for (let i = 0; i < inputs.length; i++) {
            const input = inputs[i];
            const res = this.game.resources?.[input.id];
            if (!res) continue;

            if (i > 0) {
                html += `<span style="margin: 0 4px; color: #888;">+</span>`;
            }
            html += this.renderResourceIcon(res, input.amount, assetVersion);
        }

        // Arrow
        html += `<span style="margin: 0 8px; color: #4a9; font-size: 16px;">→</span>`;

        // Output
        const outRes = this.game.resources?.[output.id];
        if (outRes) {
            html += this.renderResourceIcon(outRes, output.amount, assetVersion);
        }

        // Time
        const timeSeconds = this.formatRecipeTime(recipe.ticks, power);
        html += `<span style="margin-left: auto; color: #888; font-size: 11px;">${timeSeconds}s</span>`;

        html += `</div>`;
        return html;
    }

    /**
     * Render resource icon with amount
     */
    renderResourceIcon(resource, amount, version) {
        const iconUrl = `/assets/tiles/resources/${resource.icon_url}?v=${version}`;
        return `
            <div style="display: inline-flex; align-items: center; margin: 0 2px;" title="${resource.name}">
                <img src="${iconUrl}" width="18" height="18" style="margin-right: 3px;">
                <span style="color: #aaa; font-size: 12px;">${amount}</span>
            </div>
        `;
    }

    /**
     * Format recipe time
     */
    formatRecipeTime(ticks, power = 100) {
        const time = (ticks / 60) * (100 / power);
        return time % 1 === 0 ? time.toString() : time.toFixed(1);
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
}

export default EntityInfoWindow;
