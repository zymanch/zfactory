import * as PIXI from 'pixi.js';
import { Camera } from './modules/camera.js';
import { InputManager } from './modules/inputManager.js';
import { BuildPanel } from './modules/buildPanel.js';
import { BuildingWindow } from './modules/buildingWindow.js';
import { BuildMode } from './modules/buildMode.js';
import { FogOfWar } from './modules/fogOfWar.js';
import { TileLayerManager } from './modules/tileLayerManager.js';
import { EntityTooltip } from './modules/entityTooltip.js';
import { BuildingRules } from './modules/buildingRules.js';
import { ResourceTransportManager } from './modules/resourceTransport/ResourceTransportManager.js';
import { SPRITE_STATES, VIEWPORT_RELOAD_INTERVAL } from './modules/constants.js';

/**
 * ZFactory Game Engine
 * Browser automation game with PixiJS rendering
 */
class ZFactoryGame {
    constructor(configUrl) {
        this.configUrl = configUrl;
        this.config = {};

        this.zoom = 1;
        this.textures = {};
        this.landingTypes = {};
        this.entityTypes = {};
        this.resources = {};
        this.recipes = {};
        this.entityTypeRecipes = {};

        // Entity management
        this.loadedEntities = new Map();
        this.entityData = new Map();
        this.hoveredEntity = null;

        // State flags
        this.needsReload = false;
        this.lastReloadTime = 0;
        this.tilesLoaded = false;
        this.entitiesLoaded = false;

        // FPS tracking
        this.lastFpsTime = 0;
        this.frameCount = 0;

        // Modules (initialized after config load)
        this.camera = null;
        this.input = null;
        this.buildPanel = null;
        this.buildingWindow = null;
        this.buildMode = null;
        this.fogOfWar = null;
        this.tileManager = null;
        this.entityTooltip = null;
        this.buildingRules = null;
        this.resourceTransport = null;
    }

    /**
     * Initialize game
     */
    async init() {
        await this.loadConfig();
        this.initModules();
        await this.initPixi();
        this.initLayers();
        this.initCamera();
        await this.loadTextures();
        this.initModulesPost();
        await this.loadViewport();
        this.startGameLoop();
    }

    /**
     * Initialize all game modules
     */
    initModules() {
        this.camera = new Camera(this);
        this.input = new InputManager(this);
        this.buildPanel = new BuildPanel(this);
        this.buildingWindow = new BuildingWindow(this);
        this.buildMode = new BuildMode(this);
        this.fogOfWar = new FogOfWar(this);
        this.tileManager = new TileLayerManager(this);
        this.entityTooltip = new EntityTooltip(this);
        this.buildingRules = new BuildingRules(this);
        this.resourceTransport = new ResourceTransportManager(this);
    }

    /**
     * Initialize PIXI application
     */
    async initPixi() {
        this.app = new PIXI.Application();
        await this.app.init({
            width: window.innerWidth,
            height: window.innerHeight,
            backgroundColor: 0x1a1a2e,
            resizeTo: window,
            antialias: false,
            resolution: window.devicePixelRatio || 1
        });

        const container = document.getElementById('game-container');
        if (container) {
            container.appendChild(this.app.canvas);
        }

        const loading = document.getElementById('loading');
        if (loading) {
            loading.style.display = 'none';
        }
    }

    /**
     * Initialize render layers
     */
    initLayers() {
        this.worldContainer = new PIXI.Container();
        this.landingLayer = new PIXI.Container();
        this.entityLayer = new PIXI.Container();

        this.worldContainer.addChild(this.landingLayer);
        this.worldContainer.addChild(this.entityLayer);
        this.app.stage.addChild(this.worldContainer);

        this.landingLayer.sortableChildren = true;
        this.entityLayer.sortableChildren = true;
        this.entityLayer.eventMode = 'static';
    }

    /**
     * Initialize camera position from server config
     */
    initCamera() {
        if (this.initialCameraPosition) {
            this.camera.setInitialPosition(
                this.initialCameraPosition.x,
                this.initialCameraPosition.y,
                this.initialCameraPosition.zoom
            );
        }
    }

    /**
     * Initialize modules that need assets loaded first
     */
    initModulesPost() {
        this.input.init();
        this.buildPanel.init();
        this.buildingWindow.init();
        this.buildMode.init();
        this.fogOfWar.init();
        this.entityTooltip.init();
        this.buildPanel.refresh();
        // Note: resourceTransport.init() is called after entities are loaded in loadViewport()
    }

    /**
     * Start the game loop
     */
    startGameLoop() {
        this.app.ticker.add((ticker) => this.gameLoop(ticker));
    }

    /**
     * Load game config from server
     */
    async loadConfig() {
        const response = await fetch(this.configUrl);
        const data = await response.json();

        if (data.result !== 'ok') {
            throw new Error('Failed to load config');
        }

        this.config = data.config;
        this.landingTypes = data.landing;
        this.entityTypes = data.entityTypes;
        this.resources = data.resources || {};
        this.recipes = data.recipes || {};
        this.entityTypeRecipes = data.entityTypeRecipes || {};
        this.initialBuildPanel = data.buildPanel || [];
        this.initialEyeEntities = data.eyeEntities || [];
        this.initialCameraPosition = data.cameraPosition || { x: 0, y: 0, zoom: 1 };
        this.initialEntityResources = data.entityResources || [];
        this.initialCraftingStates = data.craftingStates || [];
        this.initialTransportStates = data.transportStates || [];

        // Initialize building rules from server config
        if (this.buildingRules && data.buildingRules) {
            this.buildingRules.init(data.buildingRules);
        }
    }

    /**
     * Get asset URL with version query string
     */
    assetUrl(path) {
        const v = this.config.assetVersion || 1;
        return `${path}?v=${v}`;
    }

    /**
     * Load texture assets
     */
    async loadTextures() {
        await this.loadLandingTextures();
        await this.loadEntityTextures();
    }

    /**
     * Load terrain textures
     */
    async loadLandingTextures() {
        for (const landingId in this.landingTypes) {
            const landing = this.landingTypes[landingId];
            const url = this.assetUrl(this.config.tilesPath + 'landing/' + landing.image_url);
            try {
                this.textures['landing_' + landingId] = await PIXI.Assets.load(url);
            } catch (e) {
                console.warn('Failed to load landing texture:', url);
            }
        }
    }

    /**
     * Load entity textures (all states)
     */
    async loadEntityTextures() {
        for (const typeId in this.entityTypes) {
            const entityType = this.entityTypes[typeId];
            const folder = entityType.image_url;
            const ext = entityType.extension || 'svg';

            for (const state of SPRITE_STATES) {
                const url = this.assetUrl(`${this.config.tilesPath}entities/${folder}/${state}.${ext}`);
                const textureKey = `entity_${typeId}_${state}`;
                try {
                    this.textures[textureKey] = await PIXI.Assets.load(url);
                } catch (e) {
                    console.warn('Failed to load entity texture:', url);
                }
            }
        }
    }

    /**
     * Get texture key based on entity state and durability
     */
    getEntityTextureKey(entity, isSelected = false) {
        const typeId = entity.entity_type_id;
        const entityType = this.entityTypes[typeId];

        if (entity.state === 'blueprint') {
            return `entity_${typeId}_blueprint`;
        }

        const maxDurability = entityType?.max_durability || 100;
        const durability = entity.durability || maxDurability;
        const isDamaged = durability < (maxDurability * 0.5);

        if (isDamaged) {
            return isSelected ? `entity_${typeId}_damaged_selected` : `entity_${typeId}_damaged`;
        }

        return isSelected ? `entity_${typeId}_normal_selected` : `entity_${typeId}_normal`;
    }

    /**
     * Load map tiles and entities for current viewport
     */
    async loadViewport() {
        const { tileWidth, tileHeight } = this.config;

        if (!this.tilesLoaded) {
            await this.loadMapTiles();
            this.tilesLoaded = true;
        }

        if (!this.entitiesLoaded) {
            await this.loadAllEntities();
            this.entitiesLoaded = true;

            // Initialize resource transport after entities are loaded
            this.resourceTransport.init();
        }

        const viewport = this.calculateViewport();

        this.tileManager.renderSkyTiles(viewport.startX, viewport.startY, viewport.width, viewport.height);
        this.tileManager.renderIslandEdgeTiles(viewport.startX, viewport.startY, viewport.width, viewport.height);
        this.updateEntityVisibility();

        if (this.fogOfWar) {
            this.fogOfWar.renderFog(viewport.startX, viewport.startY, viewport.width, viewport.height);
        }
    }

    /**
     * Calculate viewport bounds in tiles
     */
    calculateViewport() {
        const { tileWidth, tileHeight } = this.config;
        const bufferTiles = 4;

        return {
            width: Math.ceil(window.innerWidth / (tileWidth * this.zoom)) + bufferTiles * 2,
            height: Math.ceil(window.innerHeight / (tileHeight * this.zoom)) + bufferTiles * 2,
            startX: Math.floor(this.camera.x / tileWidth) - bufferTiles,
            startY: Math.floor(this.camera.y / tileHeight) - bufferTiles
        };
    }

    /**
     * Load all map tiles from server
     */
    async loadMapTiles() {
        const response = await fetch(this.config.mapUrl);
        const data = await response.json();

        if (data.result === 'ok') {
            this.tileManager.storeTileData(data.tiles);
            this.tileManager.renderTiles(data.tiles);
        }
    }

    /**
     * Load all entities from server
     */
    async loadAllEntities() {
        const response = await fetch(this.config.entitiesUrl);
        const data = await response.json();

        if (data.result === 'ok') {
            this.renderEntities(data.entities);
        }
    }

    /**
     * Render entities with state-based textures
     */
    renderEntities(entities) {
        for (const entity of entities) {
            const key = `entity_${entity.entity_id}`;
            this.entityData.set(key, entity);

            if (this.loadedEntities.has(key)) continue;

            const isVisible = !this.fogOfWar || this.fogOfWar.isEntityVisible(entity);
            const textureKey = this.getEntityTextureKey(entity, false);
            const texture = this.textures[textureKey];

            if (texture) {
                const sprite = this.createEntitySprite(entity, texture, isVisible);
                this.entityLayer.addChild(sprite);
                this.loadedEntities.set(key, sprite);
            }
        }

        this.updateDebug('entities', this.loadedEntities.size);
    }

    /**
     * Create entity sprite with event handlers
     * Entity coordinates are stored as tiles, convert to pixels for rendering
     */
    createEntitySprite(entity, texture, isVisible) {
        const key = `entity_${entity.entity_id}`;
        const sprite = new PIXI.Sprite(texture);

        // Convert tile coordinates to pixel coordinates
        const { tileWidth, tileHeight } = this.config;
        const pixelX = parseInt(entity.x) * tileWidth;
        const pixelY = parseInt(entity.y) * tileHeight;

        sprite.x = pixelX;
        sprite.y = pixelY;
        sprite.zIndex = pixelY;
        sprite.visible = isVisible;
        sprite.eventMode = isVisible ? 'static' : 'none';
        sprite.cursor = isVisible ? 'pointer' : 'default';
        sprite.entityKey = key;

        if (entity.state !== 'blueprint') {
            sprite.on('pointerover', (e) => this.onEntityHover(sprite, true, e));
            sprite.on('pointerout', (e) => this.onEntityHover(sprite, false, e));
            sprite.on('pointermove', (e) => this.onEntityMove(e));
        }

        return sprite;
    }

    /**
     * Update entity visibility based on fog of war
     */
    updateEntityVisibility() {
        if (!this.fogOfWar) return;

        for (const [key, sprite] of this.loadedEntities) {
            const entity = this.entityData.get(key);
            if (!entity) continue;

            const isVisible = this.fogOfWar.isEntityVisible(entity);
            sprite.visible = isVisible;
            sprite.eventMode = isVisible ? 'static' : 'none';
        }
    }

    /**
     * Handle entity hover (selection highlight)
     */
    onEntityHover(sprite, isHovering, event) {
        const key = sprite.entityKey;
        const entity = this.entityData.get(key);

        if (!entity || entity.state === 'blueprint') return;

        this.hoveredEntity = isHovering ? key : null;
        const textureKey = this.getEntityTextureKey(entity, isHovering);
        const texture = this.textures[textureKey];

        if (texture) {
            sprite.texture = texture;
        }

        // Show/hide tooltip
        if (isHovering && this.entityTooltip) {
            const screenX = event.global.x;
            const screenY = event.global.y;
            this.entityTooltip.show(key, screenX, screenY);
        } else if (this.entityTooltip) {
            this.entityTooltip.hide();
        }
    }

    /**
     * Handle entity mouse move (update tooltip position)
     */
    onEntityMove(event) {
        if (this.entityTooltip && this.hoveredEntity) {
            this.entityTooltip.updatePosition(event.global.x, event.global.y);
        }
    }

    /**
     * Main game loop
     */
    gameLoop(ticker) {
        const moved = this.camera.update();
        this.camera.apply();

        if (moved) {
            this.needsReload = true;
        }

        const now = performance.now();
        if (this.needsReload && now - this.lastReloadTime > VIEWPORT_RELOAD_INTERVAL) {
            this.loadViewport();
            this.needsReload = false;
            this.lastReloadTime = now;
        }

        // Tick resource transport system
        this.resourceTransport.tick();

        this.updateDebug('camera', `${Math.round(this.camera.x)}, ${Math.round(this.camera.y)}`);
        this.updateFPS(now);
    }

    /**
     * Update FPS counter
     */
    updateFPS(now) {
        this.frameCount++;
        if (now - this.lastFpsTime >= 1000) {
            this.updateDebug('fps', this.frameCount);
            this.frameCount = 0;
            this.lastFpsTime = now;
        }
    }

    /**
     * Update debug display element
     */
    updateDebug(key, value) {
        const el = document.getElementById('debug-' + key);
        if (el) {
            el.textContent = value;
        }
    }

    // Proxy methods for backward compatibility with tileManager
    get loadedTiles() {
        return this.tileManager?.loadedTiles || new Map();
    }

    get tileDataMap() {
        return this.tileManager?.tileDataMap || new Map();
    }
}

// Initialize game when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.gameConfig !== 'undefined' && window.gameConfig.configUrl) {
        const game = new ZFactoryGame(window.gameConfig.configUrl);
        game.init().catch(console.error);
        window.game = game;
    }
});

export default ZFactoryGame;
