import { GameLoader } from './core/GameLoader.js';
import { GraphicsEngine } from './core/GraphicsEngine.js';
import ZFactoryGame from './game.js';
import { ErrorModal } from './modules/windows/ErrorModal.js';

/**
 * Bootstrap - Game initialization entry point
 *
 * Orchestrates the complete game startup sequence:
 * 1. Load data (config, entities, tiles)
 * 2. Initialize graphics engine and load textures
 * 3. Create and initialize game instance
 *
 * Shows loading progress to user during initialization.
 */

// Loading UI helpers
function showLoading(message) {
    const el = document.getElementById('loading-text');
    if (el) el.textContent = message;
}

function updateProgress(percent) {
    const bar = document.getElementById('loading-bar');
    if (bar) bar.style.width = `${percent}%`;
}

// Main initialization
document.addEventListener('DOMContentLoaded', async () => {
    try {
        console.log('[Bootstrap] Starting game initialization...');

        // Phase 1: Load data (0-10%)
        showLoading('Loading game data...');
        const loader = new GameLoader(window.gameConfig.configUrl);

        // Subscribe to GameLoader progress
        loader.on('progress', ({ percent, message }) => {
            updateProgress(percent);
            showLoading(message);
        });

        const { config, entities, tiles } = await loader.loadAll();
        console.log('[Bootstrap] Data loaded:', {
            entities: Array.isArray(entities) ? entities.length : 0,
            tiles: typeof tiles === 'object' ? Object.keys(tiles).length : 0,
            assetManifest: Object.keys(config.assetManifest || {}).length
        });

        // Phase 2: Initialize graphics engine (10-15%)
        updateProgress(10);
        showLoading('Initializing graphics...');
        const graphics = new GraphicsEngine(config.assetManifest, {
            width: window.innerWidth,
            height: window.innerHeight,
            backgroundColor: 0x87CEEB  // Sky blue
        });

        const gameContainer = document.getElementById('game-container');
        await graphics.initApplication(gameContainer);

        // Phase 3: Load textures with progress tracking (15-75%)
        updateProgress(15);
        showLoading('Loading assets...');

        // Subscribe to GraphicsEngine progress
        graphics.onProgress(({ percent, message }) => {
            updateProgress(percent);
            showLoading(message);
        });

        await graphics.loadAllTextures();

        // Phase 4: Initialize game (75-100%)
        updateProgress(75);
        showLoading('Initializing game...');
        // Wrap entities in expected format (tiles already has correct format from GameLoader)
        const entitiesData = Array.isArray(entities) ? entities : [];
        const tilesData = tiles;  // Already contains { tiles, shakeZones }

        console.log('[Bootstrap] Creating game instance with:', {
            entitiesCount: entitiesData.length,
            tilesCount: Object.keys(tilesData.tiles || {}).length,
            shakeZonesCount: Object.keys(tilesData.shakeZones || {}).length,
            configKeys: Object.keys(config)
        });

        const game = new ZFactoryGame(config, entitiesData, tilesData, graphics);

        // Subscribe to Game initialization progress
        game.onProgress(({ percent, message }) => {
            updateProgress(percent);
            showLoading(message);
        });

        console.log('[Bootstrap] Game instance created, calling init()...');
        await game.init();
        console.log('[Bootstrap] Game init completed');

        // Done - hide loading screen
        updateProgress(100);
        console.log('[Bootstrap] Initialization complete!');

        // Small delay before hiding loading screen for visual feedback
        await new Promise(resolve => setTimeout(resolve, 200));

        const loadingEl = document.getElementById('loading');
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }

        // Expose game globally for debugging
        window.game = game;

    } catch (error) {
        console.error('[Bootstrap] Failed to initialize game:', error);

        // Hide loading screen
        const loadingEl = document.getElementById('loading');
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }

        // Build detailed error message
        let errorMessage = error.message || 'Unknown error';
        let errorDetails = '';

        // Add suggestion for common errors
        if (error.message && error.message.includes('You may need to log in')) {
            errorDetails = 'Please refresh the page and log in.';
        } else if (error.message && error.message.includes('Failed to load')) {
            errorDetails = 'Check your network connection and try refreshing the page.';
        } else {
            errorDetails = 'Please check browser console (F12) for details and refresh the page.';
        }

        // Show error in modal dialog
        ErrorModal.show('Failed to Initialize Game', errorMessage, errorDetails);

        // Show error details in console
        if (error.stack) {
            console.error(error.stack);
        }
    }
});
