import { GameModeBase } from './gameModeBase.js';

/**
 * NormalMode - Default game mode
 *
 * Features:
 * - Entity hover interactivity enabled (tooltips, selected sprites)
 * - Click entities to open info window
 * - Normal camera and input controls
 */
export class NormalMode extends GameModeBase {
    constructor(game) {
        super(game);
    }

    /**
     * Activate normal mode
     */
    onActivate() {
        // Enable entity hover interactivity (tooltip, selected sprites)
        if (this.game.gameModeManager) {
            this.game.gameModeManager.setEntityInteractivity(true);
        }
    }

    /**
     * Deactivate normal mode
     */
    onDeactivate() {
        // Hide tooltip if it was open
        if (this.game.entityTooltip) {
            this.game.entityTooltip.hide();
        }
    }
}

export default NormalMode;
