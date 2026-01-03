/**
 * GameModeBase - Base class for all game modes
 *
 * Provides common lifecycle management and event handling utilities.
 * All mode classes should extend this base class to ensure consistent behavior.
 *
 * Lifecycle:
 * 1. constructor(game) - Create mode instance
 * 2. init() - One-time initialization (setup DOM, register global handlers)
 * 3. activate(data) - Activate mode with optional data
 * 4. deactivate() - Deactivate mode and cleanup
 *
 * Event Management:
 * - Use addEventListener() to register events - they'll be auto-cleaned up
 * - Events are removed automatically when deactivate() is called
 * - Supports both DOM events and PIXI events
 */
export class GameModeBase {
    constructor(game) {
        this.game = game;
        this.isActive = false;
        this._eventListeners = []; // Track all registered event listeners
    }

    /**
     * Initialize mode (called once on game startup)
     * Override in subclasses for one-time setup
     */
    init() {
        // No-op by default - override in subclasses
    }

    /**
     * Activate mode
     * Override onActivate() in subclasses, not this method directly
     */
    activate(data = {}) {
        if (this.isActive) {
            console.warn(`Mode already active:`, this.constructor.name);
            return;
        }

        this.isActive = true;
        this.onActivate(data);
    }

    /**
     * Deactivate mode
     * Override onDeactivate() in subclasses, not this method directly
     */
    deactivate() {
        if (!this.isActive) {
            return;
        }

        this.unbindAllEvents(); // Auto-cleanup all registered events
        this.onDeactivate();
        this.isActive = false;
    }

    /**
     * Hook called when mode is activated
     * Override this in subclasses
     */
    onActivate(data) {
        // No-op - override in subclasses
    }

    /**
     * Hook called when mode is deactivated
     * Override this in subclasses
     */
    onDeactivate() {
        // No-op - override in subclasses
    }

    /**
     * Check if mode can be activated with given data
     * Override this to add validation
     */
    canActivate(data) {
        return true; // Allow by default
    }

    /**
     * Register an event listener that will be automatically removed on deactivate
     *
     * Supports both DOM events (addEventListener) and PIXI events (.on())
     *
     * @param {Object} target - DOM element, document, or PIXI object
     * @param {string} eventName - Event name (e.g., 'click', 'keydown', 'pointermove')
     * @param {Function} handler - Event handler function
     * @param {Object} options - Options for addEventListener (capture, once, passive)
     * @returns {Function} The bound handler (useful if you need to remove it manually)
     */
    addEventListener(target, eventName, handler, options = {}) {
        const boundHandler = handler.bind(this);

        // Store for cleanup
        this._eventListeners.push({
            target,
            eventName,
            handler: boundHandler,
            options
        });

        // Add listener (support both DOM and PIXI)
        if (target.addEventListener) {
            target.addEventListener(eventName, boundHandler, options);
        } else if (target.on) {
            // PIXI event system
            target.on(eventName, boundHandler);
        } else {
            console.warn('Target does not support event binding:', target);
        }

        return boundHandler;
    }

    /**
     * Remove all registered event listeners
     * Called automatically in deactivate()
     */
    unbindAllEvents() {
        for (const { target, eventName, handler, options } of this._eventListeners) {
            if (target.removeEventListener) {
                target.removeEventListener(eventName, handler, options);
            } else if (target.off) {
                // PIXI event system
                target.off(eventName, handler);
            }
        }

        this._eventListeners = [];
    }
}

export default GameModeBase;
