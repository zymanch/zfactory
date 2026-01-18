import * as PIXI from 'pixi.js';
import { Camera } from './modules/camera.js';
import { InputManager } from './modules/inputManager.js';
import { BuildPanel } from './modules/panels/BuildPanel.js';
import { ResourcePanel } from './modules/panels/ResourcePanel.js';
import { CameraInfo } from './modules/panels/DebugPanel.js';
import { ControlsHint } from './modules/panels/ControlsHint.js';
import { BuildingWindow } from './modules/windows/buildingWindow.js';
import { BuildMode } from './modules/modes/buildMode.js';
import { NormalMode } from './modules/modes/normalMode.js';
import { DeleteMode } from './modules/modes/deleteMode.js';
import { MenuMode } from './modules/modes/menuMode.js';
import { FogOfWar } from './modules/fogOfWar.js';
import { TileLayerManager } from './modules/tileLayerManager.js';
import { EntityTooltip } from './modules/tooltips/EntityTooltip.js';
import { BuildingRules } from './modules/buildingRules.js';
import { ResourceTransportManager } from './modules/resourceTransport/ResourceTransportManager.js';
import { ResourceRenderer } from './modules/resourceTransport/ResourceRenderer.js';
import { CloudManager } from './modules/cloudManager.js';
import { ConveyorManager } from './modules/conveyorManager.js';
import { GameModeManager, GameMode } from './modules/modes/gameModeManager.js';
import { EntityInfoWindow } from './modules/windows/entityInfoWindow.js';
import { TechnologyWindow } from './modules/windows/technologyWindow.js';
import { ConstructionManager } from './modules/constructionManager.js';
import { DepositLayerManager } from './modules/depositLayerManager.js';
import { PipeSystemManager } from './modules/pipes/PipeSystemManager.js';
import { PipeRenderer } from './modules/pipes/PipeRenderer.js';
import { PipeInletRenderer } from './modules/rendering/PipeInletRenderer.js';
import { PipeConnectionManager } from './modules/pipes/PipeConnectionManager.js';
import { ElectricitySystemManager } from './modules/electricity/ElectricitySystemManager.js';
import { ElectrificationLayerManager } from './modules/electricity/ElectrificationLayerManager.js';
import { NoPowerIndicator } from './modules/electricity/NoPowerIndicator.js';
import { PerformanceMonitor } from './core/PerformanceMonitor.js';
import { SPRITE_STATES, SPRITE_STATES_ORIGINAL, CONSTRUCTION_FRAMES, VIEWPORT_RELOAD_INTERVAL } from './modules/constants.js';
import { getCSRFToken } from './modules/utils.js';

/**
 * ZFactory Game Engine
 * Browser automation game with graphics abstraction
 *
 * NEW ARCHITECTURE:
 * - All data loaded via GameLoader (no Ajax in game)
 * - All graphics via GraphicsEngine (no direct PixiJS)
 * - Clean separation: data → graphics → logic
 */
class ZFactoryGame {
    constructor(configData, entitiesData, tilesData, graphics) {
        // Injected dependencies
        this.graphics = graphics;

        // Reference data from config
        this.landingTypes = configData.landing || {};
        this.entityTypes = configData.entityTypes || {};
        this.depositTypes = configData.depositTypes || {};
        this.resources = configData.resources || {};
        this.recipes = configData.recipes || {};
        this.config = configData.config || {};

        // Initial state from config
        this.userResources = configData.userResources || {};
        // REMOVED (2026-01): initialEntityResources, initialCraftingStates, initialTransportStates
        // These are now properties of entities in entitiesData
        this.initialCameraPosition = configData.cameraPosition || { x: 0, y: 0, zoom: 1 };
        this.initialDeposits = configData.deposits || [];
        this.region = configData.region || null;
        this.buildPanelData = configData.buildPanel || [];

        // Instance data from entities - now includes state as properties
        // NEW (2026-01): entitiesData can be either {entities: []} or just []
        this.entitiesData = Array.isArray(entitiesData) ? entitiesData : (entitiesData.entities || []);

        // Map tiles - now a dictionary {"x_y": landing_id}
        this.tilesData = tilesData.tiles || {};

        // Runtime state
        this.zoom = this.initialCameraPosition.zoom || 1;
        this.loadedEntities = new Map();

        // Performance monitoring (accessible via game.perfMonitor in console)
        this.perfMonitor = new PerformanceMonitor(60); // Average over 60 frames
        this.entityData = new Map();
        this.hoveredEntity = null;
        this.needsReload = false;
        this.lastReloadTime = 0;
        this.lastFpsTime = 0;
        this.frameCount = 0; // For FPS display (resets every second)
        this.gameTick = 0;   // For game logic (never resets)

        // Progress tracking
        this.progressCallbacks = [];

        // Modules (initialized in initModules())
        this.camera = null;
        this.input = null;
        this.buildPanel = null;
        this.resourcePanel = null;
        this.cameraInfo = null;
        this.controlsHint = null;
        this.buildingWindow = null;
        this.buildMode = null;
        this.normalMode = null;
        this.deleteMode = null;
        this.menuMode = null;
        this.fogOfWar = null;
        this.tileManager = null;
        this.depositManager = null;
        this.entityTooltip = null;
        this.buildingRules = null;
        this.resourceTransport = null;
        this.resourceRenderer = null;
        this.cloudManager = null;
        this.conveyorManager = null;
        this.gameModeManager = null;
        this.entityInfoWindow = null;
        this.technologyWindow = null;
        this.pipeSystemManager = null;
        this.pipeRenderer = null;
        this.pipeInletRenderer = null;
        this.pipeConnectionManager = null;
        this.electricityManager = null;
        this.electrificationLayer = null;
        this.noPowerIndicator = null;
        this.constructionManager = null;

        // Game data structure (for managers)
        this.gameData = {
            landings: this.landingTypes,
            entityTypes: this.entityTypes,
            region: this.region
        };

        // Legacy compatibility - managers may access these
        // TODO: Remove after all managers migrated to GraphicsEngine
        this.textures = {};  // Will be removed after migration
        this.app = graphics ? graphics.app : null;
    }

    /**
     * Initialize game
     * Data and textures are already loaded by bootstrap
     * Progress range: 75-100%
     */
    async init() {
        this.emitProgress(75, 'Initializing modules');
        console.log('[Game] 1/8 Initializing modules...');
        this.initModules();

        this.emitProgress(77, 'Creating layers');
        console.log('[Game] 2/8 Initializing layers...');
        this.initLayers();

        this.emitProgress(79, 'Setting up camera');
        console.log('[Game] 3/8 Initializing camera...');
        this.initCamera();

        this.emitProgress(81, 'Initializing systems');
        console.log('[Game] 4/8 Post-init modules...');
        await this.initModulesPost();

        this.emitProgress(85, 'Preparing textures');
        console.log('[Game] 5/8 Preparing entity textures...');
        this.prepareEntityTextures();

        this.emitProgress(87, 'Preparing icons');
        console.log('[Game] 5.3/8 Preparing icon data URLs from atlases...');
        this.prepareIconDataUrls();

        this.emitProgress(88, 'Loading special assets');
        console.log('[Game] 5.5/8 Preparing special textures...');
        await this.prepareSpecialTextures();

        this.emitProgress(91, 'Loading map');
        console.log('[Game] 6/8 Loading map tiles...');
        this.loadMapTiles();

        this.emitProgress(95, 'Loading entities');
        console.log('[Game] 7/8 Loading entities...');
        this.loadEntities();

        this.emitProgress(98, 'Starting game');
        console.log('[Game] 8/8 Starting game loop...');
        this.startGameLoop();

        this.emitProgress(100, 'Game ready');
        console.log('[Game] Init complete!');
    }

    /**
     * Initialize all game modules
     */
    initModules() {
        // Create mode instances first
        this.normalMode = new NormalMode(this);
        this.deleteMode = new DeleteMode(this);
        this.buildMode = new BuildMode(this);
        this.menuMode = new MenuMode(this);

        // Create GameModeManager (will access modes via this.game)
        this.gameModeManager = new GameModeManager(this);
        this.camera = new Camera(this);
        this.input = new InputManager(this);

        // UI modules
        this.buildPanel = new BuildPanel(this);
        this.resourcePanel = new ResourcePanel(this);
        this.cameraInfo = new CameraInfo(this);
        this.controlsHint = new ControlsHint(this);

        this.buildingWindow = new BuildingWindow(this);
        this.fogOfWar = new FogOfWar(this);
        this.tileManager = new TileLayerManager(this);
        this.depositManager = new DepositLayerManager(this);
        this.entityTooltip = new EntityTooltip(this);
        this.buildingRules = new BuildingRules(this);
        this.resourceTransport = new ResourceTransportManager(this);
        this.resourceRenderer = new ResourceRenderer(this);
        this.cloudManager = new CloudManager(this);
        this.conveyorManager = new ConveyorManager(this);
        this.entityInfoWindow = new EntityInfoWindow(this);
        this.technologyWindow = new TechnologyWindow(this);
        this.constructionManager = new ConstructionManager(this);
        this.pipeSystemManager = new PipeSystemManager(this);
        this.pipeRenderer = new PipeRenderer(this);
        this.pipeInletRenderer = new PipeInletRenderer(this);
        this.pipeConnectionManager = new PipeConnectionManager(this);
        this.electricityManager = new ElectricitySystemManager(this);
        this.electrificationLayer = new ElectrificationLayerManager(this);
        this.noPowerIndicator = new NoPowerIndicator(this);
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
        // Create containers via GraphicsEngine (no direct PIXI usage)
        this.worldContainer = this.graphics.createContainer({ sortableChildren: true });
        this.landingLayer = this.graphics.createContainer({ zIndex: 1, sortableChildren: true });
        this.entityLayer = this.graphics.createContainer({ zIndex: 2, eventMode: 'static', sortableChildren: true });

        this.worldContainer.addChild(this.landingLayer);
        this.worldContainer.addChild(this.entityLayer);

        // Add pipe inlet renderer layer (above entities)
        this.worldContainer.addChild(this.pipeInletRenderer.container);
        this.pipeInletRenderer.container.zIndex = 3;

        const stage = this.graphics.getStage();
        stage.addChild(this.worldContainer);

        // depositLayer will be added by depositManager.init() with z-index 1.6
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

        // Initialize game modes
        this.normalMode.init();
        this.deleteMode.init();
        this.buildMode.init();
        this.menuMode.init();

        this.buildingWindow.init();
        this.fogOfWar.init();
        this.depositManager.init();
        this.entityTooltip.init();
        this.entityInfoWindow.init();
        this.technologyWindow.init();

        if (this.cloudManager) {
            await this.cloudManager.init();
        }

        // Initialize electricity managers
        await this.electrificationLayer.init();
        await this.noPowerIndicator.init();

        this.buildPanel.refresh();

        // Create menu button
        this.createMenuButton();

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

        // Override landing ID constants from config (for ship building system)
        this.LANDING_SKY_ID = this.config.landingSkyId;
        this.LANDING_BRIDGE_ID = this.config.landingBridgeId;
        this.LANDING_ISLAND_EDGE_ID = this.config.landingIslandEdgeId;
        this.LANDING_SHIP_EDGE_ID = this.config.landingShipEdgeId;

        this.landingTypes = data.landing;
        this.entityTypes = data.entityTypes;  // Теперь содержит costs, recipes, behavior
        this.depositTypes = data.depositTypes || {};
        this.resources = data.resources || {};
        this.recipes = data.recipes || {};
        // УБРАЛИ: entityTypeRecipes, entityTypeCosts (теперь в entityTypes)
        // УБРАЛИ: eyeEntities (фильтруем из entities на клиенте)
        this.userResources = data.userResources || {};
        this.initialBuildPanel = data.buildPanel || [];
        this.initialDeposits = data.deposits || [];
        this.initialCameraPosition = data.cameraPosition || { x: 0, y: 0, zoom: 1 };
        this.initialEntityResources = data.entityResources || [];
        this.initialCraftingStates = data.craftingStates || [];
        this.initialTransportStates = data.transportStates || [];
        // УБРАЛИ: pipeSystems (теперь в /game/entities)
        this.electricitySystems = data.electricitySystems || {};

        // Load electricity systems data
        if (this.electricityManager && this.electricitySystems) {
            this.electricityManager.loadSystems(this.electricitySystems);
        }

        // Setup gameData structure for new atlas system
        this.gameData = {
            landings: this.landingTypes,
            entityTypes: this.entityTypes,
            region: data.region || null
        };

        // Initialize building rules (deprecated, now behaviors in entityTypes)
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
     * Prepare entity textures from atlases loaded by GraphicsEngine
     * Slices each entity atlas into textures for different states
     */
    prepareEntityTextures() {
        if (!this.entityTypes) {
            console.error('[Game] entityTypes is undefined in prepareEntityTextures');
            return;
        }

        if (!this.graphics) {
            console.error('[Game] graphics is undefined in prepareEntityTextures');
            return;
        }

        const { tileWidth, tileHeight } = this.config;

        for (const typeId in this.entityTypes) {
            const entityType = this.entityTypes[typeId];

            if (!entityType) {
                console.warn(`[Game] Entity type ${typeId} is undefined`);
                continue;
            }
            const width = entityType.width || 1;
            const height = entityType.height || 1;

            const pixelWidth = width * tileWidth;
            const pixelHeight = height * tileHeight;

            // Get atlas texture from GraphicsEngine (already loaded)
            const atlasKey = `entity_atlas_${typeId}`;
            const atlasTexture = this.graphics.getTexture(atlasKey);

            if (!atlasTexture) {
                console.warn(`[Game] Entity atlas not found: ${atlasKey}`);
                continue;
            }

            // Create textures for each state from atlas
            // Atlas row 1: [normal][damaged][blueprint][normal_selected][damaged_selected][deleting][crafting]
            // Atlas row 2: [construction_10][construction_20]...[construction_90]

            // Load all 7 sprites from row 1
            let xOffset = 0;
            for (const state of SPRITE_STATES) {
                const textureKey = `entity_${typeId}_${state}`;

                // Create texture from atlas region using GraphicsEngine
                const rect = this.graphics.createRectangle(xOffset, 0, pixelWidth, pixelHeight);
                this.textures[textureKey] = this.graphics.createTextureFromAtlas(atlasKey, rect);

                xOffset += pixelWidth;
            }

            // Load 9 construction frames from row 2
            xOffset = 0;
            const yOffset = pixelHeight; // Second row
            for (const progress of CONSTRUCTION_FRAMES) {
                const textureKey = `entity_${typeId}_construction_${progress}`;

                const rect = this.graphics.createRectangle(xOffset, yOffset, pixelWidth, pixelHeight);
                this.textures[textureKey] = this.graphics.createTextureFromAtlas(atlasKey, rect);

                xOffset += pixelWidth;
            }
        }

        console.log(`[Game] Prepared textures for ${Object.keys(this.entityTypes).length} entity types`);
    }

    /**
     * Prepare icon data URLs from atlas textures
     * Creates data URLs from normal state textures for use in UI
     */
    prepareIconDataUrls() {
        this.iconDataUrls = {};

        for (const typeId in this.entityTypes) {
            const textureKey = `entity_${typeId}_normal`;
            const texture = this.textures[textureKey];

            if (!texture) {
                console.warn(`[Game] Texture not found for icon: ${textureKey}`);
                continue;
            }

            // Create canvas and render texture to it
            const canvas = document.createElement('canvas');
            const entityType = this.entityTypes[typeId];
            const width = (entityType.width || 1) * this.config.tileWidth;
            const height = (entityType.height || 1) * this.config.tileHeight;

            canvas.width = width;
            canvas.height = height;

            const ctx = canvas.getContext('2d');

            // Get base texture source (Image or Canvas)
            const source = texture.baseTexture.resource.source;

            // Draw the texture region to canvas
            const frame = texture.frame;
            ctx.drawImage(
                source,
                frame.x, frame.y, frame.width, frame.height,
                0, 0, width, height
            );

            // Convert to data URL and cache
            this.iconDataUrls[typeId] = canvas.toDataURL('image/png');
        }

        console.log(`[Game] Prepared ${Object.keys(this.iconDataUrls).length} icon data URLs from atlases`);
    }

    /**
     * Prepare special textures (conveyors, pipes) that need atlas organization
     */
    async prepareSpecialTextures() {
        // Conveyors: organize textures by orientation and state
        await this.conveyorManager.loadAtlases();

        // Pipes: load variant atlases (separate from entity atlases)
        await this.pipeConnectionManager.loadVariantTextures();
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
     * Update viewport-dependent rendering (electrification, fog, visibility)
     * Called when camera moves or eye entity is deleted
     */
    updateViewport() {
        // Render electrification layer when viewport changes
        if (this.electrificationLayer) {
            this.electrificationLayer.render();
        }

        const viewport = this.calculateViewport();

        // Update entity visibility based on viewport
        this.updateEntityVisibility();

        // Re-render fog of war
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
     * Load map tiles (data already loaded by GameLoader)
     */
    loadMapTiles() {
        // tilesData is an object (dictionary), not an array
        const tilesCount = Object.keys(this.tilesData).length;
        if (this.tilesData && tilesCount > 0) {
            this.tileManager.storeTileData(this.tilesData);
            this.tileManager.renderTiles(this.tilesData);
            console.log(`[Game] Loaded ${tilesCount} tiles`);
        }
    }

    /**
     * Load entities (data already loaded by GameLoader)
     */
    loadEntities() {
        // Calculate pipe systems locally (NEW: BFS instead of server loading)
        this.pipeSystemManager.calculateSystems();

        // Filter eye entities (entities with type='eye')
        this.initialEyeEntities = this.entitiesData.filter(e => {
            const type = this.entityTypes[e.entity_type_id];
            return type && type.type === 'eye';
        });

        // Render all entities
        this.renderEntities(this.entitiesData);

        // Initialize resource transport after entities are loaded
        this.resourceTransport.init();

        // Initialize resource renderer (visual layer for resources on conveyors/manipulators)
        this.resourceRenderer.init();

        console.log(`[Game] Loaded ${this.entitiesData.length} entities`);
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
            const folder = entityType?.folder || '';

            // Check entity types
            const isPipeEntity = this.pipeConnectionManager.isPipe(entity);
            const isConveyor = this.conveyorManager.isConveyor(entity);
            const hasAnimation = this.conveyorManager.hasAnimationAtlas(entity);

            // Handle animated conveyors with atlas support (but exclude pipes)
            if (isConveyor && hasAnimation && !isPipeEntity) {
                const texture = this.conveyorManager.getConveyorTexture(entity, false, 0);
                if (texture) {
                    const sprite = this.createEntitySprite(entity, texture, isVisible);
                    this.entityLayer.addChild(sprite);
                    this.loadedEntities.set(key, sprite);
                    this.conveyorManager.registerConveyor(entity.entity_id, sprite);
                }
            } else if (isPipeEntity) {
                // Handle pipes with connection variants and fluid visualization
                const fallbackTexture = this.textures[`entity_${entity.entity_type_id}_normal`];
                if (fallbackTexture) {
                    const container = this.pipeRenderer.createPipeContainer(entity, fallbackTexture, isVisible);
                    this.entityLayer.addChild(container);
                    this.loadedEntities.set(key, container);
                    this.pipeConnectionManager.registerPipe(entity.entity_id, container);
                }
            } else if (isConveyor && !isPipeEntity) {
                // Handle non-animated conveyors (dual, fast, etc.) - use regular texture but register for spatial index
                const textureKey = this.getEntityTextureKey(entity, false);
                const texture = this.textures[textureKey];

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

        // Update all connections after all entities are loaded
        if (this.conveyorManager) {
            this.conveyorManager.updateAllConnections();
        }
        if (this.pipeConnectionManager) {
            this.pipeConnectionManager.updateAllConnections();
        }
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
        const folder = entityType?.folder || '';

        // Check if this is a pipe entity with connection variants
        const isPipeEntity = this.pipeConnectionManager.isPipe(entity);

        // Get hover sprite type based on current game mode
        const hoverSpriteType = this.gameModeManager.getHoverSpriteType();

        // Check if this is an animated conveyor
        const isAnimatedConveyor = this.conveyorManager.isConveyor(entity);

        if (isHovering && hoverSpriteType) {
            // Handle animated conveyors separately (but exclude pipes and underground)
            if (isAnimatedConveyor && !isPipeEntity) {
                this.conveyorManager.updateConveyorTexture(entity.entity_id, true);
            } else if (isPipeEntity) {
                // Handle pipes - switch to selected texture
                if (hoverSpriteType === 'deleting') {
                    // For delete mode, use tint (no _deleting atlas)
                    const pipeSprite = sprite.children ? sprite.children[0] : sprite;
                    if (pipeSprite) {
                        pipeSprite.tint = 0xff6666; // Red tint for delete
                    }
                } else {
                    // For normal hover, use _selected atlas
                    this.pipeConnectionManager.updatePipeTexture(entity.entity_id, true);
                }
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
            if (isAnimatedConveyor && !isPipeEntity) {
                this.conveyorManager.updateConveyorTexture(entity.entity_id, false);
            } else if (isPipeEntity) {
                // Handle pipes - reset to normal texture
                this.pipeConnectionManager.updatePipeTexture(entity.entity_id, false);
                // Also reset tint in case it was in delete mode
                const pipeSprite = sprite.children ? sprite.children[0] : sprite;
                if (pipeSprite) {
                    pipeSprite.tint = 0xffffff;
                }
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
     * Handle deposit hover (show/hide tooltip)
     */
    onDepositHover(sprite, isHovering, event) {
        // Show/hide tooltip based on game mode (use entityTooltip for deposits too)
        if (isHovering && this.entityTooltip && this.gameModeManager.shouldShowTooltip()) {
            const screenX = event.global.x;
            const screenY = event.global.y;
            this.entityTooltip.show(sprite.depositData, screenX, screenY);
        } else if (this.entityTooltip) {
            this.entityTooltip.hide();
        }
    }

    /**
     * Handle deposit mouse move (update tooltip position)
     */
    onDepositMove(event) {
        if (this.entityTooltip && this.entityTooltip.isVisible) {
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
        const entityType = this.entityTypes[entity.entity_type_id];

        // Handle different game modes
        if (mode.isMode(GameMode.DELETE)) {
            // Delete mode - delete entity
            this.deleteEntity(entity);
        } else if (mode.isMode(GameMode.NORMAL)) {
            // HQ - open technology window
            if (entityType && entityType.type === 'hq') {
                mode.switchMode(GameMode.TECHNOLOGY_WINDOW);
            } else {
                // Normal mode - open entity info window
                mode.switchMode(GameMode.ENTITY_INFO, { entityId: entity.entity_id });
            }
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
                const entityType = this.entityTypes[entity.entity_type_id];

                this.entityData.delete(key);
                const sprite = this.loadedEntities.get(key);
                if (sprite) {
                    this.entityLayer.removeChild(sprite);
                    sprite.destroy();
                    this.loadedEntities.delete(key);
                }

                // Unregister conveyor and update connections
                if (this.conveyorManager && entityType && entityType.type === 'conveyor') {
                    this.conveyorManager.unregisterConveyor(entity.entity_id);
                    this.conveyorManager.updateAllConnections();
                }

                // Remove from resource transport system
                if (this.resourceTransport) {
                    this.resourceTransport.onEntityRemoved(entity.entity_id);
                }

                // Update fog of war if it was an eye entity
                if (this.fogOfWar && entityType && entityType.type === 'eye') {
                    this.fogOfWar.removeEyeEntity(entity.entity_id);
                    this.updateViewport();
                }

                // Invalidate electricity network cache if electricity entity was deleted
                if (this.electricityManager && entityType && entityType.type === 'electricity') {
                    this.electricityManager.invalidateNetworkCache();
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
        try {
            // Increment game tick (never resets)
            this.gameTick++;

            this.perfMonitor.start('camera');
            const moved = this.camera.update();
            this.camera.apply();
            this.perfMonitor.end('camera');

            this.perfMonitor.start('cloudParallax');
            if (this.cloudManager) {
                this.cloudManager.applyParallax();
            }
            this.perfMonitor.end('cloudParallax');

            if (moved) {
                this.needsReload = true;
            }

            const now = performance.now();
            if (this.needsReload && now - this.lastReloadTime > VIEWPORT_RELOAD_INTERVAL) {
                this.perfMonitor.start('viewport');
                this.updateViewport();
                this.perfMonitor.end('viewport');
                this.needsReload = false;
                this.lastReloadTime = now;
            }

            // === EVERY FRAME UPDATES (60 fps) ===

            // Update UI (every frame)
            this.perfMonitor.start('cameraInfo');
            this.cameraInfo.update();
            this.perfMonitor.end('cameraInfo');

            // Tick resource transport system (every frame for animation, heavy logic every 30 ticks internally)
            this.perfMonitor.start('resourceTransport');
            this.resourceTransport.tick();
            this.perfMonitor.end('resourceTransport');

            // Render resource sprites on conveyors/manipulators (every frame)
            this.perfMonitor.start('resourceRenderer');
            this.resourceRenderer.render();
            this.perfMonitor.end('resourceRenderer');

            // Update cloud positions (every frame)
            this.perfMonitor.start('cloudManager');
            if (this.cloudManager) {
                this.cloudManager.update();
            }
            this.perfMonitor.end('cloudManager');

            // Update conveyor animations (every frame)
            this.perfMonitor.start('conveyorManager');
            if (this.conveyorManager) {
                this.conveyorManager.update();
            }
            this.perfMonitor.end('conveyorManager');

            // Update construction progress (every frame)
            this.perfMonitor.start('constructionManager');
            if (this.constructionManager) {
                this.constructionManager.update();
            }
            this.perfMonitor.end('constructionManager');

            // === PERIODIC UPDATES (lower frequency) ===

            // Update electricity indicators (periodic, every 60 frames = ~1 second)
            this.perfMonitor.start('noPowerIndicator');
            if (this.noPowerIndicator && this.gameTick % 60 === 0) {
                this.noPowerIndicator.update();
            }
            this.perfMonitor.end('noPowerIndicator');

            // Update pipe inlet sprites (periodic, every 30 frames = ~0.5 second)
            this.perfMonitor.start('pipeInletRenderer');
            if (this.pipeInletRenderer && this.gameTick % 30 === 0) {
                this.pipeInletRenderer.update();
            }
            this.perfMonitor.end('pipeInletRenderer');

            this.updateDebug('camera', `${Math.round(this.camera.x)}, ${Math.round(this.camera.y)}`);
            this.updateFPS(now);
        } catch (error) {
            console.error('[GameLoop] Error:', error);
        }
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

    /**
     * Register progress callback
     * @param {Function} callback - callback({ percent, message })
     */
    onProgress(callback) {
        this.progressCallbacks.push(callback);
    }

    /**
     * Emit progress to all registered callbacks
     * @private
     */
    emitProgress(percent, message) {
        this.progressCallbacks.forEach(cb => cb({ percent, message }));
    }

    /**
     * Start profiling managers performance
     * Resets perfMonitor and starts collecting data
     */
    startProfiling() {
        console.log('[Game] Starting profiling...');

        // Reset performance monitor to collect fresh data
        if (this.perfMonitor) {
            this.perfMonitor.reset();
            this.perfMonitor.setEnabled(true);
        }

        this.profiling = {
            active: true,
            startTime: performance.now()
        };
    }

    /**
     * Stop profiling
     */
    stopProfiling() {
        if (this.profiling) {
            this.profiling.active = false;
            console.log('[Game] Profiling stopped');
        }
    }

    /**
     * Get profiling results
     * Uses PerformanceMonitor stats
     * @returns {Array} Array of manager stats sorted by average time
     */
    getProfilingResults() {
        if (!this.perfMonitor) {
            return [];
        }

        // Get stats from PerformanceMonitor
        const stats = this.perfMonitor.getStats();

        // Convert to format expected by MenuMode
        return stats.map(s => ({
            manager: s.name,
            avgTime: parseFloat(s.avg),
            totalTime: parseFloat(s.total),
            calls: s.count
        }));
    }

    /**
     * Create menu button in top-right corner
     */
    createMenuButton() {
        const menuButton = document.createElement('button');
        menuButton.id = 'menu-button';
        menuButton.innerHTML = '☰';
        menuButton.title = 'Open Menu (ESC)';

        menuButton.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border: 2px solid #4a90e2;
            border-radius: 8px;
            background: rgba(20, 20, 30, 0.9);
            color: #4a90e2;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1000;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        `;

        menuButton.addEventListener('mouseenter', () => {
            menuButton.style.background = 'rgba(74, 144, 226, 0.2)';
            menuButton.style.transform = 'scale(1.05)';
        });

        menuButton.addEventListener('mouseleave', () => {
            menuButton.style.background = 'rgba(20, 20, 30, 0.9)';
            menuButton.style.transform = 'scale(1)';
        });

        menuButton.addEventListener('click', () => {
            const isAdmin = this.region?.is_admin || false;
            this.gameModeManager.switchMode(GameMode.MENU, { isAdmin });
        });

        document.body.appendChild(menuButton);
        console.log('[Game] Menu button created');
    }

}

// REMOVED (2026-01-15): Game initialization moved to bootstrap.js
// Old code tried to create Game with single parameter (configUrl),
// but constructor now expects 4 parameters: (configData, entitiesData, tilesData, graphics)
// Bootstrap.js handles proper initialization with GameLoader and GraphicsEngine

export default ZFactoryGame;
