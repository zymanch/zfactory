import * as PIXI from 'pixi.js';
import { Camera } from './modules/camera.js';
import { InputManager } from './modules/inputManager.js';
import { BuildPanel } from './modules/ui/BuildPanel.js';
import { ResourcePanel } from './modules/ui/ResourcePanel.js';
import { CameraInfo } from './modules/ui/CameraInfo.js';
import { ControlsHint } from './modules/ui/ControlsHint.js';
import { BuildingWindow } from './modules/windows/buildingWindow.js';
import { BuildMode } from './modules/modes/buildMode.js';
import { FogOfWar } from './modules/fogOfWar.js';
import { TileLayerManager } from './modules/tileLayerManager.js';
import { EntityTooltip } from './modules/entityTooltip.js';
import { BuildingRules } from './modules/buildingRules.js';
import { ResourceTransportManager } from './modules/resourceTransport/ResourceTransportManager.js';
import { ResourceRenderer } from './modules/resourceTransport/ResourceRenderer.js';
import { LandingWindow } from './modules/windows/landingWindow.js';
import { LandingEditMode } from './modules/modes/landingEditMode.js';
import { CloudManager } from './modules/cloudManager.js';
import { ConveyorManager } from './modules/conveyorManager.js';
import { GameModeManager, GameMode } from './modules/modes/gameModeManager.js';
import { EntityInfoWindow } from './modules/windows/entityInfoWindow.js';
import { ConstructionManager } from './modules/constructionManager.js';
import { DepositLayerManager } from './modules/depositLayerManager.js';
import { DepositTooltip } from './modules/depositTooltip.js';
import { SPRITE_STATES, SPRITE_STATES_ORIGINAL, CONSTRUCTION_FRAMES, VIEWPORT_RELOAD_INTERVAL } from './modules/constants.js';
import { getCSRFToken } from './modules/utils.js';

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
        this.landingAdjacencies = [];
        this.entityTypes = {};
        this.depositTypes = {};
        this.resources = {};
        this.recipes = {};
        this.entityTypeRecipes = {};
        this.entityTypeCosts = {};
        this.userResources = {};

        // Entity management
        this.loadedEntities = new Map();
        this.entityData = new Map();
        this.hoveredEntity = null;

        // State flags
        this.needsReload = false;
        this.lastReloadTime = 0;
        this.tilesLoaded = false;
        this.depositsLoaded = false;
        this.entitiesLoaded = false;

        // FPS tracking
        this.lastFpsTime = 0;
        this.frameCount = 0;

        // Modules (initialized after config load)
        this.camera = null;
        this.input = null;

        // UI modules
        this.buildPanel = null;
        this.resourcePanel = null;
        this.cameraInfo = null;
        this.controlsHint = null;

        this.buildingWindow = null;
        this.buildMode = null;
        this.fogOfWar = null;
        this.tileManager = null;
        this.depositManager = null;
        this.entityTooltip = null;
        this.depositTooltip = null;
        this.buildingRules = null;
        this.resourceTransport = null;
        this.resourceRenderer = null;
        this.landingWindow = null;
        this.landingEditMode = null;
        this.cloudManager = null;
        this.conveyorManager = null;
        this.gameModeManager = null;
        this.entityInfoWindow = null;
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
        await this.initModulesPost();
        await this.loadViewport();
        this.startGameLoop();
    }

    /**
     * Initialize all game modules
     */
    initModules() {
        this.gameModeManager = new GameModeManager(this);
        this.camera = new Camera(this);
        this.input = new InputManager(this);

        // UI modules
        this.buildPanel = new BuildPanel(this);
        this.resourcePanel = new ResourcePanel(this);
        this.cameraInfo = new CameraInfo(this);
        this.controlsHint = new ControlsHint(this);

        this.buildingWindow = new BuildingWindow(this);
        this.buildMode = new BuildMode(this);
        this.fogOfWar = new FogOfWar(this);
        this.tileManager = new TileLayerManager(this);
        this.depositManager = new DepositLayerManager(this);
        this.entityTooltip = new EntityTooltip(this);
        this.depositTooltip = new DepositTooltip(this);
        this.buildingRules = new BuildingRules(this);
        this.resourceTransport = new ResourceTransportManager(this);
        this.resourceRenderer = new ResourceRenderer(this);
        this.landingWindow = new LandingWindow(this);
        this.landingEditMode = new LandingEditMode(this);
        this.cloudManager = new CloudManager(this);
        this.conveyorManager = new ConveyorManager(this);
        this.entityInfoWindow = new EntityInfoWindow(this);
        this.constructionManager = new ConstructionManager(this);
    }

    /**
     * Initialize PIXI application
     */
    async initPixi() {
        this.app = new PIXI.Application();
        await this.app.init({
            width: window.innerWidth,
            height: window.innerHeight,
            backgroundColor: 0x87CEEB,
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
        this.worldContainer.sortableChildren = true;  // Enable z-index sorting for all layers

        this.landingLayer = new PIXI.Container();
        this.entityLayer = new PIXI.Container();

        this.worldContainer.addChild(this.landingLayer);
        this.worldContainer.addChild(this.entityLayer);
        this.app.stage.addChild(this.worldContainer);

        this.landingLayer.sortableChildren = true;
        this.landingLayer.zIndex = 1;

        this.entityLayer.sortableChildren = true;
        this.entityLayer.eventMode = 'static';
        this.entityLayer.zIndex = 2;

        // depositLayer will be added by depositManager.init() with z-index 1.5
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
    async initModulesPost() {
        this.input.init();

        // UI modules
        this.buildPanel.init();
        this.resourcePanel.init();
        this.cameraInfo.init();
        this.controlsHint.init();

        this.buildingWindow.init();
        this.buildMode.init();
        this.fogOfWar.init();
        this.depositManager.init();
        this.entityTooltip.init();
        this.depositTooltip.init();
        this.landingWindow.init();
        this.landingEditMode.init();
        this.entityInfoWindow.init();

        if (this.cloudManager) {
            await this.cloudManager.init();
        }

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
        this.landingAdjacencies = data.landingAdjacencies || [];
        this.entityTypes = data.entityTypes;
        this.depositTypes = data.depositTypes || {};
        this.resources = data.resources || {};
        this.recipes = data.recipes || {};
        this.entityTypeRecipes = data.entityTypeRecipes || {};
        this.entityTypeCosts = data.entityTypeCosts || {};
        this.userResources = data.userResources || {};
        this.initialBuildPanel = data.buildPanel || [];
        this.initialEyeEntities = data.eyeEntities || [];
        this.initialDeposits = data.deposits || [];
        this.initialCameraPosition = data.cameraPosition || { x: 0, y: 0, zoom: 1 };
        this.initialEntityResources = data.entityResources || [];
        this.initialCraftingStates = data.craftingStates || [];
        this.initialTransportStates = data.transportStates || [];

        // Setup gameData structure for new atlas system
        this.gameData = {
            landings: this.landingTypes,
            entityTypes: this.entityTypes
        };

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
        await this.loadTransitionTextures();
        await this.loadEntityTextures();
        await this.loadDepositTextures();
        await this.conveyorManager.loadAtlases();
    }

    /**
     * Load terrain textures
     */
    async loadLandingTextures() {
        for (const landingId in this.landingTypes) {
            const landing = this.landingTypes[landingId];
            const url = this.assetUrl(this.config.tilesPath + 'landing/' + landing.folder + '.png');
            try {
                this.textures['landing_' + landingId] = await PIXI.Assets.load(url);
            } catch (e) {
                console.warn('Failed to load landing texture:', url);
            }
        }
    }

    /**
     * Load texture atlases for all landing types
     */
    async loadTransitionTextures() {
        const landings = Object.values(this.landingTypes);

        for (const landing of landings) {
            const atlasUrl = this.assetUrl(`${this.config.tilesPath}landing/atlases/${landing.folder}_atlas.png`);

            try {
                const texture = await PIXI.Assets.load(atlasUrl);
                this.tileManager.landingAtlases[landing.folder + '_atlas'] = texture;
                console.log('Loaded atlas:', landing.folder + '_atlas');
            } catch (e) {
                console.error('Failed to load atlas:', atlasUrl, e);
            }
        }

        console.log('All atlases loaded.');
    }

    /**
     * Check if two landing types have an adjacency relationship
     */
    hasLandingAdjacency(landingId1, landingId2) {
        if (!this.adjacencySet) return false;
        return this.adjacencySet.has(`${landingId1}_${landingId2}`);
    }

    /**
     * Load deposit textures (only normal.png for each deposit type)
     */
    async loadDepositTextures() {
        for (const depositTypeId in this.depositTypes) {
            const depositType = this.depositTypes[depositTypeId];
            const folder = depositType.image_url;

            // Only load normal.png for deposits (no damaged, blueprint, etc.)
            const normalUrl = this.assetUrl(`${this.config.tilesPath}deposits/${folder}/normal.png`);

            try {
                const texture = await PIXI.Assets.load(normalUrl);
                this.textures[`deposit_${depositTypeId}_normal`] = texture;
            } catch (e) {
                console.warn('Failed to load deposit texture:', normalUrl, e);
            }
        }
    }

    /**
     * Load entity textures from atlases (PNG only, no SVG)
     */
    async loadEntityTextures() {
        const { tileWidth, tileHeight } = this.config;

        for (const typeId in this.entityTypes) {
            const entityType = this.entityTypes[typeId];
            const folder = entityType.image_url;
            const width = entityType.width || 1;
            const height = entityType.height || 1;

            const pixelWidth = width * tileWidth;
            const pixelHeight = height * tileHeight;

            // Load atlas.png
            const atlasUrl = this.assetUrl(`${this.config.tilesPath}entities/${folder}/atlas.png`);

            try {
                const atlasTexture = await PIXI.Assets.load(atlasUrl);

                // Create textures for each state from atlas
                // Atlas row 1: [normal][damaged][blueprint][normal_selected][damaged_selected][deleting][crafting]
                // Atlas row 2: [construction_10][construction_20]...[construction_90]

                // Load all 7 sprites from row 1
                let xOffset = 0;
                for (const state of SPRITE_STATES) {
                    const textureKey = `entity_${typeId}_${state}`;

                    // Create texture from atlas region
                    const rect = new PIXI.Rectangle(xOffset, 0, pixelWidth, pixelHeight);
                    this.textures[textureKey] = new PIXI.Texture({
                        source: atlasTexture.source,
                        frame: rect
                    });

                    xOffset += pixelWidth;
                }

                // Load 9 construction frames from row 2
                xOffset = 0;
                const yOffset = pixelHeight; // Second row
                for (const progress of CONSTRUCTION_FRAMES) {
                    const textureKey = `entity_${typeId}_construction_${progress}`;

                    const rect = new PIXI.Rectangle(xOffset, yOffset, pixelWidth, pixelHeight);
                    this.textures[textureKey] = new PIXI.Texture({
                        source: atlasTexture.source,
                        frame: rect
                    });

                    xOffset += pixelWidth;
                }
            } catch (e) {
                console.warn('Failed to load entity atlas:', atlasUrl, e);
            }
        }
    }

    /**
     * Get texture key based on entity state, durability, and hover type
     * @param {object} entity - Entity data
     * @param {boolean} isSelected - Is entity selected/hovered
     * @param {string} hoverType - Type of hover sprite ('selected' or 'deleting')
     */
    getEntityTextureKey(entity, isSelected = false, hoverType = 'selected') {
        const typeId = entity.entity_type_id;
        const entityType = this.entityTypes[typeId];

        // Check if entity is under construction
        const constructionProgress = parseInt(entity.construction_progress) || 100;
        if (constructionProgress < 100) {
            // Show construction frame based on progress
            // 0-9% -> construction_10, 10-19% -> construction_10, 20-29% -> construction_20, etc.
            // Round progress to nearest 10 (ceiling)
            const frameProgress = Math.ceil(constructionProgress / 10) * 10;
            const clampedProgress = Math.max(10, Math.min(90, frameProgress)); // Clamp to 10-90
            return `entity_${typeId}_construction_${clampedProgress}`;
        }

        if (entity.state === 'blueprint') {
            return `entity_${typeId}_blueprint`;
        }

        const maxDurability = entityType?.max_durability || 100;
        const durability = entity.durability || maxDurability;
        const isDamaged = durability < (maxDurability * 0.5);

        // If selected/hovered, use hover sprite type (selected or deleting)
        if (isSelected) {
            if (hoverType === 'deleting') {
                // Use deleting sprite with red outline
                return `entity_${typeId}_deleting`;
            } else {
                // Use selected sprite (yellow outline)
                return isDamaged ? `entity_${typeId}_damaged_selected` : `entity_${typeId}_normal_selected`;
            }
        }

        // Not selected - use normal or damaged
        return isDamaged ? `entity_${typeId}_damaged` : `entity_${typeId}_normal`;
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

        // Load deposits after tiles (deposits need to be above landing layer)
        if (!this.depositsLoaded) {
            this.depositManager.loadDeposits(this.initialDeposits);
            this.depositsLoaded = true;
        }

        if (!this.entitiesLoaded) {
            await this.loadAllEntities();
            this.entitiesLoaded = true;

            // Initialize resource transport after entities are loaded
            this.resourceTransport.init();

            // Initialize resource renderer (visual layer for resources on conveyors/manipulators)
            await this.resourceRenderer.init();
        }

        const viewport = this.calculateViewport();

        // Sky tiles removed - using solid background color + cloud system
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
            const entityType = this.entityTypes[entity.entity_type_id];

            // Handle conveyors separately
            if (entityType && entityType.type === 'transporter') {
                const texture = this.conveyorManager.getConveyorTexture(entity, false, 0);
                if (texture) {
                    const sprite = this.createEntitySprite(entity, texture, isVisible);
                    this.entityLayer.addChild(sprite);
                    this.loadedEntities.set(key, sprite);
                    this.conveyorManager.registerConveyor(entity.entity_id, sprite);
                }
            } else {
                // Handle other entities normally
                const textureKey = this.getEntityTextureKey(entity, false);
                const texture = this.textures[textureKey];

                if (texture) {
                    const sprite = this.createEntitySprite(entity, texture, isVisible);
                    this.entityLayer.addChild(sprite);
                    this.loadedEntities.set(key, sprite);
                }
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
            sprite.on('click', (e) => this.onEntityClick(sprite, e));
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
        const entityType = this.entityTypes[entity.entity_type_id];

        // Get hover sprite type based on current game mode
        const hoverSpriteType = this.gameModeManager.getHoverSpriteType();

        if (isHovering && hoverSpriteType) {
            // Handle conveyors separately
            if (entityType && entityType.type === 'transporter') {
                this.conveyorManager.updateConveyorTexture(entity.entity_id, true);
            } else {
                // Handle other entities normally
                const textureKey = this.getEntityTextureKey(entity, true, hoverSpriteType);
                const texture = this.textures[textureKey];

                if (texture) {
                    sprite.texture = texture;
                }
            }
        } else {
            // Reset to normal texture
            if (entityType && entityType.type === 'transporter') {
                this.conveyorManager.updateConveyorTexture(entity.entity_id, false);
            } else {
                const textureKey = this.getEntityTextureKey(entity, false);
                const texture = this.textures[textureKey];

                if (texture) {
                    sprite.texture = texture;
                }
            }
        }

        // Show/hide tooltip based on game mode
        if (isHovering && this.entityTooltip && this.gameModeManager.shouldShowTooltip()) {
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
     * Handle entity click
     */
    onEntityClick(sprite, event) {
        const key = sprite.entityKey;
        const entity = this.entityData.get(key);

        if (!entity) return;

        const mode = this.gameModeManager;

        // Handle different game modes
        if (mode.isMode(GameMode.DELETE)) {
            // Delete mode - delete entity
            this.deleteEntity(entity);
        } else if (mode.isMode(GameMode.NORMAL)) {
            // Normal mode - open entity info window
            mode.switchMode(GameMode.ENTITY_INFO, { entityId: entity.entity_id });
        }
    }

    /**
     * Delete entity (for DELETE mode)
     */
    async deleteEntity(entity) {
        const deleteUrl = this.config.deleteEntityUrl;
        if (!deleteUrl) {
            console.error('deleteEntityUrl not configured');
            return;
        }

        try {
            const response = await fetch(deleteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCSRFToken()
                },
                body: JSON.stringify({ entity_id: entity.entity_id })
            });

            const data = await response.json();

            if (data.result === 'ok') {
                // Remove entity from client
                const key = `entity_${entity.entity_id}`;
                this.entityData.delete(key);
                const sprite = this.loadedEntities.get(key);
                if (sprite) {
                    this.entityLayer.removeChild(sprite);
                    sprite.destroy();
                    this.loadedEntities.delete(key);
                }

                // Update fog of war if it was an eye entity
                if (this.fogOfWar) {
                    const entityType = this.entityTypes[entity.entity_type_id];
                    if (entityType && entityType.type === 'eye') {
                        this.fogOfWar.removeEyeEntity(entity.entity_id);
                        this.loadViewport();
                    }
                }
            } else {
                console.error('Failed to delete entity:', data.error);
            }
        } catch (e) {
            console.error('Error deleting entity:', e);
        }
    }

    /**
     * Main game loop
     */
    gameLoop(ticker) {
        const moved = this.camera.update();
        this.camera.apply();

        if (this.cloudManager) {
            this.cloudManager.applyParallax();
        }

        if (moved) {
            this.needsReload = true;
        }

        const now = performance.now();
        if (this.needsReload && now - this.lastReloadTime > VIEWPORT_RELOAD_INTERVAL) {
            this.loadViewport();
            this.needsReload = false;
            this.lastReloadTime = now;
        }

        // Update UI
        this.cameraInfo.update();

        // Tick resource transport system
        this.resourceTransport.tick();

        // Render resource sprites on conveyors/manipulators
        this.resourceRenderer.render();

        // Update cloud positions
        if (this.cloudManager) {
            this.cloudManager.update();
        }

        // Update conveyor animations
        if (this.conveyorManager) {
            this.conveyorManager.update();
        }

        // Update construction progress
        if (this.constructionManager) {
            this.constructionManager.update();
        }

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
