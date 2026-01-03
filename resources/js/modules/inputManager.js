import { screenToWorld, worldToTile } from './utils.js';
import { GameMode } from './modes/gameModeManager.js';

/**
 * InputManager - handles keyboard and mouse input
 */
export class InputManager {
    constructor(game) {
        this.game = game;
        this.keys = {};
        this.mousePosition = { x: 0, y: 0 };
        this.callbacks = {
            keydown: {},
            keyup: {}
        };

        // Movement keys (EN and RU layouts)
        this.movementKeys = ['w', 'a', 's', 'd', 'ц', 'ф', 'ы', 'в', 'arrowup', 'arrowdown', 'arrowleft', 'arrowright'];
    }

    /**
     * Initialize input handlers
     */
    init() {
        window.addEventListener('keydown', (e) => this.onKeyDown(e));
        window.addEventListener('keyup', (e) => this.onKeyUp(e));
        window.addEventListener('mousemove', (e) => this.onMouseMove(e));
        window.addEventListener('resize', () => this.game.needsReload = true);

        this.game.app.canvas.addEventListener('wheel', (e) => {
            e.preventDefault();
            this.game.camera.handleZoom(e.deltaY);
        }, { passive: false });
    }

    /**
     * Handle key down events
     */
    onKeyDown(e) {
        const key = e.key.toLowerCase();
        this.keys[key] = true;

        if (this.movementKeys.includes(key)) {
            e.preventDefault();
        }

        this.fireCallback('keydown', key, e);
        this.handleHotkeys(key);
    }

    /**
     * Handle hotkey actions
     */
    handleHotkeys(key) {
        const mode = this.game.gameModeManager;

        // Number keys 1-0 for build panel slots (only in NORMAL or BUILD mode)
        if (/^[0-9]$/.test(key)) {
            if (mode.isMode(GameMode.NORMAL) || mode.isMode(GameMode.BUILD)) {
                const slot = key === '0' ? 9 : parseInt(key) - 1;
                this.game.buildPanel?.activateSlot(slot);
            }
        }

        // B/И key - toggle building window
        if (key === 'b' || key === 'и') {
            if (mode.isMode(GameMode.ENTITY_SELECTION_WINDOW)) {
                // Close window, return to normal mode
                mode.returnToNormalMode();
            } else if (mode.isMode(GameMode.NORMAL)) {
                // Open window
                mode.switchMode(GameMode.ENTITY_SELECTION_WINDOW);
            }
        }

        // Delete key - toggle DELETE mode
        if (key === 'delete') {
            if (mode.isMode(GameMode.DELETE)) {
                // Exit delete mode, return to normal
                mode.returnToNormalMode();
            } else if (mode.isMode(GameMode.NORMAL)) {
                // Enter delete mode
                mode.switchMode(GameMode.DELETE);
            }
        }

        // Escape - close windows / cancel modes
        if (key === 'escape') {
            // Return to normal mode or previous mode
            if (!mode.isMode(GameMode.NORMAL)) {
                if (mode.isMode(GameMode.ENTITY_INFO)) {
                    // Close entity info, return to normal
                    mode.returnToNormalMode();
                } else if (mode.isMode(GameMode.BUILD)) {
                    // Cancel build mode, return to normal
                    mode.returnToNormalMode();
                } else if (mode.isMode(GameMode.DELETE)) {
                    // Cancel delete mode, return to normal
                    mode.returnToNormalMode();
                } else {
                    // For windows and other modes, return to previous mode or normal
                    mode.returnToPreviousMode();
                }
            }
        }

        // F key - toggle fog of war (EN/RU) - works in any mode
        if (key === 'f' || key === 'а') {
            this.game.fogOfWar?.toggle();
        }
    }

    /**
     * Handle key up events
     */
    onKeyUp(e) {
        const key = e.key.toLowerCase();
        this.keys[key] = false;
        this.fireCallback('keyup', key, e);
    }

    /**
     * Fire registered callback
     */
    fireCallback(event, key, e) {
        if (this.callbacks[event][key]) {
            this.callbacks[event][key](e);
        }
    }

    /**
     * Handle mouse move
     */
    onMouseMove(e) {
        this.mousePosition.x = e.clientX;
        this.mousePosition.y = e.clientY;

        if (this.game.buildMode?.isActive) {
            this.game.buildMode.updatePreview(e.clientX, e.clientY);
        }
    }

    /**
     * Check if key is pressed
     */
    isKeyPressed(key) {
        return !!this.keys[key.toLowerCase()];
    }

    /**
     * Register callback for key event
     */
    on(event, key, callback) {
        if (this.callbacks[event]) {
            this.callbacks[event][key.toLowerCase()] = callback;
        }
    }

    /**
     * Get world coordinates from screen position
     */
    screenToWorld(screenX, screenY) {
        return screenToWorld(screenX, screenY, this.game.camera, this.game.zoom);
    }

    /**
     * Get tile coordinates from screen position
     */
    screenToTile(screenX, screenY) {
        const world = this.screenToWorld(screenX, screenY);
        return worldToTile(world.x, world.y, this.game.config.tileWidth, this.game.config.tileHeight);
    }
}

export default InputManager;
