import * as PIXI from 'pixi.js';
import { getCSRFToken } from '../utils.js';
import { GameModeBase } from '../modes/gameModeBase.js';

/**
 * DepositBuildMode - place deposits on the map with preview (admin tool)
 * Features:
 * - Semi-transparent preview sprite following mouse
 * - Green tint if valid placement (landing exists), red if not
 * - Click to create deposit with random amount in range
 * - AJAX creation without validation
 */
export class DepositBuildMode extends GameModeBase {
    constructor(game) {
        super(game); // Call GameModeBase constructor

        this.depositType = null;
        this.minAmount = 0;
        this.maxAmount = 0;
        this.previewSprite = null;
    }

    /**
     * Initialize mode
     */
    init() {
        // Mode will be activated from depositWindow
    }

    /**
     * Activate build mode with deposit type and amount range
     */
    onActivate(data) {
        this.depositType = data.depositType;
        this.minAmount = data.minAmount;
        this.maxAmount = data.maxAmount;

        this.createPreviewSprite();
        this.bindEvents();
    }

    /**
     * Deactivate build mode
     */
    onDeactivate() {
        if (this.previewSprite) {
            this.previewSprite.destroy();
            this.previewSprite = null;
        }

        this.unbindEvents();
    }

    /**
     * Create preview sprite
     */
    createPreviewSprite() {
        const textureName = `deposit_${this.depositType.deposit_type_id}_normal`;
        const texture = this.game.textures[textureName];

        if (!texture) {
            console.warn(`Texture not found: ${textureName}`);
            return;
        }

        this.previewSprite = new PIXI.Sprite(texture);
        this.previewSprite.alpha = 0.5;
        this.previewSprite.tint = 0x00ff00; // Green by default

        // Add to deposit layer
        this.game.depositLayerManager.depositLayer.addChild(this.previewSprite);
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Use base class method for auto-cleanup
        this.addEventListener(this.game.app.stage, 'pointermove', this.onPointerMove);
        this.addEventListener(document, 'click', this.onClick);
    }

    /**
     * Unbind event listeners (called by base class)
     */
    unbindEvents() {
        // Base class unbindAllEvents() will handle cleanup
        // This method kept for compatibility
    }

    /**
     * Handle pointer move - update preview position and validity
     */
    onPointerMove(event) {
        if (!this.previewSprite) return;

        const worldPos = this.game.inputManager.screenToWorld(event.global.x, event.global.y);
        const tileX = Math.floor(worldPos.x / this.game.config.tileWidth);
        const tileY = Math.floor(worldPos.y / this.game.config.tileHeight);

        // Position sprite at tile coordinates
        this.previewSprite.x = tileX * this.game.config.tileWidth;
        this.previewSprite.y = tileY * this.game.config.tileHeight;

        // Check if tile has landing (can only place on landing)
        const landingId = this.game.tileLayerManager.getLandingAt(tileX, tileY);
        this.previewSprite.tint = landingId ? 0x00ff00 : 0xff0000; // Green if valid, red if not
    }

    /**
     * Handle click - create deposit
     */
    async onClick(event) {
        if (!this.isActive) return;
        if (event.target !== this.game.app.canvas) return;

        const worldPos = this.game.inputManager.screenToWorld(event.clientX, event.clientY);
        const tileX = Math.floor(worldPos.x / this.game.config.tileWidth);
        const tileY = Math.floor(worldPos.y / this.game.config.tileHeight);

        // Check if can place (must have landing)
        const landingId = this.game.tileLayerManager.getLandingAt(tileX, tileY);
        if (!landingId) {
            console.log('Cannot place deposit - no landing at this position');
            return;
        }

        // Generate random amount in range
        const resourceAmount = Math.floor(
            Math.random() * (this.maxAmount - this.minAmount + 1) + this.minAmount
        );

        // Create deposit via AJAX
        try {
            const response = await fetch(this.game.config.createDepositUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCSRFToken()
                },
                body: JSON.stringify({
                    region_id: this.game.regionId,
                    deposit_type_id: this.depositType.deposit_type_id,
                    x: tileX,
                    y: tileY,
                    resource_amount: resourceAmount
                })
            });

            const data = await response.json();

            if (data.result === 'ok') {
                // Add to deposit layer
                this.game.depositLayerManager.addDeposit(data.deposit);
                console.log('Deposit created:', data.deposit);
            } else {
                console.error('Failed to create deposit:', data.error);
            }
        } catch (error) {
            console.error('Error creating deposit:', error);
        }
    }
}
