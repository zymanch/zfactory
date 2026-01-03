/**
 * BaseTooltip - base class for all tooltips
 * Provides common interface for tooltip lifecycle and positioning
 */
export class BaseTooltip {
    /**
     * @param {object} game - Game instance reference
     */
    constructor(game) {
        this.game = game;
        this.element = null;
        this.isVisible = false;
        this.hideTimeout = null;
    }

    /**
     * Initialize tooltip - create DOM element
     */
    init() {
        this.createElement();
    }

    /**
     * Create tooltip DOM element
     * Override in child classes
     */
    createElement() {
        this.element = document.createElement('div');
        this.element.style.cssText = `
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
            box-shadow: 0 2px 8px rgba(0,0,0,0.5);
        `;
        document.body.appendChild(this.element);
    }

    /**
     * Show tooltip at position
     * @param {any} data - Data to display
     * @param {number} x - Screen X position
     * @param {number} y - Screen Y position
     */
    show(data, x, y) {
        if (this.hideTimeout) {
            clearTimeout(this.hideTimeout);
            this.hideTimeout = null;
        }

        this.renderContent(data);
        this.element.style.display = 'block';
        this.isVisible = true;
        this.updatePosition(x, y);
    }

    /**
     * Render tooltip content
     * Override in child classes
     * @param {any} data - Data to display
     */
    renderContent(data) {
        // Override in child classes
    }

    /**
     * Update tooltip position (smart positioning to stay in viewport)
     * @param {number} x - Screen X position
     * @param {number} y - Screen Y position
     */
    updatePosition(x, y) {
        if (!this.element || !this.isVisible) return;

        const rect = this.element.getBoundingClientRect();
        const padding = 15;

        let posX = x + padding;
        let posY = y + padding;

        // Keep within viewport
        if (posX + rect.width > window.innerWidth) {
            posX = x - rect.width - padding;
        }
        if (posY + rect.height > window.innerHeight) {
            posY = y - rect.height - padding;
        }

        // Ensure not negative
        posX = Math.max(0, posX);
        posY = Math.max(0, posY);

        this.element.style.left = posX + 'px';
        this.element.style.top = posY + 'px';
    }

    /**
     * Hide tooltip (with optional delay)
     * @param {number} delay - Delay in ms before hiding (default 0)
     */
    hide(delay = 0) {
        if (delay > 0) {
            this.hideTimeout = setTimeout(() => {
                this._hideImmediate();
            }, delay);
        } else {
            this._hideImmediate();
        }
    }

    /**
     * Hide tooltip immediately
     * @private
     */
    _hideImmediate() {
        if (this.element) {
            this.element.style.display = 'none';
        }
        this.isVisible = false;
    }

    /**
     * Check if tooltip is currently visible
     * @returns {boolean}
     */
    getVisible() {
        return this.isVisible;
    }

    /**
     * Destroy tooltip - cleanup DOM
     */
    destroy() {
        this.hide();
        if (this.element && this.element.parentNode) {
            this.element.parentNode.removeChild(this.element);
        }
        this.element = null;
    }
}

export default BaseTooltip;
