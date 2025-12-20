import { screenToWorld, worldToScreen } from './utils.js';
import { CAMERA_SAVE_DELAY } from './constants.js';

/**
 * Camera - handles camera movement and zoom
 */
export class Camera {
    constructor(game) {
        this.game = game;
        this.x = 0;
        this.y = 0;
        this.minZoom = 1;
        this.maxZoom = 3;
        this.saveTimeout = null;
    }

    /**
     * Set initial position from server config
     */
    setInitialPosition(x, y, zoom) {
        this.x = x;
        this.y = y;
        if (zoom >= this.minZoom && zoom <= this.maxZoom) {
            this.game.zoom = zoom;
            this.game.worldContainer.scale.set(zoom);
        }
    }

    /**
     * Schedule position save with debounce
     */
    scheduleSave() {
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
        this.saveTimeout = setTimeout(() => this.savePosition(), CAMERA_SAVE_DELAY);
    }

    /**
     * Save position to server
     */
    async savePosition() {
        const url = this.game.config.savePositionUrl;
        if (!url) return;

        try {
            await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    x: Math.round(this.x),
                    y: Math.round(this.y),
                    zoom: this.game.zoom
                })
            });
        } catch (e) {
            console.warn('Failed to save camera position:', e);
        }
    }

    /**
     * Update camera position based on input
     * @returns {boolean} Whether camera moved
     */
    update() {
        const input = this.game.input;
        const speed = this.game.config.cameraSpeed / this.game.zoom;
        let moved = false;

        // WASD movement (EN and RU layouts)
        if (this.isMovingUp(input)) { this.y -= speed; moved = true; }
        if (this.isMovingDown(input)) { this.y += speed; moved = true; }
        if (this.isMovingLeft(input)) { this.x -= speed; moved = true; }
        if (this.isMovingRight(input)) { this.x += speed; moved = true; }

        if (moved) {
            this.scheduleSave();
        }

        return moved;
    }

    isMovingUp(input) {
        return input.isKeyPressed('w') || input.isKeyPressed('ц') || input.isKeyPressed('arrowup');
    }

    isMovingDown(input) {
        return input.isKeyPressed('s') || input.isKeyPressed('ы') || input.isKeyPressed('arrowdown');
    }

    isMovingLeft(input) {
        return input.isKeyPressed('a') || input.isKeyPressed('ф') || input.isKeyPressed('arrowleft');
    }

    isMovingRight(input) {
        return input.isKeyPressed('d') || input.isKeyPressed('в') || input.isKeyPressed('arrowright');
    }

    /**
     * Apply camera transform to world container
     */
    apply() {
        this.game.worldContainer.x = -this.x * this.game.zoom;
        this.game.worldContainer.y = -this.y * this.game.zoom;
    }

    /**
     * Handle zoom from mouse wheel
     */
    handleZoom(deltaY) {
        const zoomDelta = deltaY > 0 ? 0.9 : 1.1;
        const oldZoom = this.game.zoom;
        const newZoom = Math.max(this.minZoom, Math.min(this.maxZoom, this.game.zoom * zoomDelta));

        if (oldZoom !== newZoom) {
            // Calculate world position at screen center before zoom
            const centerX = this.x + (window.innerWidth / 2) / oldZoom;
            const centerY = this.y + (window.innerHeight / 2) / oldZoom;

            // Update zoom
            this.game.zoom = newZoom;
            this.game.worldContainer.scale.set(this.game.zoom);

            // Adjust camera to keep screen center at same world position
            this.x = centerX - (window.innerWidth / 2) / newZoom;
            this.y = centerY - (window.innerHeight / 2) / newZoom;

            this.game.needsReload = true;
            this.scheduleSave();
        }
    }

    /**
     * Get visible viewport bounds in world coordinates
     */
    getViewportBounds() {
        const zoom = this.game.zoom;
        return {
            left: this.x,
            top: this.y,
            right: this.x + window.innerWidth / zoom,
            bottom: this.y + window.innerHeight / zoom,
            width: window.innerWidth / zoom,
            height: window.innerHeight / zoom
        };
    }

    /**
     * Convert screen coordinates to world coordinates
     */
    screenToWorld(screenX, screenY) {
        return screenToWorld(screenX, screenY, this, this.game.zoom);
    }

    /**
     * Convert world coordinates to screen coordinates
     */
    worldToScreen(worldX, worldY) {
        return worldToScreen(worldX, worldY, this, this.game.zoom);
    }
}

export default Camera;
