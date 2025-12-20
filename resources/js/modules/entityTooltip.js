import { getCSRFToken } from './utils.js';
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
        this.resourceCache = new Map();
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
     * Get entity resources from server or cache
     */
    async getEntityResources(entityId) {
        const cacheKey = `entity_${entityId}`;

        // Check cache
        if (this.resourceCache.has(cacheKey)) {
            return this.resourceCache.get(cacheKey);
        }

        try {
            const response = await fetch(`${this.game.config.entityResourcesUrl}?entity_id=${entityId}`, {
                headers: {
                    'X-CSRF-Token': getCSRFToken()
                }
            });
            const data = await response.json();

            if (data.result === 'ok') {
                this.resourceCache.set(cacheKey, data.resources);
                return data.resources;
            }
        } catch (e) {
            console.error('Error fetching entity resources:', e);
        }

        return [];
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
     * Invalidate cache for entity
     */
    invalidateCache(entityId) {
        this.resourceCache.delete(`entity_${entityId}`);
    }

    /**
     * Clear all cache
     */
    clearCache() {
        this.resourceCache.clear();
    }
}

export default EntityTooltip;
