import { BasePanel } from './BasePanel.js';
import { getEntityIconUrl } from '../utils.js';
import { SAVE_DEBOUNCE_DELAY } from '../constants.js';
import { GameMode } from '../modes/gameModeManager.js';

/**
 * BuildPanel - 10-slot hotbar for quick building access
 */
export class BuildPanel extends BasePanel {
    constructor(game) {
        super(game);
        this.slots = new Array(10).fill(null);
        this.activeSlot = -1;
        this.slotElements = [];
        this.saveTimeout = null;
    }

    /**
     * Initialize panel UI
     */
    init() {
        this.createElement();
        this.loadFromServer();
    }

    /**
     * Create panel HTML element
     */
    createElement() {
        this.element = document.createElement('div');
        this.element.id = 'build-panel';
        this.element.innerHTML = this.getHTML();
        document.body.appendChild(this.element);

        this.slotElements = Array.from(this.element.querySelectorAll('.build-panel-slot'));
        this.slotElements.forEach((slot, index) => this.setupSlotEvents(slot, index));
    }

    /**
     * Setup event handlers for a slot
     */
    setupSlotEvents(slot, index) {
        slot.addEventListener('click', () => this.activateSlot(index));

        slot.addEventListener('dragover', (e) => {
            e.preventDefault();
            slot.classList.add('dragover');
        });

        slot.addEventListener('dragleave', () => {
            slot.classList.remove('dragover');
        });

        slot.addEventListener('drop', (e) => {
            e.preventDefault();
            slot.classList.remove('dragover');
            const entityTypeId = e.dataTransfer.getData('entityTypeId');
            if (entityTypeId) {
                this.setSlot(index, parseInt(entityTypeId));
            }
        });

        slot.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            this.clearSlot(index);
        });
    }

    /**
     * Get HTML template
     */
    getHTML() {
        return Array.from({ length: 10 }, (_, i) => {
            const key = i === 9 ? '0' : (i + 1).toString();
            return `
                <div class="build-panel-slot" data-slot="${i}">
                    <span class="slot-key">${key}</span>
                    <div class="slot-icon"></div>
                </div>
            `;
        }).join('');
    }

    /**
     * Set entity type in slot
     */
    setSlot(index, entityTypeId) {
        if (index < 0 || index >= 10) return;
        this.slots[index] = entityTypeId;
        this.updateSlotVisual(index);
        this.saveToServer();
    }

    /**
     * Clear slot
     */
    clearSlot(index) {
        if (index < 0 || index >= 10) return;
        this.slots[index] = null;
        this.updateSlotVisual(index);
        this.saveToServer();

        if (this.activeSlot === index) {
            this.deactivateSlot();
        }
    }

    /**
     * Update slot visual
     */
    updateSlotVisual(index) {
        const slot = this.slotElements[index];
        if (!slot) return;

        const iconEl = slot.querySelector('.slot-icon');
        const entityTypeId = this.slots[index];

        if (entityTypeId && this.game.entityTypes[entityTypeId]) {
            const entityType = this.game.entityTypes[entityTypeId];
            const iconUrl = getEntityIconUrl(
                entityType,
                this.game.config.tilesPath,
                this.game.config.assetVersion || 1
            );
            iconEl.style.backgroundImage = `url('${iconUrl}')`;
            iconEl.classList.add('has-icon');
        } else {
            iconEl.style.backgroundImage = '';
            iconEl.classList.remove('has-icon');
        }

        // Update affordability visual
        this.updateSlotAffordability(index);
    }

    /**
     * Check if user can afford building
     */
    canAffordBuilding(entityTypeId) {
        const costs = this.game.entityTypeCosts[entityTypeId];
        if (!costs) return true; // No cost = free

        for (const [resourceId, quantity] of Object.entries(costs)) {
            const available = this.game.userResources[resourceId] || 0;
            if (available < quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Update affordability visual for specific slot
     */
    updateSlotAffordability(index) {
        const slot = this.slotElements[index];
        if (!slot) return;

        const entityTypeId = this.slots[index];
        if (!entityTypeId) {
            slot.classList.remove('unaffordable');
            return;
        }

        const canAfford = this.canAffordBuilding(entityTypeId);
        slot.classList.toggle('unaffordable', !canAfford);
    }

    /**
     * Update affordability for all slots (call after resource changes)
     */
    updateAffordability() {
        this.slotElements.forEach((_, index) => {
            this.updateSlotAffordability(index);
        });
    }

    /**
     * Activate slot (start building mode)
     */
    activateSlot(index) {
        if (index < 0 || index >= 10) return;

        const entityTypeId = this.slots[index];
        if (!entityTypeId) return;

        if (this.activeSlot >= 0) {
            this.slotElements[this.activeSlot]?.classList.remove('active');
        }

        if (this.activeSlot === index) {
            this.deactivateSlot();
            return;
        }

        this.activeSlot = index;
        this.slotElements[index].classList.add('active');

        // Switch to BUILD mode via GameModeManager
        this.game.gameModeManager.switchMode(GameMode.BUILD, { entityTypeId });
    }

    /**
     * Deactivate current slot
     */
    deactivateSlot() {
        if (this.activeSlot >= 0) {
            this.slotElements[this.activeSlot]?.classList.remove('active');
        }
        this.activeSlot = -1;

        // Return to normal mode via GameModeManager
        if (this.game.gameModeManager.isMode(GameMode.BUILD)) {
            this.game.gameModeManager.returnToNormalMode();
        }
    }

    /**
     * Save slots to server (debounced)
     */
    saveToServer() {
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }

        this.saveTimeout = setTimeout(async () => {
            const url = this.game.config.saveBuildPanelUrl;
            if (!url) return;

            try {
                await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ slots: this.slots })
                });
            } catch (e) {
                console.warn('Failed to save build panel:', e);
            }
        }, SAVE_DEBOUNCE_DELAY);
    }

    /**
     * Load slots from server (via initial config)
     */
    loadFromServer() {
        const initialPanel = this.game.initialBuildPanel;
        if (Array.isArray(initialPanel)) {
            initialPanel.forEach((entityTypeId, index) => {
                if (index < 10) {
                    this.slots[index] = entityTypeId;
                }
            });
        }
    }

    /**
     * Refresh all slot visuals
     */
    refresh() {
        for (let i = 0; i < 10; i++) {
            this.updateSlotVisual(i);
        }
    }
}

export default BuildPanel;
