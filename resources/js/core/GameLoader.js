/**
 * GameLoader - Orchestrates loading of game data via Ajax
 *
 * Responsibilities:
 * - Load /game/config (reference data + asset manifest)
 * - Load /game/entities (entities + pipe systems)
 * - Load /map/tiles (map tiles)
 * - Emit events for progress tracking
 *
 * Does NOT handle rendering or graphics - that's GraphicsEngine's job.
 */
export class GameLoader {
    constructor(configUrl) {
        this.configUrl = configUrl;
        this.callbacks = {
            configLoaded: [],
            entitiesLoaded: [],
            tilesLoaded: [],
            progress: []
        };
    }

    /**
     * Register event callback
     * @param {string} event - Event name (configLoaded, entitiesLoaded, tilesLoaded, progress)
     * @param {Function} callback - Callback function
     */
    on(event, callback) {
        if (this.callbacks[event]) {
            this.callbacks[event].push(callback);
        }
    }

    /**
     * Emit event to all registered callbacks
     * @private
     */
    emit(event, data) {
        if (this.callbacks[event]) {
            this.callbacks[event].forEach(cb => cb(data));
        }
    }

    /**
     * Load game config
     * Returns: landing, entityTypes, resources, recipes, config, assetManifest, etc.
     */
    async loadConfig() {
        console.log('[GameLoader] Loading config...');
        const response = await fetch(this.configUrl);
        const data = await response.json();

        if (data.result !== 'ok') {
            throw new Error('Failed to load config: ' + (data.error || 'Unknown error'));
        }

        this.emit('configLoaded', data);
        console.log('[GameLoader] Config loaded');
        return data;
    }

    /**
     * Load entities
     * Returns: entities, pipeSystems
     */
    async loadEntities(entitiesUrl) {
        console.log('[GameLoader] Loading entities...');
        const response = await fetch(entitiesUrl);
        const data = await response.json();

        if (data.result !== 'ok') {
            throw new Error('Failed to load entities: ' + (data.error || 'Unknown error'));
        }

        this.emit('entitiesLoaded', data);
        console.log(`[GameLoader] Loaded ${data.entities?.length || 0} entities`);
        return data;
    }

    /**
     * Load map tiles
     * Returns: tiles
     */
    async loadTiles(mapUrl) {
        console.log('[GameLoader] Loading map tiles...');
        const response = await fetch(mapUrl);
        const data = await response.json();

        if (data.result !== 'ok') {
            throw new Error('Failed to load tiles: ' + (data.error || 'Unknown error'));
        }

        this.emit('tilesLoaded', data);
        console.log(`[GameLoader] Loaded ${data.tiles?.length || 0} tiles`);
        return data;
    }

    /**
     * Load all data in optimal order
     * 1. Config first (contains URLs for other endpoints + asset manifest)
     * 2. Entities and Tiles in parallel
     *
     * @returns {Object} { config, entities, tiles }
     */
    async loadAll() {
        // Phase 1: Load config (required for URLs)
        const config = await this.loadConfig();

        // Phase 2: Load entities and tiles in parallel
        const [entities, tiles] = await Promise.all([
            this.loadEntities(config.config.entitiesUrl),
            this.loadTiles(config.config.mapUrl)
        ]);

        console.log('[GameLoader] All data loaded');
        return { config, entities, tiles };
    }
}
