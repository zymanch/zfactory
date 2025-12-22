import * as PIXI from 'pixi.js';

/**
 * ResourceRenderer - Renders resources on conveyors and manipulators
 */
export class ResourceRenderer {
    constructor(game) {
        this.game = game;
        this.resourceTextures = new Map();  // resource_id -> PIXI.Texture
        this.resourceSprites = new Map();   // entity_id -> PIXI.Sprite
        this.container = null;
        this.initialized = false;
    }

    /**
     * Initialize the renderer
     */
    async init() {
        this.container = new PIXI.Container();
        this.container.sortableChildren = true;

        // Insert after entity layer
        const entityLayerIndex = this.game.worldContainer.getChildIndex(this.game.entityLayer);
        this.game.worldContainer.addChildAt(this.container, entityLayerIndex + 1);

        await this.loadResourceTextures();
        this.initialized = true;
    }

    /**
     * Load resource icon textures
     */
    async loadResourceTextures() {
        const v = this.game.config.assetVersion || 1;

        for (const resourceId in this.game.resources) {
            const resource = this.game.resources[resourceId];
            if (!resource.icon_url) continue;

            const url = `/assets/tiles/resources/${resource.icon_url}?v=${v}`;
            try {
                const texture = await PIXI.Assets.load(url);
                this.resourceTextures.set(parseInt(resourceId), texture);
            } catch (e) {
                console.warn('Failed to load resource texture:', url);
            }
        }
    }

    /**
     * Main render function - called every frame
     */
    render() {
        if (!this.initialized) return;

        const rt = this.game.resourceTransport;
        if (!rt || !rt.initialized) return;

        // Track which sprites are still needed
        const neededSprites = new Set();

        // Render conveyor resources
        for (const [entityId, state] of rt.transporters) {
            if (state.resourceId) {
                neededSprites.add(entityId);
                this.renderConveyorResource(entityId, state);
            }
        }

        // Render manipulator resources
        for (const [entityId, state] of rt.manipulators) {
            if (state.resourceId) {
                neededSprites.add(entityId);
                this.renderManipulatorResource(entityId, state);
            }
        }

        // Remove sprites for entities that no longer have resources
        for (const [entityId, sprite] of this.resourceSprites) {
            if (!neededSprites.has(entityId)) {
                this.container.removeChild(sprite);
                this.resourceSprites.delete(entityId);
            }
        }
    }

    /**
     * Render resource on conveyor belt
     */
    renderConveyorResource(entityId, state) {
        const texture = this.resourceTextures.get(state.resourceId);
        if (!texture) return;

        let sprite = this.resourceSprites.get(entityId);
        if (!sprite) {
            sprite = new PIXI.Sprite(texture);
            sprite.anchor.set(0.5, 0.5);
            sprite.scale.set(0.5, 0.5);  // Scale down resource icon
            this.container.addChild(sprite);
            this.resourceSprites.set(entityId, sprite);
        } else if (sprite.texture !== texture) {
            sprite.texture = texture;
        }

        // Calculate position on belt
        const { tileWidth, tileHeight } = this.game.config;
        const centerX = state.x * tileWidth + tileWidth / 2;
        const centerY = state.y * tileHeight + tileHeight / 2;

        // Movement progress: 0 = entry edge, 0.5 = center, 1 = exit edge
        const progress = state.resourcePosition - 0.5;  // -0.5 to 0.5

        // lateralOffset: -0.5 to 0.5, represents perpendicular offset from belt center
        let offsetX = 0;
        let offsetY = 0;

        switch (state.orientation) {
            case 'right':
                // Movement along X (use tileWidth), lateral offset along Y
                offsetX = progress * tileWidth;
                offsetY = state.lateralOffset * tileHeight;
                break;
            case 'left':
                // Movement along -X (use tileWidth), lateral offset along Y
                offsetX = -progress * tileWidth;
                offsetY = state.lateralOffset * tileHeight;
                break;
            case 'up':
                // Movement along -Y (use tileHeight), lateral offset along X
                offsetY = -progress * tileHeight;
                offsetX = state.lateralOffset * tileWidth;
                break;
            case 'down':
                // Movement along Y (use tileHeight), lateral offset along X
                offsetY = progress * tileHeight;
                offsetX = state.lateralOffset * tileWidth;
                break;
        }

        sprite.x = centerX + offsetX;
        sprite.y = centerY + offsetY;
        sprite.zIndex = centerY + tileHeight;  // Slightly above entity
    }

    /**
     * Render resource on manipulator arm
     */
    renderManipulatorResource(entityId, state) {
        const texture = this.resourceTextures.get(state.resourceId);
        if (!texture) return;

        let sprite = this.resourceSprites.get(entityId);
        if (!sprite) {
            sprite = new PIXI.Sprite(texture);
            sprite.anchor.set(0.5, 0.5);
            sprite.scale.set(0.5, 0.5);  // Scale down resource icon
            this.container.addChild(sprite);
            this.resourceSprites.set(entityId, sprite);
        } else if (sprite.texture !== texture) {
            sprite.texture = texture;
        }

        // Calculate arm position
        const { tileWidth, tileHeight } = this.game.config;

        // Manipulator center
        const manipX = state.x * tileWidth + tileWidth / 2;
        const manipY = state.y * tileHeight + tileHeight / 2;

        // Source and target positions
        const sourcePos = state.getSourcePosition();
        const targetPos = state.getTargetPosition();

        const sourceX = sourcePos.x * tileWidth + tileWidth / 2;
        const sourceY = sourcePos.y * tileHeight + tileHeight / 2;
        const targetX = targetPos.x * tileWidth + tileWidth / 2;
        const targetY = targetPos.y * tileHeight + tileHeight / 2;

        // Interpolate based on armPosition (0 = source, 0.5 = center, 1 = target)
        let resourceX, resourceY;

        if (state.armPosition <= 0.5) {
            // Moving from source to center
            const t = state.armPosition * 2;  // 0 to 1
            resourceX = sourceX + (manipX - sourceX) * t;
            resourceY = sourceY + (manipY - sourceY) * t;
        } else {
            // Moving from center to target
            const t = (state.armPosition - 0.5) * 2;  // 0 to 1
            resourceX = manipX + (targetX - manipX) * t;
            resourceY = manipY + (targetY - manipY) * t;
        }

        sprite.x = resourceX;
        sprite.y = resourceY;
        sprite.zIndex = manipY + tileHeight * 2;  // Above manipulator
    }

    /**
     * Clear all sprites
     */
    clear() {
        for (const sprite of this.resourceSprites.values()) {
            this.container.removeChild(sprite);
        }
        this.resourceSprites.clear();
    }
}

export default ResourceRenderer;
