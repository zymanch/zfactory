import { getEntityIconUrl } from '../utils.js';
import { GameMode } from '../modes/gameModeManager.js';

/**
 * BuildingWindow - modal window showing available entities grouped by type
 */
export class BuildingWindow {
    constructor(game) {
        this.game = game;
        this.isOpen = false;
        this.element = null;
        this.activeTab = null;

        this.tabConfig = {
            'building': 'Здания',
            'ship': 'Корабль',
            'mining': 'Добыча',
            'manipulator': 'Манипуляторы',
            'conveyor': 'Конвейеры',
            'pipe': 'Трубы',
            'electricity': 'Электричество',
            'resource': 'Ресурсы',
            'eye': 'Башни'
        };
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
        this.element.id = 'building-window';
        this.element.className = 'game-window';
        this.element.style.display = 'none';

        this.element.innerHTML = `
            <div class="window-tabs"></div>
            <div class="window-main">
                <div class="window-header">
                    <span class="window-title">Объекты</span>
                    <button class="window-close">&times;</button>
                </div>
                <div class="window-content">
                    <div class="buildings-grid"></div>
                </div>
            </div>
            <div class="building-tooltips"></div>
        `;

        document.body.appendChild(this.element);

        // Close button returns to NORMAL mode
        this.element.querySelector('.window-close').addEventListener('click', () => {
            this.game.gameModeManager.returnToNormalMode();
        });

        this.element.addEventListener('click', (e) => e.stopPropagation());
    }

    /**
     * Get entity types grouped by type
     * Excludes orientation variants (entities with parent_entity_type_id)
     * HQ entities are merged into 'building' group
     */
    getGroupedEntityTypes() {
        const groups = {};

        for (const typeId in this.game.entityTypes) {
            const entityType = this.game.entityTypes[typeId];

            // Skip orientation variants - only show base entities
            if (entityType.parent_entity_type_id) {
                continue;
            }

            // Merge 'hq' type into 'building' group
            const type = entityType.type === 'hq' ? 'building' : entityType.type;

            if (!groups[type]) {
                groups[type] = [];
            }
            groups[type].push({ ...entityType, id: typeId, originalType: entityType.type });
        }

        return groups;
    }

    /**
     * Create tabs based on available entity types
     */
    createTabs() {
        const tabsContainer = this.element.querySelector('.window-tabs');
        tabsContainer.innerHTML = '';

        const groups = this.getGroupedEntityTypes();
        let firstTab = null;

        for (const type in this.tabConfig) {
            if (!groups[type] || groups[type].length === 0) continue;

            const tab = document.createElement('button');
            tab.className = 'window-tab';
            tab.dataset.type = type;
            tab.textContent = this.tabConfig[type];
            tab.addEventListener('click', () => this.selectTab(type));

            tabsContainer.appendChild(tab);
            if (!firstTab) firstTab = type;
        }

        if (firstTab) {
            this.selectTab(firstTab);
        }
    }

    /**
     * Select tab and show its content
     */
    selectTab(type) {
        this.activeTab = type;

        const tabs = this.element.querySelectorAll('.window-tab');
        tabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.type === type);
        });

        this.populateGrid(type);
    }

    /**
     * Populate grid with entities of specified type
     */
    populateGrid(type) {
        const grid = this.element.querySelector('.buildings-grid');
        grid.innerHTML = '';

        const groups = this.getGroupedEntityTypes();
        let entities = groups[type] || [];

        // Sort: HQ buildings first, then others
        entities.sort((a, b) => {
            if (a.originalType === 'hq' && b.originalType !== 'hq') return -1;
            if (a.originalType !== 'hq' && b.originalType === 'hq') return 1;
            return 0;
        });

        for (const entityType of entities) {
            const item = this.createBuildingItem(entityType);
            grid.appendChild(item);
        }
    }

    /**
     * Create building item element
     */
    createBuildingItem(entityType) {
        const typeId = entityType.id;
        const iconUrl = getEntityIconUrl(
            entityType,
            this.game.config.tilesPath,
            this.game.config.assetVersion || 1
        );

        const item = document.createElement('div');
        item.className = 'building-item';
        item.draggable = true;
        item.dataset.entityTypeId = typeId;

        // Create tooltip with name, description and cost information
        const costs = this.game.entityTypeCosts[typeId];
        let tooltipHtml = `<strong>${entityType.name}</strong>`;

        // Add description if available
        if (entityType.description) {
            tooltipHtml += `<div class="tooltip-description">${entityType.description}</div>`;
        }

        if (costs && Object.keys(costs).length > 0) {
            tooltipHtml += '<div class="tooltip-costs">';

            for (const [resourceId, quantity] of Object.entries(costs)) {
                const resource = this.game.resources[resourceId];
                if (!resource) continue;

                const available = this.game.userResources[resourceId] || 0;
                const canAfford = available >= quantity;
                const costClass = canAfford ? 'tooltip-cost-item' : 'tooltip-cost-item insufficient';

                tooltipHtml += `
                    <div class="${costClass}">
                        <img src="${this.game.config.tilesPath}resources/${resource.icon_url}?v=${this.game.config.assetVersion}"
                             width="16" height="16" title="${resource.name}">
                        <span>${quantity}</span>
                        <span class="available">(${available})</span>
                    </div>
                `;
            }

            tooltipHtml += '</div>';
        }

        item.innerHTML = `
            <div class="building-icon" style="background-image: url('${iconUrl}')"></div>
            <div class="building-name">${entityType.name}</div>
        `;

        // Store tooltip HTML in dataset
        item.dataset.tooltipHtml = tooltipHtml;

        // Show tooltip on hover
        item.addEventListener('mouseenter', (e) => {
            const tooltipContainer = this.element.querySelector('.building-tooltips');
            const itemRect = item.getBoundingClientRect();
            const containerRect = tooltipContainer.getBoundingClientRect();

            const tooltip = document.createElement('div');
            tooltip.className = 'building-tooltip-floating';
            tooltip.innerHTML = item.dataset.tooltipHtml;

            // Calculate position relative to tooltip container
            const left = itemRect.left - containerRect.left + itemRect.width / 2;
            const top = itemRect.bottom - containerRect.top + 8;

            tooltip.style.left = `${left}px`;
            tooltip.style.top = `${top}px`;
            tooltip.style.transform = 'translateX(-50%)';

            tooltipContainer.appendChild(tooltip);

            // Trigger reflow to enable transition
            tooltip.offsetHeight;
            tooltip.classList.add('visible');

            // Store reference
            item._tooltip = tooltip;
        });

        item.addEventListener('mouseleave', () => {
            if (item._tooltip) {
                item._tooltip.classList.remove('visible');
                setTimeout(() => {
                    if (item._tooltip && item._tooltip.parentNode) {
                        item._tooltip.parentNode.removeChild(item._tooltip);
                    }
                    item._tooltip = null;
                }, 200);
            }
        });

        item.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('entityTypeId', typeId);
            e.dataTransfer.effectAllowed = 'copy';
            item.classList.add('dragging');
        });

        item.addEventListener('dragend', () => {
            item.classList.remove('dragging');
        });

        item.addEventListener('click', () => {
            const entityTypeId = parseInt(typeId);

            // Close window and switch to BUILD mode
            this.game.gameModeManager.switchMode(GameMode.BUILD, { entityTypeId });
        });

        return item;
    }

    /**
     * Add entity to first empty slot in build panel
     */
    addToFirstEmptySlot(entityTypeId) {
        if (!this.game.buildPanel) return;

        for (let i = 0; i < 10; i++) {
            if (!this.game.buildPanel.slots[i]) {
                this.game.buildPanel.setSlot(i, entityTypeId);
                break;
            }
        }
    }

    /**
     * Open window
     */
    open() {
        this.createTabs();
        this.element.style.display = 'flex';
        this.isOpen = true;
    }

    /**
     * Close window (called by GameModeManager during deactivation)
     */
    close() {
        this.element.style.display = 'none';
        this.isOpen = false;
    }

    /**
     * Toggle window visibility
     */
    toggle() {
        this.isOpen ? this.close() : this.open();
    }
}

export default BuildingWindow;
