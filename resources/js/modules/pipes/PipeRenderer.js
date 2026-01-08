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
     * Create pipe sprite with fluid visualization
     * @param {Object} entity
     * @param {PIXI.Texture} texture
     * @returns {PIXI.Container}
     */
    createPipeSprite(entity, texture) {
        const container = new PIXI.Container();

        // Add main pipe sprite
        const pipeSprite = new PIXI.Sprite(texture);
        container.addChild(pipeSprite);

        // Add fluid visualization if system has fluid
        const system = this.pipeSystemManager.getSystemForEntity(entity.entity_id);
        if (system && system.resource_id && system.current_amount > 0) {
            const fluidGraphics = this.createFluidGraphics(system.resource_id);
            container.addChild(fluidGraphics);
        }

        return container;
    }

    /**
     * Create fluid visualization graphics (colored square in transparent window)
     * @param {number} resourceId
     * @returns {PIXI.Graphics}
     */
    createFluidGraphics(resourceId) {
        const graphics = new PIXI.Graphics();
        const color = this.pipeSystemManager.getFluidColor(resourceId);

        // Draw colored rectangle in the transparent window area
        // Window is 12x12px centered at (32, 32) in 64x64 sprite
        // So it's from (26, 26) to (38, 38)
        graphics.beginFill(color, 0.8);
        graphics.drawRect(26, 26, 12, 12);
        graphics.endFill();

        return graphics;
    }

    /**
     * Update existing pipe entity with new fluid state
     * @param {PIXI.Container} container
     * @param {Object} entity
     */
    updateFluidVisualization(container, entity) {
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
