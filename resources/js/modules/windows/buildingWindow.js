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
            'mining': 'Добыча',
            'manipulator': 'Манипуляторы',
            'transporter': 'Транспорт',
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
            <div class="window-header">
                <span class="window-title">Объекты</span>
                <button class="window-close">&times;</button>
            </div>
            <div class="window-tabs"></div>
            <div class="window-content">
                <div class="buildings-grid"></div>
            </div>
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
     */
    getGroupedEntityTypes() {
        const groups = {};

        for (const typeId in this.game.entityTypes) {
            const entityType = this.game.entityTypes[typeId];

            // Skip orientation variants - only show base entities
            if (entityType.parent_entity_type_id) {
                continue;
            }

            const type = entityType.type;

            if (!groups[type]) {
                groups[type] = [];
            }
            groups[type].push({ ...entityType, id: typeId });
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
        const entities = groups[type] || [];

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

        item.innerHTML = `
            <div class="building-icon" style="background-image: url('${iconUrl}')"></div>
            <div class="building-name">${entityType.name}</div>
        `;

        // Add cost display if building has a cost
        const costs = this.game.entityTypeCosts[typeId];
        if (costs && Object.keys(costs).length > 0) {
            const costDiv = document.createElement('div');
            costDiv.className = 'entity-cost';

            for (const [resourceId, quantity] of Object.entries(costs)) {
                const resource = this.game.resources[resourceId];
                if (!resource) continue;

                const available = this.game.userResources[resourceId] || 0;
                const canAfford = available >= quantity;

                const costItem = document.createElement('div');
                costItem.className = canAfford ? 'cost-item' : 'cost-item insufficient';

                // Resource icon (16x16)
                const icon = document.createElement('img');
                icon.src = `${this.game.config.tilesPath}resources/${resource.icon_url}?v=${this.game.config.assetVersion}`;
                icon.width = 16;
                icon.height = 16;
                icon.title = resource.name;

                // Quantity
                const text = document.createElement('span');
                text.textContent = `${quantity}`;

                costItem.appendChild(icon);
                costItem.appendChild(text);
                costDiv.appendChild(costItem);
            }

            item.appendChild(costDiv);
        }

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
