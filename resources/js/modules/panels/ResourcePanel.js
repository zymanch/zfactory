import { BasePanel } from './BasePanel.js';

/**
 * ResourcePanel - displays player's current resources at the top of screen
 */
export class ResourcePanel extends BasePanel {
    constructor(game) {
        super(game);
        this.resourceElements = {};
    }

    /**
     * Initialize panel UI
     */
    init() {
        this.createElement();
        this.refresh();
    }

    /**
     * Create panel HTML element
     */
    createElement() {
        this.element = document.createElement('div');
        this.element.id = 'resource-panel';
        document.body.appendChild(this.element);
    }

    /**
     * Refresh panel content based on current resources
     */
    refresh() {
        if (!this.element) return;

        // Clear existing content
        this.element.innerHTML = '';
        this.resourceElements = {};

        // Get all resources player has (quantity > 0) or resources used in building costs
        const displayedResources = new Set();

        // Add resources player currently has
        for (const resourceId in this.game.userResources) {
            const quantity = this.game.userResources[resourceId];
            displayedResources.add(parseInt(resourceId));
        }

        // Add resources used in any building cost (even if player has 0)
        for (const entityTypeId in this.game.entityTypeCosts) {
            const costs = this.game.entityTypeCosts[entityTypeId];
            for (const resourceId in costs) {
                displayedResources.add(parseInt(resourceId));
            }
        }

        // Sort by resource_id for consistent display
        const sortedResourceIds = Array.from(displayedResources).sort((a, b) => a - b);

        // Create resource items
        for (const resourceId of sortedResourceIds) {
            const resource = this.game.resources[resourceId];
            if (!resource) continue;

            const item = this.createResourceItem(resourceId, resource);
            this.element.appendChild(item);
            this.resourceElements[resourceId] = item;
        }
    }

    /**
     * Create single resource item element
     */
    createResourceItem(resourceId, resource) {
        const quantity = this.game.userResources[resourceId] || 0;

        const item = document.createElement('div');
        item.className = 'resource-item';
        item.dataset.resourceId = resourceId;

        // Resource icon
        const icon = document.createElement('img');
        icon.src = `${this.game.config.tilesPath}resources/${resource.icon_url}?v=${this.game.config.assetVersion}`;
        icon.width = 20;
        icon.height = 20;
        icon.title = resource.name;
        icon.alt = resource.name;

        // Quantity text
        const text = document.createElement('span');
        text.className = 'resource-quantity';
        text.textContent = this.formatQuantity(quantity);

        item.appendChild(icon);
        item.appendChild(text);

        return item;
    }

    /**
     * Format quantity for display (e.g., 1234 -> "1.2k")
     */
    formatQuantity(quantity) {
        if (quantity >= 1000000) {
            return (quantity / 1000000).toFixed(1) + 'M';
        } else if (quantity >= 1000) {
            return (quantity / 1000).toFixed(1) + 'k';
        }
        return quantity.toString();
    }

    /**
     * Update specific resource display
     */
    updateResource(resourceId) {
        const item = this.resourceElements[resourceId];
        if (!item) {
            // Resource not displayed yet, refresh entire panel
            this.refresh();
            return;
        }

        const quantity = this.game.userResources[resourceId] || 0;
        const quantityEl = item.querySelector('.resource-quantity');
        if (quantityEl) {
            quantityEl.textContent = this.formatQuantity(quantity);
        }
    }

    /**
     * Update all resource displays
     */
    updateAll() {
        for (const resourceId in this.resourceElements) {
            this.updateResource(parseInt(resourceId));
        }
    }
}

export default ResourcePanel;
