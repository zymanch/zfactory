import { vi } from 'vitest';

/**
 * FakeGraphicsEngine - Test double for GraphicsEngine
 *
 * Provides same API as GraphicsEngine but all methods return minimal mocks.
 * Used in tests to run game logic without PixiJS rendering.
 */
export class FakeGraphicsEngine {
    constructor(assetManifest = {}, options = {}) {
        this.manifest = assetManifest;
        this.options = options;
        this.textures = new Map();
        this.progressCallbacks = [];

        // Mock app structure
        this.app = {
            canvas: {},
            stage: this.createContainer(),
            ticker: {
                add: vi.fn(),
                remove: vi.fn(),
                start: vi.fn(),
                stop: vi.fn()
            },
            renderer: {
                width: 1280,
                height: 960
            }
        };
    }

    /**
     * Mock: Initialize application
     */
    async initApplication(containerElement) {
        // No-op - just return mock structure
        return {
            canvas: this.app.canvas,
            stage: this.app.stage
        };
    }

    /**
     * Mock: Load all textures (instant, no actual loading)
     */
    async loadAllTextures() {
        const keys = Object.keys(this.manifest);
        const total = keys.length;

        // Instantly "load" all textures
        keys.forEach((key, index) => {
            this.textures.set(key, { width: 64, height: 64 }); // Mock texture

            // Emit progress
            const loaded = index + 1;
            const progress = {
                loaded,
                total,
                percent: Math.round((loaded / total) * 100),
                currentKey: key
            };
            this.emitProgress(progress);
        });
    }

    /**
     * Register progress callback
     */
    onProgress(callback) {
        this.progressCallbacks.push(callback);
    }

    /**
     * Emit progress
     * @private
     */
    emitProgress(progress) {
        this.progressCallbacks.forEach(cb => cb(progress));
    }

    /**
     * Mock: Get texture
     */
    getTexture(key) {
        if (this.textures.has(key)) {
            return this.textures.get(key);
        }
        // Return mock texture even if not loaded
        return { width: 64, height: 64 };
    }

    /**
     * Mock: Create container
     */
    createContainer(options = {}) {
        return {
            x: 0,
            y: 0,
            width: 0,
            height: 0,
            children: [],
            visible: true,
            alpha: 1,
            rotation: 0,
            scale: { x: 1, y: 1 },
            zIndex: options.zIndex || 0,
            sortableChildren: options.sortableChildren || false,
            eventMode: options.eventMode || 'auto',

            // Mock methods
            addChild: vi.fn(function(child) {
                this.children.push(child);
                return child;
            }),
            removeChild: vi.fn(function(child) {
                const index = this.children.indexOf(child);
                if (index !== -1) {
                    this.children.splice(index, 1);
                }
                return child;
            }),
            removeChildren: vi.fn(function() {
                this.children = [];
            }),
            getChildIndex: vi.fn(() => 0),
            addChildAt: vi.fn(function(child, index) {
                this.children.splice(index, 0, child);
                return child;
            }),
            destroy: vi.fn()
        };
    }

    /**
     * Mock: Create sprite
     */
    createSprite(textureKeyOrTexture, options = {}) {
        const texture = typeof textureKeyOrTexture === 'string'
            ? this.getTexture(textureKeyOrTexture)
            : textureKeyOrTexture;

        return {
            texture: texture,
            x: options.x || 0,
            y: options.y || 0,
            width: options.width || 64,
            height: options.height || 64,
            visible: options.visible !== undefined ? options.visible : true,
            alpha: options.alpha !== undefined ? options.alpha : 1,
            rotation: options.rotation || 0,
            zIndex: options.zIndex || 0,
            tint: options.tint || 0xFFFFFF,
            cursor: options.cursor || 'default',
            eventMode: options.eventMode || 'auto',

            anchor: {
                x: 0.5,
                y: 0.5,
                set: vi.fn(function(x, y) {
                    this.x = y !== undefined ? x : x;
                    this.y = y !== undefined ? y : x;
                })
            },

            scale: {
                x: 1,
                y: 1,
                set: vi.fn(function(x, y) {
                    this.x = y !== undefined ? x : x;
                    this.y = y !== undefined ? y : x;
                })
            },

            // Mock methods
            destroy: vi.fn(),
            on: vi.fn(),
            off: vi.fn(),
            emit: vi.fn()
        };
    }

    /**
     * Mock: Create graphics
     */
    createGraphics() {
        return {
            x: 0,
            y: 0,
            width: 0,
            height: 0,
            visible: true,
            alpha: 1,
            zIndex: 0,

            // Graphics methods
            clear: vi.fn(),
            beginFill: vi.fn(),
            endFill: vi.fn(),
            drawRect: vi.fn(),
            drawCircle: vi.fn(),
            drawPolygon: vi.fn(),
            lineStyle: vi.fn(),
            moveTo: vi.fn(),
            lineTo: vi.fn(),

            destroy: vi.fn()
        };
    }

    /**
     * Mock: Create texture from atlas
     */
    createTextureFromAtlas(atlasKey, rect) {
        return {
            width: rect.width || 64,
            height: rect.height || 64
        };
    }

    /**
     * Mock: Create rectangle
     */
    createRectangle(x, y, width, height) {
        return { x, y, width, height };
    }

    /**
     * Get canvas (mock)
     */
    getCanvas() {
        return this.app.canvas;
    }

    /**
     * Get stage (mock)
     */
    getStage() {
        return this.app.stage;
    }

    /**
     * Get ticker (mock)
     */
    getTicker() {
        return this.app.ticker;
    }

    /**
     * Mock: Destroy (no-op)
     */
    destroy() {
        this.textures.clear();
        this.progressCallbacks = [];
    }
}
