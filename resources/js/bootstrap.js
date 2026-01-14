import { GameLoader } from './core/GameLoader.js';
import { GraphicsEngine } from './core/GraphicsEngine.js';
import ZFactoryGame from './game.js';

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

        // Phase 1: Load data
        showLoading('Loading game data...');
        const loader = new GameLoader(window.gameConfig.configUrl);

        const { config, entities, tiles } = await loader.loadAll();
        console.log('[Bootstrap] Data loaded:', {
            entities: entities.entities?.length || 0,
            tiles: tiles.tiles?.length || 0,
            assetManifest: Object.keys(config.assetManifest || {}).length
        });

        // Phase 2: Initialize graphics engine
        showLoading('Initializing graphics...');
        const graphics = new GraphicsEngine(config.assetManifest, {
            width: window.innerWidth,
            height: window.innerHeight,
            backgroundColor: 0x87CEEB  // Sky blue
        });

        const gameContainer = document.getElementById('game-container');
        await graphics.initApplication(gameContainer);

        // Phase 3: Load textures with progress tracking
        showLoading('Loading assets...');
        updateProgress(0);

        graphics.onProgress((progress) => {
            updateProgress(progress.percent);
            showLoading(`Loading assets... ${progress.loaded}/${progress.total} (${progress.percent}%)`);
        });

        await graphics.loadAllTextures();

        // Phase 4: Initialize game
        showLoading('Initializing game...');
        const game = new ZFactoryGame(config, entities, tiles, graphics);
        await game.init();

        // Done - hide loading screen
        console.log('[Bootstrap] Initialization complete!');
        const loadingEl = document.getElementById('loading');
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }

        // Expose game globally for debugging
        window.game = game;

    } catch (error) {
        console.error('[Bootstrap] Failed to initialize game:', error);
        showLoading('Error loading game. Please refresh the page.');

        // Show error details in console
        if (error.stack) {
            console.error(error.stack);
        }
    }
});
