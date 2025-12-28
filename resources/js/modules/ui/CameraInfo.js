/**
 * CameraInfo - displays camera position and game stats (top-left corner)
 */
export class CameraInfo {
    constructor(game) {
        this.game = game;
        this.element = null;
        this.cameraEl = null;
        this.tilesEl = null;
        this.entitiesEl = null;
        this.fpsEl = null;
        this.modeEl = null;
        this.lastFrameTime = performance.now();
        this.fps = 60;
    }

    /**
     * Initialize camera info display
     */
    init() {
        this.element = document.getElementById('debug-info');
        if (!this.element) {
            console.warn('CameraInfo: #debug-info element not found');
            return;
        }

        this.cameraEl = document.getElementById('debug-camera');
        this.tilesEl = document.getElementById('debug-tiles');
        this.entitiesEl = document.getElementById('debug-entities');
        this.fpsEl = document.getElementById('debug-fps');
        this.modeEl = document.getElementById('debug-mode');
    }

    /**
     * Update camera info display
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
     * Show/hide camera info
     */
    setVisible(visible) {
        if (this.element) {
            this.element.style.display = visible ? 'block' : 'none';
        }
    }
}

export default CameraInfo;
