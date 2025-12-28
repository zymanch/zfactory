import * as PIXI from 'pixi.js';

/**
 * Manages deposit layer rendering (trees, rocks, ores)
 * Deposits are separate from entities - simplified rendering (only normal.png)
 */
export class DepositLayerManager {
    constructor(game) {
        this.game = game;
        this.deposits = {}; // depositId -> deposit data
        this.sprites = {}; // depositId -> PIXI.Sprite

        // Create deposit layer container (z-index between landing and entity)
        this.depositLayer = new PIXI.Container();
        this.depositLayer.zIndex = 1.5;
        this.depositLayer.name = 'depositLayer';
    }

    /**
     * Initialize deposit layer
     */
    init() {
        this.game.worldContainer.addChild(this.depositLayer);
        this.depositLayer.sortableChildren = true;
    }

    /**
     * Load deposits from initial data (already loaded in config)
     */
    loadDeposits(deposits) {
        this.clearAllDeposits();

        for (const deposit of deposits) {
            this.addDeposit(deposit);
        }

        console.log(`Loaded ${Object.keys(this.deposits).length} deposits`);
    }

    /**
     * Add deposit to the layer
     */
    addDeposit(depositData) {
        const depositId = depositData.deposit_id;
        this.deposits[depositId] = depositData;

        const sprite = this.createDepositSprite(depositData);
        if (sprite) {
            this.sprites[depositId] = sprite;
            this.depositLayer.addChild(sprite);
        }
    }

    /**
     * Create PIXI sprite for deposit
     */
    createDepositSprite(deposit) {
        const depositType = this.game.depositTypes[deposit.deposit_type_id];
        if (!depositType) {
            console.warn(`Unknown deposit_type_id: ${deposit.deposit_type_id}`);
            return null;
        }

        const textureName = `deposit_${deposit.deposit_type_id}_normal`;
        const texture = this.game.textures[textureName];

        if (!texture) {
            console.warn(`Texture not found: ${textureName}`);
            return null;
        }

        const sprite = new PIXI.Sprite(texture);

        // Position (deposits use tile coordinates)
        sprite.x = deposit.x * this.game.config.tileWidth;
        sprite.y = deposit.y * this.game.config.tileHeight;

        // Store deposit data
        sprite.depositId = deposit.deposit_id;
        sprite.depositData = deposit;
        sprite.depositType = depositType;

        // Enable interaction for tooltips
        sprite.eventMode = 'static';
        sprite.cursor = 'pointer';

        // Set z-index based on Y position for proper depth sorting
        sprite.zIndex = sprite.y;

        return sprite;
    }

    /**
     * Remove deposit from layer
     */
    removeDeposit(depositId) {
        if (this.sprites[depositId]) {
            this.depositLayer.removeChild(this.sprites[depositId]);
            this.sprites[depositId].destroy();
            delete this.sprites[depositId];
        }

        delete this.deposits[depositId];
    }

    /**
     * Remove multiple deposits (when building placed on deposits)
     */
    removeDeposits(depositIds) {
        for (const depositId of depositIds) {
            this.removeDeposit(depositId);
        }
    }

    /**
     * Clear all deposits
     */
    clearAllDeposits() {
        for (const depositId in this.sprites) {
            this.depositLayer.removeChild(this.sprites[depositId]);
            this.sprites[depositId].destroy();
        }

        this.sprites = {};
        this.deposits = {};
    }

    /**
     * Get deposit at tile position
     */
    getDepositAt(tileX, tileY) {
        for (const depositId in this.deposits) {
            const deposit = this.deposits[depositId];
            if (deposit.x === tileX && deposit.y === tileY) {
                return deposit;
            }
        }
        return null;
    }

    /**
     * Get all deposits in area
     */
    getDepositsInArea(tileX, tileY, width, height) {
        const deposits = [];

        for (const depositId in this.deposits) {
            const deposit = this.deposits[depositId];
            if (deposit.x >= tileX && deposit.x < tileX + width &&
                deposit.y >= tileY && deposit.y < tileY + height) {
                deposits.push(deposit);
            }
        }

        return deposits;
    }

    /**
     * Get sprite at screen position (for hover/click detection)
     */
    getSpriteAt(screenX, screenY) {
        const point = new PIXI.Point(screenX, screenY);

        // Check from top to bottom (highest zIndex first)
        const sortedSprites = Object.values(this.sprites).sort((a, b) => b.zIndex - a.zIndex);

        for (const sprite of sortedSprites) {
            if (sprite.containsPoint(point)) {
                return sprite;
            }
        }

        return null;
    }

    /**
     * Update deposit layer
     */
    update() {
        // Deposits are static, no animations needed
        // Only update z-indices if camera moved (for proper depth sorting)
        this.depositLayer.children.sort((a, b) => a.zIndex - b.zIndex);
    }

    /**
     * Destroy deposit layer
     */
    destroy() {
        this.clearAllDeposits();
        if (this.depositLayer) {
            this.game.worldContainer.removeChild(this.depositLayer);
            this.depositLayer.destroy();
        }
    }
}
