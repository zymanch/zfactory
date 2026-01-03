import { GameMode } from '../modes/gameModeManager.js';

/**
 * DepositWindow - modal window for selecting deposit types (admin tool)
 * Features:
 * - Tabs for deposit categories (trees, rocks, ores)
 * - Grid of deposit type icons
 * - Range slider for resource amount (min-max)
 * - Different defaults per category
 */
export class DepositWindow {
    constructor(game) {
        this.game = game;
        this.isOpen = false;
        this.element = null;
        this.selectedDeposit = null;
        this.minAmount = 10;
        this.maxAmount = 30;
        this.currentTab = 'tree';
    }

    /**
     * Initialize window UI
     */
    init() {
        this.createElement();
        this.bindEvents();
    }

    /**
     * Create window HTML element
     */
    createElement() {
        this.element = document.createElement('div');
        this.element.id = 'deposit-window';
        this.element.className = 'game-window';
        this.element.style.display = 'none';

        this.element.innerHTML = `
            <div class="window-header">
                <span class="window-title">Select Deposit Type</span>
                <button class="window-close">&times;</button>
            </div>
            <div class="deposit-tabs">
                <button class="tab-btn active" data-type="tree">Trees</button>
                <button class="tab-btn" data-type="rock">Rocks</button>
                <button class="tab-btn" data-type="ore">Ores</button>
            </div>
            <div class="window-content">
                <div class="deposit-grid" id="deposit-grid"></div>
                <div class="amount-slider">
                    <label>Resource Amount Range:</label>
                    <div class="range-values">
                        <span id="amount-min">10</span> - <span id="amount-max">30</span>
                    </div>
                    <div class="slider-container">
                        <label class="slider-label">Min:</label>
                        <input type="range" id="amount-min-slider" min="0" max="1000" value="10">
                    </div>
                    <div class="slider-container">
                        <label class="slider-label">Max:</label>
                        <input type="range" id="amount-max-slider" min="0" max="1000" value="30">
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(this.element);
        this.element.addEventListener('click', (e) => e.stopPropagation());
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Close button
        this.element.querySelector('.window-close').addEventListener('click', () => {
            this.close();
        });

        // Tab switching
        this.element.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const type = e.target.dataset.type;
                this.switchTab(type);
            });
        });

        // Amount sliders
        this.initSliders();

        // Deposit selection (delegated)
        this.element.querySelector('#deposit-grid').addEventListener('click', (e) => {
            const item = e.target.closest('.deposit-item');
            if (!item) return;

            const depositTypeId = parseInt(item.dataset.depositId);
            this.selectDeposit(depositTypeId);
        });
    }

    /**
     * Initialize amount range sliders
     */
    initSliders() {
        const minSlider = this.element.querySelector('#amount-min-slider');
        const maxSlider = this.element.querySelector('#amount-max-slider');
        const minLabel = this.element.querySelector('#amount-min');
        const maxLabel = this.element.querySelector('#amount-max');

        minSlider.addEventListener('input', (e) => {
            let value = parseInt(e.target.value);
            if (value > this.maxAmount) {
                value = this.maxAmount;
                e.target.value = value;
            }
            this.minAmount = value;
            minLabel.textContent = value;
        });

        maxSlider.addEventListener('input', (e) => {
            let value = parseInt(e.target.value);
            if (value < this.minAmount) {
                value = this.minAmount;
                e.target.value = value;
            }
            this.maxAmount = value;
            maxLabel.textContent = value;
        });
    }

    /**
     * Switch between deposit type tabs
     */
    switchTab(type) {
        this.currentTab = type;

        // Update active tab button
        this.element.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.type === type);
        });

        // Set default amounts based on type
        if (type === 'ore') {
            this.setAmountRange(50, 100);
        } else {
            this.setAmountRange(10, 30);
        }

        // Render deposits for this type
        this.populateGrid(type);
    }

    /**
     * Set amount range sliders to specific values
     */
    setAmountRange(min, max) {
        this.minAmount = min;
        this.maxAmount = max;

        this.element.querySelector('#amount-min-slider').value = min;
        this.element.querySelector('#amount-max-slider').value = max;
        this.element.querySelector('#amount-min').textContent = min;
        this.element.querySelector('#amount-max').textContent = max;
    }

    /**
     * Populate grid with deposits of a specific type
     */
    populateGrid(type) {
        const grid = this.element.querySelector('#deposit-grid');
        grid.innerHTML = '';

        const depositTypes = Object.values(this.game.depositTypes);
        const filtered = depositTypes.filter(d => d.type === type);

        const v = this.game.config.assetVersion || 1;

        filtered.forEach(deposit => {
            const item = this.createDepositItem(deposit, v);
            grid.appendChild(item);
        });
    }

    /**
     * Create deposit item element
     */
    createDepositItem(deposit, assetVersion) {
        const iconUrl = `${this.game.config.tilesPath}deposits/${deposit.image_url}/normal.png?v=${assetVersion}`;

        const item = document.createElement('div');
        item.className = 'deposit-item';
        item.dataset.depositId = deposit.deposit_type_id;

        item.innerHTML = `
            <img src="${iconUrl}" class="deposit-icon" alt="${deposit.name}">
            <div class="deposit-name">${deposit.name}</div>
        `;

        return item;
    }

    /**
     * Select a deposit type and enter build mode
     */
    selectDeposit(depositTypeId) {
        this.selectedDeposit = this.game.depositTypes[depositTypeId];
        if (!this.selectedDeposit) return;

        // Enter deposit build mode (switchMode will close this window via deactivateDepositSelectionWindow)
        this.game.gameModeManager.switchMode(GameMode.DEPOSIT_BUILD, {
            depositType: this.selectedDeposit,
            minAmount: this.minAmount,
            maxAmount: this.maxAmount
        });
    }

    /**
     * Open window
     */
    open() {
        this.element.style.display = 'block';
        this.isOpen = true;

        // Populate grid with current tab
        this.populateGrid(this.currentTab);

        this.game.gameModeManager.switchMode(GameMode.DEPOSIT_SELECTION_WINDOW);
    }

    /**
     * Close window
     */
    close() {
        // Prevent recursion - only close if actually open
        if (!this.isOpen) return;

        this.element.style.display = 'none';
        this.isOpen = false;

        // Only return to normal mode if we're in DEPOSIT_SELECTION_WINDOW mode
        // (avoid recursion with gameModeManager.deactivateDepositSelectionWindow)
        if (this.game.gameModeManager.isMode(GameMode.DEPOSIT_SELECTION_WINDOW)) {
            this.game.gameModeManager.returnToNormalMode();
        }
    }

    /**
     * Toggle window open/closed
     */
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
}
