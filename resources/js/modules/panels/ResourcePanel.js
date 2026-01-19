import { BasePanel } from './BasePanel.js';

/**
 * ResourcePanel - displays player's current resources at the top of screen
 */
export class ResourcePanel extends BasePanel {
    constructor(game) {
        super(game);
        this.resourceElements = {};
        this.dropdown = null;
        this.moreBtn = null;
        this.isDropdownOpen = false;
        this.visibleResources = [];
        this.hiddenResources = [];
    }

    /**
     * Initialize panel UI
     */
    init() {
        this.createElement();
        this.refresh();

        // Recalculate on window resize
        window.addEventListener('resize', () => this.refresh());
    }

    /**
     * Create panel HTML element
     */
    createElement() {
        this.element = document.createElement('div');
        this.element.id = 'resource-panel';
        document.body.appendChild(this.element);

        // Close dropdown on click outside
        document.addEventListener('click', (e) => {
            if (this.isDropdownOpen && !this.element.contains(e.target)) {
                this.closeDropdown();
            }
        });
    }

    /**
     * Refresh panel content based on current resources
     */
    refresh() {
        if (!this.element) return;

        // Clear existing content
        this.element.innerHTML = '';
        this.resourceElements = {};
        this.dropdown = null;
        this.moreBtn = null;
        this.isDropdownOpen = false;

        // Get all resources player has (quantity > 0) or resources used in building costs
        const displayedResources = new Set();

        // Add resources player currently has
        for (const resourceId in this.game.userResources) {
            const quantity = this.game.userResources[resourceId];
            displayedResources.add(parseInt(resourceId));
        }

        // Add resources used in any building cost (even if player has 0)
        for (const entityTypeId in this.game.entityTypes) {
            const costs = this.game.entityTypes[entityTypeId]?.costs;
            if (costs) {
                for (const resourceId in costs) {
                    displayedResources.add(parseInt(resourceId));
                }
            }
        }

        // Sort by resource_id for consistent display
        const sortedResourceIds = Array.from(displayedResources).sort((a, b) => a - b);

        // Calculate how many resources fit in the panel
        this.calculateVisibleResources(sortedResourceIds);

        // Create visible resource items
        for (const resourceId of this.visibleResources) {
            const resource = this.game.resources[resourceId];
            if (!resource) continue;

            const item = this.createResourceItem(resourceId, resource);
            this.element.appendChild(item);
            this.resourceElements[resourceId] = item;
        }

        // Create "..." button if there are hidden resources
        if (this.hiddenResources.length > 0) {
            this.createMoreButton();
        }
    }

    /**
     * Calculate which resources should be visible based on available width
     */
    calculateVisibleResources(allResourceIds) {
        // Estimate item width: icon(20px) + gap(4px) + text(30-50px) + padding(8px) = ~70px
        const itemWidth = 70;
        const moreBtnWidth = 40;
        const panelPadding = 20; // left + right padding
        const panelBorder = 2;
        const gap = 4; // gap between items

        // Available width
        const availableWidth = window.innerWidth - 40; // 20px margin on each side

        // Calculate max visible items
        let maxItems = Math.floor((availableWidth - panelPadding - panelBorder) / (itemWidth + gap));

        // Reserve space for "..." button if needed
        if (allResourceIds.length > maxItems) {
            maxItems = Math.floor((availableWidth - panelPadding - panelBorder - moreBtnWidth) / (itemWidth + gap));
        }

        // Ensure at least 3 items visible
        maxItems = Math.max(3, maxItems);

        this.visibleResources = allResourceIds.slice(0, maxItems);
        this.hiddenResources = allResourceIds.slice(maxItems);
    }

    /**
     * Create "..." button for showing hidden resources
     */
    createMoreButton() {
        this.moreBtn = document.createElement('div');
        this.moreBtn.className = 'more-resources-btn';
        this.moreBtn.textContent = '...';
        this.moreBtn.title = `Show ${this.hiddenResources.length} more resources`;

        this.moreBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });

        this.element.appendChild(this.moreBtn);
    }

    /**
     * Toggle dropdown visibility
     */
    toggleDropdown() {
        if (this.isDropdownOpen) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }

    /**
     * Open dropdown with hidden resources
     */
    openDropdown() {
        this.closeDropdown(); // Close if already open

        this.dropdown = document.createElement('div');
        this.dropdown.className = 'resource-dropdown';

        // Add hidden resources to dropdown
        for (const resourceId of this.hiddenResources) {
            const resource = this.game.resources[resourceId];
            if (!resource) continue;

            const item = this.createResourceItem(resourceId, resource);
            this.dropdown.appendChild(item);
            this.resourceElements[resourceId] = item;
        }

        this.element.appendChild(this.dropdown);
        this.isDropdownOpen = true;
    }

    /**
     * Close dropdown
     */
    closeDropdown() {
        if (this.dropdown) {
            this.dropdown.remove();
            this.dropdown = null;
        }

        // Remove hidden resource elements from tracking
        for (const resourceId of this.hiddenResources) {
            delete this.resourceElements[resourceId];
        }

        this.isDropdownOpen = false;
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
        icon.src = `${resource.icon_url}?v=${this.game.config.assetVersion}`;
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
            // Resource not displayed yet, refresh entire panel to recalculate layout
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
        // Update visible resources
        for (const resourceId of this.visibleResources) {
            const item = this.resourceElements[resourceId];
            if (!item) continue;

            const quantity = this.game.userResources[resourceId] || 0;
            const quantityEl = item.querySelector('.resource-quantity');
            if (quantityEl) {
                quantityEl.textContent = this.formatQuantity(quantity);
            }
        }

        // Update hidden resources if dropdown is open
        if (this.isDropdownOpen) {
            for (const resourceId of this.hiddenResources) {
                const item = this.resourceElements[resourceId];
                if (!item) continue;

                const quantity = this.game.userResources[resourceId] || 0;
                const quantityEl = item.querySelector('.resource-quantity');
                if (quantityEl) {
                    quantityEl.textContent = this.formatQuantity(quantity);
                }
            }
        }
    }
}

export default ResourcePanel;
