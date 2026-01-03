import { BaseTooltip } from './BaseTooltip.js';

/**
 * DepositTooltip - displays deposit info (trees, rocks, ores) on hover
 * Shows name, resource icon, and amount
 */
export class DepositTooltip extends BaseTooltip {
    constructor(game) {
        super(game);
        this.currentDeposit = null;
    }

    /**
     * Initialize tooltip
     */
    init() {
        this.element = document.getElementById('deposit-tooltip');
        if (!this.element) {
            this.createElement();
        }
    }

    /**
     * Create tooltip DOM element
     */
    createElement() {
        this.element = document.createElement('div');
        this.element.id = 'deposit-tooltip';
        this.element.className = 'game-tooltip deposit-tooltip';
        this.element.style.position = 'absolute';
        this.element.style.display = 'none';
        this.element.style.pointerEvents = 'none';
        this.element.style.zIndex = '1000';
        document.body.appendChild(this.element);
    }

    /**
     * Show tooltip for deposit
     */
    show(deposit, mouseX, mouseY) {
        if (!deposit) {
            this.hide();
            return;
        }

        this.currentDeposit = deposit;
        const depositType = this.game.depositTypes[deposit.deposit_type_id];

        if (!depositType) {
            this.hide();
            return;
        }

        const resource = this.game.resources[depositType.resource_id];
        if (!resource) {
            this.hide();
            return;
        }

        // Build tooltip HTML
        let html = `<div class="tooltip-header">${depositType.name}</div>`;
        html += `<div class="tooltip-body">`;
        html += `<div class="tooltip-row">`;
        html += `<img src="${this.game.config.tilesPath}resources/${resource.icon_url}" class="resource-icon" />`;
        html += `<span>${resource.name}: ${deposit.resource_amount}</span>`;
        html += `</div>`;
        html += `</div>`;

        this.element.innerHTML = html;
        this.updatePosition(mouseX, mouseY);
        this.element.style.display = 'block';
        this.isVisible = true;
    }

    /**
     * Hide tooltip
     */
    hide() {
        if (this.element) {
            this.element.style.display = 'none';
        }
        this.currentDeposit = null;
        this.isVisible = false;
    }

    /**
     * Update tooltip position
     */
    updatePosition(mouseX, mouseY) {
        if (!this.isVisible || !this.element) {
            return;
        }

        const offsetX = 15;
        const offsetY = 15;
        const padding = 10;

        let x = mouseX + offsetX;
        let y = mouseY + offsetY;

        // Get tooltip dimensions
        const rect = this.element.getBoundingClientRect();
        const tooltipWidth = rect.width;
        const tooltipHeight = rect.height;

        // Keep tooltip within window bounds
        if (x + tooltipWidth + padding > window.innerWidth) {
            x = mouseX - tooltipWidth - offsetX;
        }

        if (y + tooltipHeight + padding > window.innerHeight) {
            y = mouseY - tooltipHeight - offsetY;
        }

        this.element.style.left = `${x}px`;
        this.element.style.top = `${y}px`;
    }

    /**
     * Check if mouse is over a deposit
     */
    handleMouseMove(event) {
        const sprite = this.game.depositManager.getSpriteAt(event.clientX, event.clientY);

        if (sprite && sprite.depositData) {
            this.show(sprite.depositData, event.clientX, event.clientY);
        } else {
            this.hide();
        }
    }
}

export default DepositTooltip;
