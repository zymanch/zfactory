import * as PIXI from 'pixi.js';

/**
 * GraphicsEngine - Single point of interaction with graphics engine (PixiJS)
 *
 * This class abstracts ALL PixiJS interactions, allowing:
 * - Easy testing with FakeGraphicsEngine
 * - Potential migration to different graphics library
 * - Centralized asset loading with progress tracking
 * - Clean separation between game logic and rendering
 */
export class GraphicsEngine {
    constructor(assetManifest = {}, options = {}) {
        this.manifest = assetManifest;
        this.options = {
            width: options.width || 800,
            height: options.height || 600,
            backgroundColor: options.backgroundColor || 0x000000,
            ...options
        };

        this.app = null;
        this.textures = new Map(); // key => PIXI.Texture
        this.progressCallbacks = [];
    }

    /**
     * Initialize PixiJS application
     * @param {HTMLElement} containerElement - DOM element to append canvas to
     */
    async initApplication(containerElement) {
        this.app = new PIXI.Application();

        await this.app.init({
            width: this.options.width,
            height: this.options.height,
            backgroundColor: this.options.backgroundColor,
            resolution: window.devicePixelRatio || 1,
            autoDensity: true,
            antialias: true
        });

        // Normal FPS (60) - remove this line to use browser's native FPS
        // this.app.ticker.maxFPS = 60;

        if (containerElement) {
            containerElement.appendChild(this.app.canvas);
        }

        console.log('[GraphicsEngine] Application initialized (FPS: ' + this.app.ticker.maxFPS + ')');
        return { canvas: this.app.canvas, stage: this.app.stage };
    }

    /**
     * Load all textures from asset manifest
     * Emits progress events via onProgress callbacks
     * Progress range: 15-75% (60% of total loading)
     */
    async loadAllTextures() {
        const keys = Object.keys(this.manifest);
        const total = keys.length;
        let loaded = 0;

        console.log(`[GraphicsEngine] Loading ${total} assets...`);

        for (const key of keys) {
            const url = this.manifest[key];

            try {
                const texture = await PIXI.Assets.load(url);
                this.textures.set(key, texture);
                loaded++;

                // Emit progress (15% to 75% range)
                const assetPercent = (loaded / total) * 60; // 60% of total progress
                const totalPercent = 15 + assetPercent;

                const progress = {
                    loaded,
                    total,
                    percent: Math.round(totalPercent),
                    message: `Loading assets... (${loaded}/${total})`
                };
                this.emitProgress(progress);

            } catch (error) {
                console.warn(`[GraphicsEngine] Failed to load texture: ${key} (${url})`, error);
                loaded++;

                const assetPercent = (loaded / total) * 60;
                const totalPercent = 15 + assetPercent;

                this.emitProgress({
                    loaded,
                    total,
                    percent: Math.round(totalPercent),
                    message: `Loading assets... (${loaded}/${total})`
                });
            }
        }

        console.log(`[GraphicsEngine] Loaded ${loaded}/${total} assets`);
    }

    /**
     * Register progress callback
     * @param {Function} callback - callback({ loaded, total, percent, currentKey })
     */
    onProgress(callback) {
        this.progressCallbacks.push(callback);
    }

    /**
     * Emit progress to all registered callbacks
     * @private
     */
    emitProgress(progress) {
        this.progressCallbacks.forEach(cb => cb(progress));
    }

    /**
     * Get loaded texture by key
     * @param {string} key - Texture key from manifest
     * @returns {PIXI.Texture|null}
     */
    getTexture(key) {
        return this.textures.get(key) || null;
    }

    /**
     * Create container
     * @param {Object} options - Container options
     * @returns {PIXI.Container}
     */
    createContainer(options = {}) {
        const container = new PIXI.Container();

        if (options.sortableChildren !== undefined) {
            container.sortableChildren = options.sortableChildren;
        }
        if (options.zIndex !== undefined) {
            container.zIndex = options.zIndex;
        }
        if (options.eventMode !== undefined) {
            container.eventMode = options.eventMode;
        }
        if (options.visible !== undefined) {
            container.visible = options.visible;
        }

        return container;
    }

    /**
     * Create sprite from texture key
     * @param {string|PIXI.Texture} textureKeyOrTexture - Texture key or PIXI.Texture instance
     * @param {Object} options - Sprite options
     * @returns {PIXI.Sprite}
     */
    createSprite(textureKeyOrTexture, options = {}) {
        let texture;

        if (typeof textureKeyOrTexture === 'string') {
            texture = this.getTexture(textureKeyOrTexture);
            if (!texture) {
                console.warn(`[GraphicsEngine] Texture not found: ${textureKeyOrTexture}`);
                texture = PIXI.Texture.EMPTY;
            }
        } else {
            texture = textureKeyOrTexture;
        }

        const sprite = new PIXI.Sprite(texture);

        // Apply options
        if (options.x !== undefined) sprite.x = options.x;
        if (options.y !== undefined) sprite.y = options.y;
        if (options.width !== undefined) sprite.width = options.width;
        if (options.height !== undefined) sprite.height = options.height;
        if (options.visible !== undefined) sprite.visible = options.visible;
        if (options.alpha !== undefined) sprite.alpha = options.alpha;
        if (options.rotation !== undefined) sprite.rotation = options.rotation;
        if (options.eventMode !== undefined) sprite.eventMode = options.eventMode;
        if (options.cursor !== undefined) sprite.cursor = options.cursor;
        if (options.zIndex !== undefined) sprite.zIndex = options.zIndex;

        if (options.anchor !== undefined) {
            if (typeof options.anchor === 'number') {
                sprite.anchor.set(options.anchor);
            } else if (options.anchor.x !== undefined && options.anchor.y !== undefined) {
                sprite.anchor.set(options.anchor.x, options.anchor.y);
            }
        }

        if (options.scale !== undefined) {
            if (typeof options.scale === 'number') {
                sprite.scale.set(options.scale);
            } else if (options.scale.x !== undefined && options.scale.y !== undefined) {
                sprite.scale.set(options.scale.x, options.scale.y);
            }
        }

        if (options.tint !== undefined) {
            sprite.tint = options.tint;
        }

        return sprite;
    }

    /**
     * Create graphics object
     * @returns {PIXI.Graphics}
     */
    createGraphics() {
        return new PIXI.Graphics();
    }

    /**
     * Create text object
     * @param {string} text - Text content
     * @param {Object} style - Text style options
     * @returns {PIXI.Text}
     */
    createText(text, style = {}) {
        return new PIXI.Text(text, style);
    }

    /**
     * Create texture from atlas
     * @param {string|PIXI.Texture} atlasKeyOrTexture - Atlas texture key or texture instance
     * @param {Object} rect - Rectangle { x, y, width, height }
     * @returns {PIXI.Texture|null}
     */
    createTextureFromAtlas(atlasKeyOrTexture, rect) {
        let atlasTexture;

        if (typeof atlasKeyOrTexture === 'string') {
            atlasTexture = this.getTexture(atlasKeyOrTexture);
            if (!atlasTexture) {
                console.warn(`[GraphicsEngine] Atlas texture not found: ${atlasKeyOrTexture}`);
                return null;
            }
        } else {
            atlasTexture = atlasKeyOrTexture;
        }

        const frame = new PIXI.Rectangle(rect.x, rect.y, rect.width, rect.height);
        return new PIXI.Texture({
            source: atlasTexture.source,
            frame: frame
        });
    }

    /**
     * Create rectangle (PIXI.Rectangle utility)
     * @param {number} x
     * @param {number} y
     * @param {number} width
     * @param {number} height
     * @returns {PIXI.Rectangle}
     */
    createRectangle(x, y, width, height) {
        return new PIXI.Rectangle(x, y, width, height);
    }

    /**
     * Get canvas element
     * @returns {HTMLCanvasElement}
     */
    getCanvas() {
        return this.app ? this.app.canvas : null;
    }

    /**
     * Get stage (root container)
     * @returns {PIXI.Container}
     */
    getStage() {
        return this.app ? this.app.stage : null;
    }

    /**
     * Get ticker (game loop)
     * @returns {PIXI.Ticker}
     */
    getTicker() {
        return this.app ? this.app.ticker : null;
    }

    /**
     * Destroy application and cleanup
     */
    destroy() {
        if (this.app) {
            this.app.destroy(true, { children: true, texture: true, baseTexture: true });
            this.app = null;
        }

        this.textures.clear();
        this.progressCallbacks = [];
        console.log('[GraphicsEngine] Destroyed');
    }
}
