/**
 * BasePanel - base class for all UI panels
 * Provides common interface for panel lifecycle management
 */
export class BasePanel {
    /**
     * @param {object} game - Game instance reference
     */
    constructor(game) {
        this.game = game;
        this.element = null;
        this.isVisible = true;
    }

    /**
     * Initialize panel - create DOM elements and setup events
     * Must be called after game is ready
     */
    init() {
        this.createElement();
    }

    /**
     * Create panel DOM element
     * Override in child classes
     */
    createElement() {
        // Override in child classes
    }

    /**
     * Update panel content
     * Called each frame for panels that need continuous updates
     */
    update() {
        // Override in child classes if needed
    }

    /**
     * Refresh panel content
     * Called when data changes and panel needs to re-render
     */
    refresh() {
        // Override in child classes if needed
    }

    /**
     * Show or hide panel
     * @param {boolean} visible
     */
    setVisible(visible) {
        this.isVisible = visible;
        if (this.element) {
            this.element.style.display = visible ? '' : 'none';
        }
    }

    /**
     * Check if panel is visible
     * @returns {boolean}
     */
    getVisible() {
        return this.isVisible;
    }

    /**
     * Destroy panel - cleanup DOM and events
     */
    destroy() {
        if (this.element && this.element.parentNode) {
            this.element.parentNode.removeChild(this.element);
        }
        this.element = null;
    }
}

export default BasePanel;
