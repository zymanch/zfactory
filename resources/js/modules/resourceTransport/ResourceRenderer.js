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

        // Get current position from ticks
        const position_px = state.getPositionPx();
        const movementPhase = state.getMovementPhase();

        // Two-phase positioning:
        // Phase 1: Move from entry edge to center (depends on fromDirection)
        // Phase 2: Move from center to exit edge (depends on orientation)
        let offsetX = 0;
        let offsetY = 0;

        if (movementPhase === 1) {
            // Phase 1: Moving to center based on fromDirection
            // position_px: -32 → 0
            const distance = Math.abs(position_px);  // 32px → 0px

            switch (state.fromDirection) {
                case 'up':
                    offsetY = -distance;  // Moving down to center
                    break;
                case 'down':
                    offsetY = distance;   // Moving up to center
                    break;
                case 'left':
                    offsetX = -distance;  // Moving right to center
                    break;
                case 'right':
                    offsetX = distance;   // Moving left to center
                    break;
            }
        } else {
            // Phase 2: Moving from center to exit based on orientation
            // position_px: 0 → +32
            const distance = position_px;  // 0px → 32px

            switch (state.orientation) {
                case 'right':
                    offsetX = distance;
                    break;
                case 'down':
                    offsetY = distance;
                    break;
                case 'left':
                    offsetX = -distance;
                    break;
                case 'up':
                    offsetY = -distance;
                    break;
            }
        }

        sprite.x = centerX + offsetX;
        sprite.y = centerY + offsetY;
        sprite.zIndex = centerY + tileHeight;  // Slightly above entity

        // Underground conveyor transparency
        // Resource is "underground" (30% transparent) when:
        // - On IN belt after reaching center (position_px >= 0)
        // - On OUT belt before reaching surface (position_px < 0)
        if ((state.isUndergroundIn && position_px >= 0) ||
            (state.isUndergroundOut && position_px < 0)) {
            sprite.alpha = 0.3;
        } else {
            sprite.alpha = 1.0;
        }
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
