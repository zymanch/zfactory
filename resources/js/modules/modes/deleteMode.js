import { GameModeBase } from './gameModeBase.js';

/**
 * DeleteMode - Entity deletion mode
 *
 * Features:
 * - Visual indicator showing delete mode is active
 * - Crosshair cursor
 * - Entity hover shows 'deleting' sprite state
 * - Click entity to delete (handled by GameModeManager canClickEntity)
 */
export class DeleteMode extends GameModeBase {
    constructor(game) {
        super(game);
        this.indicatorElement = null;
    }

    /**
     * Initialize mode (one-time setup)
     */
    init() {
        this.createDeleteIndicator();
    }

    /**
     * Create delete mode indicator DOM element
     */
    createDeleteIndicator() {
        this.indicatorElement = document.createElement('div');
        this.indicatorElement.id = 'delete-mode-indicator';
        this.indicatorElement.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(180, 0, 0, 0.9);
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            z-index: 9999;
            pointer-events: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
            display: none;
        `;
        this.indicatorElement.textContent = 'Режим удаления (Esc - выход)';
        document.body.appendChild(this.indicatorElement);
    }

    /**
     * Activate delete mode
     */
    onActivate() {
        // Enable entity hover interactivity (for tooltip and 'deleting' sprite)
        if (this.game.gameModeManager) {
            this.game.gameModeManager.setEntityInteractivity(true);
        }

        // Show delete mode indicator
        this.showDeleteModeIndicator();

        // Change cursor to crosshair
        this.game.app.canvas.style.cursor = 'crosshair';
    }

    /**
     * Deactivate delete mode
     */
    onDeactivate() {
        // Hide indicator
        this.hideDeleteModeIndicator();

        // Reset cursor
        this.game.app.canvas.style.cursor = 'default';

        // Hide tooltip
        if (this.game.entityTooltip) {
            this.game.entityTooltip.hide();
        }
    }

    /**
     * Show delete mode indicator
     */
    showDeleteModeIndicator() {
        if (this.indicatorElement) {
            this.indicatorElement.style.display = 'block';
        }
    }

    /**
     * Hide delete mode indicator
     */
    hideDeleteModeIndicator() {
        if (this.indicatorElement) {
            this.indicatorElement.style.display = 'none';
        }
    }
}

export default DeleteMode;
