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

        // Check HTTP status
        if (!response.ok) {
            throw new Error(`Failed to load config: HTTP ${response.status} ${response.statusText}`);
        }

        // Check Content-Type
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error(`Failed to load config: Expected JSON, got ${contentType || 'unknown'}. You may need to log in.`);
        }

        const data = await response.json();

        // Validate response structure
        if (!data || typeof data !== 'object') {
            throw new Error('Failed to load config: Invalid response format');
        }

        if (data.result !== 'ok') {
            throw new Error('Failed to load config: ' + (data.error || 'Unknown error'));
        }

        // Validate required fields
        if (!data.landing || !data.entityTypes || !data.config) {
            throw new Error('Failed to load config: Missing required fields (landing, entityTypes, or config)');
        }

        this.emit('configLoaded', data);
        console.log('[GameLoader] Config loaded');

        // Return clean data (without result/error wrapper)
        return data;
    }

    /**
     * Load entities
     * Returns: entities
     */
    async loadEntities(entitiesUrl) {
        console.log('[GameLoader] Loading entities...');
        const response = await fetch(entitiesUrl);

        // Check HTTP status
        if (!response.ok) {
            throw new Error(`Failed to load entities: HTTP ${response.status} ${response.statusText}`);
        }

        // Check Content-Type
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error(`Failed to load entities: Expected JSON, got ${contentType || 'unknown'}. You may need to log in.`);
        }

        const data = await response.json();

        // Validate response structure
        if (!data || typeof data !== 'object') {
            throw new Error('Failed to load entities: Invalid response format');
        }

        if (data.result !== 'ok') {
            throw new Error('Failed to load entities: ' + (data.error || 'Unknown error'));
        }

        // Validate required fields
        if (!Array.isArray(data.entities)) {
            throw new Error('Failed to load entities: Missing or invalid entities field');
        }

        this.emit('entitiesLoaded', data);
        console.log(`[GameLoader] Loaded ${data.entities.length} entities`);

        // Return clean data (just entities array, without result wrapper)
        return data.entities;
    }

    /**
     * Load map tiles
     * Returns: tiles
     */
    async loadTiles(mapUrl) {
        console.log('[GameLoader] Loading map tiles...');
        const response = await fetch(mapUrl);

        // Check HTTP status
        if (!response.ok) {
            throw new Error(`Failed to load tiles: HTTP ${response.status} ${response.statusText}`);
        }

        // Check Content-Type
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error(`Failed to load tiles: Expected JSON, got ${contentType || 'unknown'}. You may need to log in.`);
        }

        const data = await response.json();

        // Validate response structure
        if (!data || typeof data !== 'object') {
            throw new Error('Failed to load tiles: Invalid response format');
        }

        if (data.result !== 'ok') {
            throw new Error('Failed to load tiles: ' + (data.error || 'Unknown error'));
        }

        // Validate required fields
        if (!data.tiles || typeof data.tiles !== 'object') {
            throw new Error('Failed to load tiles: Missing or invalid tiles field');
        }

        this.emit('tilesLoaded', data);
        console.log(`[GameLoader] Loaded ${Object.keys(data.tiles).length} tiles`);

        // Return clean data (just tiles object, without result wrapper)
        return data.tiles;
    }

    /**
     * Load all data in optimal order
     * 1. Config first (contains URLs for other endpoints + asset manifest)
     * 2. Entities and Tiles in parallel
     *
     * @returns {Object} { config, entities, tiles }
     */
    async loadAll() {
        // Phase 1: Load config (0-5%)
        this.emit('progress', { percent: 0, message: 'Loading game configuration...' });
        const config = await this.loadConfig();
        this.emit('progress', { percent: 5, message: 'Loading game data...' });

        // Phase 2: Load entities and tiles in parallel (5-10%)
        const [entities, tiles] = await Promise.all([
            this.loadEntities(config.config.entitiesUrl),
            this.loadTiles(config.config.mapUrl)
        ]);
        this.emit('progress', { percent: 10, message: 'Configuration loaded' });

        console.log('[GameLoader] All data loaded');
        return { config, entities, tiles };
    }
}
