import { BasePanel } from './BasePanel.js';
import { GameMode } from '../modes/gameModeManager.js';

/**
 * ControlsHint - displays keyboard shortcuts (bottom-left corner)
 * Shows different hints depending on current game mode
 */
export class ControlsHint extends BasePanel {
    constructor(game) {
        super(game);
        this.currentMode = null;
    }

    /**
     * Initialize controls hint display
     */
    init() {
        this.element = document.getElementById('controls-hint');
        if (!this.element) {
            console.warn('ControlsHint: #controls-hint element not found');
            return;
        }

        // Update hints on mode change
        this.update();
    }

    /**
     * Update hints based on current game mode
     */
    update() {
        if (!this.element) return;

        const mode = this.game.gameModeManager?.currentMode;

        // Only update if mode changed
        if (mode === this.currentMode) return;
        this.currentMode = mode;

        const hints = this.getHintsForMode(mode);
        this.element.innerHTML = hints.map(hint =>
            `<span class="hint-row">${hint}</span>`
        ).join('');
    }

    /**
     * Get hints for specific mode
     */
    getHintsForMode(mode) {
        // Common hints (always shown)
        const common = [
            '<kbd>W</kbd><kbd>A</kbd><kbd>S</kbd><kbd>D</kbd> move',
            '<kbd>Wheel</kbd> zoom'
        ];

        switch (mode) {
            case GameMode.NORMAL:
                return [
                    ...common,
                    '<kbd>B</kbd> buildings',
                    '<kbd>1</kbd>-<kbd>0</kbd> build',
                    '<kbd>Delete</kbd> delete mode',
                    '<kbd>Click</kbd> entity info'
                ];

            case GameMode.BUILD:
                return [
                    ...common,
                    '<kbd>R</kbd> rotate',
                    '<kbd>Click</kbd> place',
                    '<kbd>Esc</kbd> cancel'
                ];

            case GameMode.DELETE:
                return [
                    ...common,
                    '<kbd>Click</kbd> delete entity',
                    '<kbd>Delete</kbd> exit delete mode',
                    '<kbd>Esc</kbd> cancel'
                ];

            case GameMode.ENTITY_INFO:
                return [
                    ...common,
                    '<kbd>Esc</kbd> close window'
                ];

            case GameMode.ENTITY_SELECTION_WINDOW:
                return [
                    ...common,
                    '<kbd>Click</kbd> select',
                    '<kbd>Esc</kbd> close'
                ];

            default:
                return common;
        }
    }

    /**
     * Show/hide controls hint
     */
    setVisible(visible) {
        this.isVisible = visible;
        if (this.element) {
            this.element.style.display = visible ? 'block' : 'none';
        }
    }
}

export default ControlsHint;
