import * as PIXI from 'pixi.js';

/**
 * Renders pipes with fluid visualization
 */
export class PipeRenderer {
    constructor(game) {
        this.game = game;
        this.pipeSystemManager = game.pipeSystemManager;
    }

    /**
     * Create pipe container with sprite + fluid visualization
     * @param {Object} entity - Entity data
     * @param {PIXI.Texture} texture - Pipe texture
     * @param {boolean} isVisible - Visibility flag
     * @returns {PIXI.Container}
     */
    createPipeContainer(entity, texture, isVisible) {
        const container = new PIXI.Container();

        // Set position and z-index
        const { tileWidth, tileHeight } = this.game.config;
        const pixelX = parseInt(entity.x) * tileWidth;
        const pixelY = parseInt(entity.y) * tileHeight;

        container.x = pixelX;
        container.y = pixelY;
        container.zIndex = pixelY;
        container.visible = isVisible;
        container.eventMode = isVisible ? 'static' : 'none';
        container.cursor = isVisible ? 'pointer' : 'default';
        container.entityKey = `entity_${entity.entity_id}`;

        // Add main pipe sprite
        const pipeSprite = new PIXI.Sprite(texture);
        container.addChild(pipeSprite);

        // Add fluid sprite (empty or resource icon)
        const fluidSprite = this.createFluidSprite(entity);
        container.addChild(fluidSprite);

        // Add event handlers (if not blueprint)
        if (entity.state !== 'blueprint') {
            container.on('pointerover', (e) => this.game.onEntityHover(container, true, e));
            container.on('pointerout', (e) => this.game.onEntityHover(container, false, e));
            container.on('pointermove', (e) => this.game.onEntityMove(e));
            container.on('click', (e) => this.game.onEntityClick(container, e));
        }

        return container;
    }

    /**
     * Create fluid visualization (empty black square or resource icon)
     * @param {Object} entity
     * @returns {PIXI.Graphics|PIXI.Sprite}
     */
    createFluidSprite(entity) {
        // Safety check for pipeSystemManager
        if (!this.pipeSystemManager) {
            // Fallback: just show empty black square
            const graphics = new PIXI.Graphics();
            graphics.beginFill(0x000000, 1.0);
            graphics.drawRect(27, 27, 10, 10);
            graphics.endFill();
            return graphics;
        }

        const system = this.pipeSystemManager.getSystemForEntity(entity.entity_id);
        const v = this.game.config.assetVersion || 1;

        if (system && system.resource_id && system.current_amount > 0) {
            // Has fluid - show resource icon
            const resource = this.game.resources[String(system.resource_id)];
            if (resource && resource.icon_url) {
                const iconUrl = `/assets/tiles/resources/${resource.icon_url}?v=${v}`;
                const sprite = new PIXI.Sprite(PIXI.Texture.from(iconUrl));
                sprite.x = 27;
                sprite.y = 27;
                sprite.width = 10;
                sprite.height = 10;
                return sprite;
            }
        }

        // Empty pipe - draw black square using Graphics
        const graphics = new PIXI.Graphics();
        graphics.beginFill(0x000000, 1.0);
        graphics.drawRect(27, 27, 10, 10);
        graphics.endFill();
        return graphics;
    }

    /**
     * Create fluid visualization graphics (colored square in transparent window)
     * @param {number} resourceId
     * @returns {PIXI.Graphics}
     */
    createFluidGraphics(resourceId) {
        const graphics = new PIXI.Graphics();

        // Get color from pipeSystemManager if available, otherwise default
        const color = this.pipeSystemManager
            ? this.pipeSystemManager.getFluidColor(resourceId)
            : 0xffffff;

        // Draw colored rectangle in the transparent window area
        // Window is 10x10px centered at (32, 32) in 64x64 sprite
        // So it's from (27, 27) to (37, 37)
        graphics.beginFill(color, 0.8);
        graphics.drawRect(27, 27, 10, 10);
        graphics.endFill();

        return graphics;
    }

    /**
     * Update existing pipe entity with new fluid state
     * @param {PIXI.Container} container
     * @param {Object} entity
     */
    updateFluidVisualization(container, entity) {
        // Safety check
        if (!this.pipeSystemManager) {
            return;
        }

        // Remove old fluid graphics
        const oldFluid = container.children.find(child => child instanceof PIXI.Graphics);
        if (oldFluid) {
            container.removeChild(oldFluid);
        }

        // Add new fluid graphics if system has fluid
        const system = this.pipeSystemManager.getSystemForEntity(entity.entity_id);
        if (system && system.resource_id && system.current_amount > 0) {
            const fluidGraphics = this.createFluidGraphics(system.resource_id);
            container.addChild(fluidGraphics);
        }
    }
}
