import { LANDING_ISLAND_EDGE_ID, LANDING_SKY_ID } from './constants.js';

/**
 * LandingWindow - modal window for selecting landing types (admin tool)
 */
export class LandingWindow {
    constructor(game) {
        this.game = game;
        this.isOpen = false;
        this.element = null;
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
        this.element.id = 'landing-window';
        this.element.className = 'game-window';
        this.element.style.display = 'none';

        this.element.innerHTML = `
            <div class="window-header">
                <span class="window-title">Ландшафты</span>
                <button class="window-close">&times;</button>
            </div>
            <div class="window-content">
                <div class="landing-grid"></div>
            </div>
        `;

        document.body.appendChild(this.element);

        this.element.querySelector('.window-close').addEventListener('click', () => this.close());
        this.element.addEventListener('click', (e) => e.stopPropagation());
    }

    /**
     * Populate grid with landing types
     */
    populateGrid() {
        const grid = this.element.querySelector('.landing-grid');
        grid.innerHTML = '';

        const v = this.game.config.assetVersion || 1;

        for (const landingId in this.game.landingTypes) {
            const id = parseInt(landingId);

            // Skip island_edge - it's auto-generated
            if (id === LANDING_ISLAND_EDGE_ID) continue;

            const landing = this.game.landingTypes[landingId];
            const item = this.createLandingItem(id, landing, v);
            grid.appendChild(item);
        }
    }

    /**
     * Create landing item element
     */
    createLandingItem(landingId, landing, assetVersion) {
        const iconUrl = `${this.game.config.tilesPath}landing/${landing.folder}.png?v=${assetVersion}`;

        const item = document.createElement('div');
        item.className = 'landing-item';
        item.dataset.landingId = landingId;

        // Special label for sky (delete action)
        const isSky = landingId === LANDING_SKY_ID;
        const displayName = isSky ? `${landing.name} (удалить)` : landing.name;

        item.innerHTML = `
            <div class="landing-icon" style="background-image: url('${iconUrl}')"></div>
            <div class="landing-name">${displayName}</div>
        `;

        item.addEventListener('click', () => {
            this.selectLanding(landingId);
        });

        return item;
    }

    /**
     * Select landing and enter edit mode
     */
    selectLanding(landingId) {
        this.close();
        this.game.landingEditMode?.activate(landingId);
    }

    /**
     * Open window
     */
    open() {
        this.populateGrid();
        this.element.style.display = 'flex';
        this.isOpen = true;
    }

    /**
     * Close window
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

export default LandingWindow;
