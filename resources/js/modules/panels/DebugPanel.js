import { BasePanel } from './BasePanel.js';

/**
 * DebugPanel - displays camera position, FPS, and game stats (top-left corner)
 * Previously known as CameraInfo
 */
export class DebugPanel extends BasePanel {
    constructor(game) {
        super(game);
        this.cameraEl = null;
        this.tilesEl = null;
        this.entitiesEl = null;
        this.fpsEl = null;
        this.modeEl = null;
        this.lastFrameTime = performance.now();
        this.fps = 60;
    }

    /**
     * Initialize debug panel display
     */
    init() {
        this.element = document.getElementById('debug-info');
        if (!this.element) {
            console.warn('DebugPanel: #debug-info element not found');
            return;
        }

        this.cameraEl = document.getElementById('debug-camera');
        this.tilesEl = document.getElementById('debug-tiles');
        this.entitiesEl = document.getElementById('debug-entities');
        this.fpsEl = document.getElementById('debug-fps');
        this.modeEl = document.getElementById('debug-mode');
    }

    /**
     * Update debug panel display (called each frame)
     */
    update() {
        if (!this.element) return;

        // Update camera position
        if (this.cameraEl && this.game.camera) {
            const x = Math.round(this.game.camera.x);
            const y = Math.round(this.game.camera.y);
            const zoom = this.game.zoom.toFixed(2);
            this.cameraEl.textContent = `${x}, ${y} (${zoom}x)`;
        }

        // Update tiles count
        if (this.tilesEl) {
            this.tilesEl.textContent = this.game.loadedTiles?.size || 0;
        }

        // Update entities count
        if (this.entitiesEl) {
            this.entitiesEl.textContent = this.game.loadedEntities?.size || 0;
        }

        // Update FPS
        if (this.fpsEl) {
            const now = performance.now();
            const delta = now - this.lastFrameTime;
            this.lastFrameTime = now;

            // Smooth FPS calculation
            const currentFps = 1000 / delta;
            this.fps = this.fps * 0.9 + currentFps * 0.1;
            this.fpsEl.textContent = Math.round(this.fps);
        }

        // Update game mode
        if (this.modeEl && this.game.gameModeManager) {
            const mode = this.game.gameModeManager.currentMode;
            const modeNames = {
                'NORMAL': 'Normal',
                'BUILD': 'Build',
                'DELETE': 'Delete',
                'ENTITY_INFO': 'Entity Info',
                'ENTITY_SELECTION_WINDOW': 'Building Selection',
                'LANDING_SELECTION_WINDOW': 'Landing Selection',
                'LANDING_EDIT': 'Landing Edit'
            };
            this.modeEl.textContent = modeNames[mode] || mode;
        }
    }

    /**
     * Show/hide debug panel
     */
    setVisible(visible) {
        this.isVisible = visible;
        if (this.element) {
            this.element.style.display = visible ? 'block' : 'none';
        }
    }
}

// Alias for backwards compatibility
export { DebugPanel as CameraInfo };
export default DebugPanel;
