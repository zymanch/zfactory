/**
 * Manages tooltips for deposits (trees, rocks, ores)
 * Simplified compared to entity tooltips - only shows name, resource icon, and amount
 */
export class DepositTooltip {
    constructor(game) {
        this.game = game;
        this.tooltipElement = null;
        this.currentDeposit = null;
        this.isVisible = false;
    }

    /**
     * Initialize tooltip
     */
    init() {
        this.tooltipElement = document.getElementById('deposit-tooltip');
        if (!this.tooltipElement) {
            this.createTooltipElement();
        }
    }

    /**
     * Create tooltip DOM element
     */
    createTooltipElement() {
        this.tooltipElement = document.createElement('div');
        this.tooltipElement.id = 'deposit-tooltip';
        this.tooltipElement.className = 'game-tooltip deposit-tooltip';
        this.tooltipElement.style.position = 'absolute';
        this.tooltipElement.style.display = 'none';
        this.tooltipElement.style.pointerEvents = 'none';
        this.tooltipElement.style.zIndex = '1000';
        document.body.appendChild(this.tooltipElement);
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

        this.tooltipElement.innerHTML = html;
        this.updatePosition(mouseX, mouseY);
        this.tooltipElement.style.display = 'block';
        this.isVisible = true;
    }

    /**
     * Hide tooltip
     */
    hide() {
        if (this.tooltipElement) {
            this.tooltipElement.style.display = 'none';
        }
        this.currentDeposit = null;
        this.isVisible = false;
    }

    /**
     * Update tooltip position
     */
    updatePosition(mouseX, mouseY) {
        if (!this.isVisible || !this.tooltipElement) {
            return;
        }

        const offsetX = 15;
        const offsetY = 15;
        const padding = 10;

        let x = mouseX + offsetX;
        let y = mouseY + offsetY;

        // Get tooltip dimensions
        const rect = this.tooltipElement.getBoundingClientRect();
        const tooltipWidth = rect.width;
        const tooltipHeight = rect.height;

        // Keep tooltip within window bounds
        if (x + tooltipWidth + padding > window.innerWidth) {
            x = mouseX - tooltipWidth - offsetX;
        }

        if (y + tooltipHeight + padding > window.innerHeight) {
            y = mouseY - tooltipHeight - offsetY;
        }

        this.tooltipElement.style.left = `${x}px`;
        this.tooltipElement.style.top = `${y}px`;
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

    /**
     * Destroy tooltip
     */
    destroy() {
        this.hide();
        if (this.tooltipElement && this.tooltipElement.parentNode) {
            this.tooltipElement.parentNode.removeChild(this.tooltipElement);
        }
        this.tooltipElement = null;
    }
}
