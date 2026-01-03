import ZFactoryGame from './game.js';
import { LandingWindow } from './modules/windows/landingWindow.js';
import { LandingEditMode } from './modules/modes/landingEditMode.js';
import { DepositWindow } from './modules/admin/depositWindow.js';
import { DepositBuildMode } from './modules/admin/depositBuildMode.js';

/**
 * Admin Map Editor
 * Extends the main game engine with admin-specific functionality:
 * - Landing editing (moved from main game)
 * - Deposit placement with type selection and resource amounts
 */
class AdminMapEditor extends ZFactoryGame {
    constructor() {
        // Use the admin config URL
        super('/game/config');
        this.regionId = window.REGION_ID;
        this.regionName = window.REGION_NAME;
    }

    async init() {
        await super.init();
        this.initAdminModules();
        this.initSpriteCoords();
    }

    initAdminModules() {
        // Re-add landing functionality (removed from main game)
        this.landingWindow = new LandingWindow(this);
        this.landingEditMode = new LandingEditMode(this);

        // Add deposit functionality
        this.depositWindow = new DepositWindow(this);
        this.depositBuildMode = new DepositBuildMode(this);

        // Initialize modules
        this.landingWindow.init();
        this.landingEditMode.init();
        this.depositWindow.init();
        this.depositBuildMode.init();

        // Override config URLs for admin endpoints
        this.config.updateLandingUrl = '/admin/update-landing';
        this.config.createDepositUrl = '/admin/create-deposit';
    }

    initSpriteCoords() {
        const coordsEl = document.getElementById('sprite-coords');
        if (!coordsEl) return;

        this.app.stage.on('pointermove', (event) => {
            const worldPos = this.inputManager.screenToWorld(event.global.x, event.global.y);
            const tileX = Math.floor(worldPos.x / this.config.tileWidth);
            const tileY = Math.floor(worldPos.y / this.config.tileHeight);
            coordsEl.textContent = `X: ${tileX}, Y: ${tileY}`;
        });
    }
}

// Initialize editor when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    const editor = new AdminMapEditor();
    editor.init().catch(console.error);
    window.game = editor;
});

export default AdminMapEditor;
