import * as PIXI from 'pixi.js';
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
        // Use the admin config URL with region_id parameter
        const regionId = window.REGION_ID || 1;
        super(`/admin/config?region_id=${regionId}`);
        this.regionId = regionId;
        this.regionName = window.REGION_NAME;
    }

    async init() {
        await super.init();
        this.initAdminModules();
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

    /**
     * Override initModulesPost to hide admin-unnecessary UI and setup sprite coords
     */
    async initModulesPost() {
        await super.initModulesPost();

        // Hide ResourcePanel in admin mode (no player resources needed)
        if (this.resourcePanel?.element) {
            this.resourcePanel.element.style.display = 'none';
        }

        // Hide CameraInfo debug panel (we have sprite-coords instead)
        const debugInfo = document.getElementById('debug-info');
        if (debugInfo) {
            debugInfo.style.display = 'none';
        }

        // Hide ControlsHint (different controls in admin)
        const controlsHint = document.getElementById('controls-hint');
        if (controlsHint) {
            controlsHint.style.display = 'none';
        }

        // Initialize sprite coordinates display
        this.initSpriteCoords();

        // Draw ship attachment area marker
        this.drawShipAreaMarker();

        // Initialize keyboard shortcuts
        this.initKeyboardShortcuts();
    }

    /**
     * Initialize keyboard shortcuts for admin tools
     */
    initKeyboardShortcuts() {
        window.addEventListener('keydown', (e) => {
            // Ignore if typing in input fields
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }

            const key = e.key.toLowerCase();

            switch (key) {
                case 'l':
                    // Toggle landing window
                    this.landingWindow.toggle();
                    e.preventDefault();
                    break;

                case 'd':
                    // Toggle deposit window
                    this.depositWindow.toggle();
                    e.preventDefault();
                    break;

                case 'escape':
                    // Close all windows / return to normal mode
                    if (this.landingWindow.isOpen) {
                        this.landingWindow.close();
                    }
                    if (this.depositWindow.isOpen) {
                        this.depositWindow.close();
                    }
                    if (this.landingEditMode.isActive) {
                        this.landingEditMode.deactivate();
                    }
                    if (this.depositBuildMode.isActive) {
                        this.depositBuildMode.deactivate();
                    }
                    e.preventDefault();
                    break;
            }
        });

        console.log('Admin keyboard shortcuts initialized: L (landings), D (deposits), ESC (cancel)');
    }

    drawShipAreaMarker() {
        // Get ship attach coordinates from gameData.region
        const region = this.gameData?.region;

        if (!region) {
            console.warn('Region data not found');
            return;
        }

        const shipAttachX = region.ship_attach_x;
        const shipAttachY = region.ship_attach_y;

        if (shipAttachX === undefined || shipAttachY === undefined) {
            console.warn('Ship attach coordinates not found in region data');
            return;
        }

        console.log(`Drawing ship area marker at (${shipAttachX}, ${shipAttachY})`);


        const { tileWidth, tileHeight } = this.config;

        // Create a container for ship area marker
        const shipMarker = new PIXI.Container();
        shipMarker.label = 'ship-area-marker';

        // Define ship area size (approximate, can be adjusted)
        const shipAreaWidth = 60; // tiles (3x larger)
        const shipAreaHeight = 45; // tiles (3x larger)

        // Draw semi-transparent rectangle
        const graphics = new PIXI.Graphics();

        // Background - red forbidden zone
        graphics.rect(
            shipAttachX * tileWidth,
            shipAttachY * tileHeight,
            shipAreaWidth * tileWidth,
            shipAreaHeight * tileHeight
        );
        graphics.fill({ color: 0xff0000, alpha: 0.4 });

        // Border
        graphics.rect(
            shipAttachX * tileWidth,
            shipAttachY * tileHeight,
            shipAreaWidth * tileWidth,
            shipAreaHeight * tileHeight
        );
        graphics.stroke({ color: 0xff0000, width: 3, alpha: 0.8 });

        // Draw attachment point marker (cross)
        const markerSize = tileWidth * 2;
        const centerX = shipAttachX * tileWidth;
        const centerY = shipAttachY * tileHeight;

        graphics.moveTo(centerX - markerSize, centerY);
        graphics.lineTo(centerX + markerSize, centerY);
        graphics.stroke({ color: 0xff0000, width: 4 });

        graphics.moveTo(centerX, centerY - markerSize);
        graphics.lineTo(centerX, centerY + markerSize);
        graphics.stroke({ color: 0xff0000, width: 4 });

        shipMarker.addChild(graphics);

        // Add text label
        const label = new PIXI.Text({
            text: `Ship Area\n(${shipAttachX}, ${shipAttachY})`,
            style: {
                fontFamily: 'Arial',
                fontSize: 24,
                fill: 0xffffff,
                stroke: { color: 0x000000, width: 4 },
                align: 'center'
            }
        });
        label.x = centerX;
        label.y = centerY - tileHeight * 3;
        label.anchor.set(0.5);

        shipMarker.addChild(label);

        // Add to world layer (above tiles but below entities)
        this.worldContainer.addChild(shipMarker);

        console.log(`Ship area marker drawn at (${shipAttachX}, ${shipAttachY})`);
    }

    initSpriteCoords() {
        const coordsEl = document.getElementById('sprite-coords');
        if (!coordsEl) {
            console.warn('sprite-coords element not found');
            return;
        }

        if (!this.app?.view) {
            console.warn('app.view (canvas) not ready');
            return;
        }

        if (!this.input) {
            console.warn('input manager not ready');
            return;
        }

        // Use native DOM event on canvas instead of PixiJS event
        const canvas = this.app.view;

        canvas.addEventListener('mousemove', (event) => {
            try {
                // Get mouse position relative to canvas
                const rect = canvas.getBoundingClientRect();
                const screenX = event.clientX - rect.left;
                const screenY = event.clientY - rect.top;

                // Convert to world coordinates
                const worldPos = this.input.screenToWorld(screenX, screenY);

                // Convert to tile coordinates
                const tileX = Math.floor(worldPos.x / this.config.tileWidth);
                const tileY = Math.floor(worldPos.y / this.config.tileHeight);

                coordsEl.textContent = `X: ${tileX}, Y: ${tileY}`;
            } catch (err) {
                console.error('Error updating coords:', err);
            }
        });

        console.log('Canvas mousemove event attached');
    }
}

// Initialize editor when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    const editor = new AdminMapEditor();
    editor.init().catch(console.error);
    window.game = editor;
});

export default AdminMapEditor;
