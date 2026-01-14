/**
 * Mock PixiJS Objects
 *
 * Provides mocks for PixiJS classes to avoid loading the full library in tests.
 */

import { vi } from 'vitest';

/**
 * Mock PIXI.Sprite
 */
export class MockSprite {
    constructor(texture) {
        this.texture = texture;
        this.x = 0;
        this.y = 0;
        this.width = 64;
        this.height = 64;
        this.anchor = { x: 0.5, y: 0.5 };
        this.scale = { x: 1, y: 1 };
        this.rotation = 0;
        this.visible = true;
        this.alpha = 1;
        this.tint = 0xFFFFFF;
        this.children = [];
        this.parent = null;

        this.addChild = vi.fn((child) => {
            this.children.push(child);
            child.parent = this;
        });

        this.removeChild = vi.fn((child) => {
            const index = this.children.indexOf(child);
            if (index !== -1) {
                this.children.splice(index, 1);
                child.parent = null;
            }
        });

        this.destroy = vi.fn();
    }
}

/**
 * Mock PIXI.Container
 */
export class MockContainer {
    constructor() {
        this.x = 0;
        this.y = 0;
        this.width = 0;
        this.height = 0;
        this.visible = true;
        this.alpha = 1;
        this.children = [];
        this.parent = null;

        this.addChild = vi.fn((child) => {
            this.children.push(child);
            child.parent = this;
        });

        this.removeChild = vi.fn((child) => {
            const index = this.children.indexOf(child);
            if (index !== -1) {
                this.children.splice(index, 1);
                child.parent = null;
            }
        });

        this.removeChildren = vi.fn(() => {
            this.children.forEach(child => {
                child.parent = null;
            });
            this.children = [];
        });

        this.destroy = vi.fn();
    }
}

/**
 * Mock PIXI.Texture
 */
export class MockTexture {
    static from(source) {
        return new MockTexture(source);
    }

    constructor(source) {
        this.source = source;
        this.width = 64;
        this.height = 64;
    }

    destroy() {}
}

/**
 * Mock PIXI.Graphics
 */
export class MockGraphics extends MockContainer {
    constructor() {
        super();
        this.lineStyle = vi.fn(() => this);
        this.beginFill = vi.fn(() => this);
        this.endFill = vi.fn(() => this);
        this.drawRect = vi.fn(() => this);
        this.drawCircle = vi.fn(() => this);
        this.drawPolygon = vi.fn(() => this);
        this.clear = vi.fn(() => this);
        this.moveTo = vi.fn(() => this);
        this.lineTo = vi.fn(() => this);
    }
}

/**
 * Mock PIXI.Text
 */
export class MockText extends MockSprite {
    constructor(text, style) {
        super(null);
        this.text = text;
        this.style = style || {};
    }
}

/**
 * Mock PIXI namespace
 */
export const MockPIXI = {
    Sprite: MockSprite,
    Container: MockContainer,
    Texture: MockTexture,
    Graphics: MockGraphics,
    Text: MockText,

    // Color utilities
    utils: {
        hex2rgb: vi.fn((hex) => [1, 1, 1]),
        rgb2hex: vi.fn((rgb) => 0xFFFFFF)
    }
};

/**
 * Setup global PIXI mock
 * Call this in tests that need PixiJS
 */
export function setupPixiMock() {
    global.PIXI = MockPIXI;
}

/**
 * Cleanup global PIXI mock
 */
export function cleanupPixiMock() {
    delete global.PIXI;
}
