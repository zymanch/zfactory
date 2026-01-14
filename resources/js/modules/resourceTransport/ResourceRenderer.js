/**
 * ResourceRenderer - Renders resources on conveyors and manipulators
 */
export class ResourceRenderer {
    constructor(game) {
        this.game = game;
        this.resourceTextures = new Map();  // resource_id -> Texture
        this.resourceSprites = new Map();   // entity_id -> Sprite
        this.container = null;
        this.initialized = false;
    }

    /**
     * Initialize the renderer
     */
    async init() {
        this.container = this.game.graphics.createContainer({
            sortableChildren: true,
            visible: true,
            alpha: 1.0,
            zIndex: 3  // Above entity layer (entityLayer.zIndex = 2)
        });

        // Insert after entity layer
        const entityLayerIndex = this.game.worldContainer.getChildIndex(this.game.entityLayer);
        this.game.worldContainer.addChildAt(this.container, entityLayerIndex + 1);

        this.loadResourceTextures();
        this.initialized = true;
    }

    /**
     * Load resource icon textures (already loaded in manifest)
     */
    loadResourceTextures() {
        for (const resourceId in this.game.resources) {
            const resource = this.game.resources[resourceId];
            if (!resource.icon_url) continue;

            const textureKey = `resource_${resourceId}`;
            const texture = this.game.graphics.getTexture(textureKey);
            if (texture) {
                this.resourceTextures.set(parseInt(resourceId), texture);
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
            sprite = this.game.graphics.createSprite(texture, {
                anchor: { x: 0.5, y: 0.5 },
                scale: { x: 0.75, y: 0.75 }  // Slightly smaller than tile (64 * 0.75 = 48px)
            });
            this.container.addChild(sprite);
            this.resourceSprites.set(entityId, sprite);
        } else if (sprite.texture !== texture) {
            sprite.texture = texture;
        }

        // Calculate position on belt
        const { tileWidth, tileHeight } = this.game.config;
        const centerX = state.x * tileWidth + tileWidth / 2;
        const centerY = state.y * tileHeight + tileHeight / 2;

        // position_px is already centered: 0 = center, negative = towards center, positive = from center
        // Calculate main offset (along movement direction)
        let offsetX = 0;
        let offsetY = 0;

        switch (state.orientation) {
            case 'right':
                offsetX = state.position_px;  // Already centered!
                // Perpendicular offset based on from_direction
                if (state.fromDirection === 'down') {
                    offsetY = tileHeight / 4;  // Bottom lane
                } else if (state.fromDirection === 'up') {
                    offsetY = -tileHeight / 4;  // Top lane
                }
                break;

            case 'down':
                offsetY = state.position_px;
                // Perpendicular offset
                if (state.fromDirection === 'left') {
                    offsetX = -tileHeight / 4;  // Left lane
                } else if (state.fromDirection === 'right') {
                    offsetX = tileHeight / 4;  // Right lane
                }
                break;

            case 'left':
                offsetX = -state.position_px;  // Reverse for left movement
                // Perpendicular offset
                if (state.fromDirection === 'up') {
                    offsetY = -tileHeight / 4;  // Top lane
                } else if (state.fromDirection === 'down') {
                    offsetY = tileHeight / 4;  // Bottom lane
                }
                break;

            case 'up':
                offsetY = -state.position_px;  // Reverse for up movement
                // Perpendicular offset
                if (state.fromDirection === 'right') {
                    offsetX = tileHeight / 4;  // Right lane
                } else if (state.fromDirection === 'left') {
                    offsetX = -tileHeight / 4;  // Left lane
                }
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
            sprite = this.game.graphics.createSprite(texture, {
                anchor: { x: 0.5, y: 0.5 },
                scale: { x: 1, y: 1 }  // Full size resource icon
            });
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

        // position_px is centered: -centerPx (source) to 0 (manipulator) to +centerPx (target)
        const centerPx = state.centerPositionPx;
        let resourceX, resourceY;

        if (state.position_px <= 0) {
            // Moving from source (-centerPx) to center (0)
            const t = (state.position_px + centerPx) / centerPx;  // 0 to 1
            resourceX = sourceX + (manipX - sourceX) * t;
            resourceY = sourceY + (manipY - sourceY) * t;
        } else {
            // Moving from center (0) to target (+centerPx)
            const t = state.position_px / centerPx;  // 0 to 1
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
