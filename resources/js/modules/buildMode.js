import * as PIXI from 'pixi.js';
import { tileKey, tileToWorld, getCSRFToken } from './utils.js';
import { BUILD_VALID_COLOR, BUILD_INVALID_COLOR, BUILD_VALID_ALPHA, BUILD_INVALID_ALPHA, PREVIEW_Z_OFFSET } from './constants.js';
import { EntityBehaviorFactory } from './entityBehaviors.js';

/**
 * BuildMode - handles building placement on the map
 */
export class BuildMode {
    constructor(game) {
        this.game = game;
        this.isActive = false;
        this.entityTypeId = null;
        this.previewSprite = null;
        this.canPlace = false;
        this.currentTile = { x: -1, y: -1 };
        this.targetEntity = null;
        this.placementError = null;
    }

    /**
     * Initialize build mode
     */
    init() {
        this.game.app.canvas.addEventListener('click', (e) => this.onClick(e));
    }

    /**
     * Activate build mode with selected entity type
     */
    activate(entityTypeId) {
        this.entityTypeId = entityTypeId;
        this.isActive = true;
        this.createPreviewSprite();
        this.game.app.canvas.style.cursor = 'crosshair';
    }

    /**
     * Deactivate build mode
     */
    deactivate() {
        this.isActive = false;
        this.entityTypeId = null;
        this.removePreviewSprite();
        this.game.app.canvas.style.cursor = 'default';

        if (this.game.buildPanel) {
            this.game.buildPanel.activeSlot = -1;
            this.game.buildPanel.slotElements.forEach(el => el.classList.remove('active'));
        }
    }

    /**
     * Create preview sprite for placement
     */
    createPreviewSprite() {
        this.removePreviewSprite();

        const entityType = this.game.entityTypes[this.entityTypeId];
        if (!entityType) return;

        const texture = this.game.textures[`entity_${this.entityTypeId}_blueprint`];
        if (!texture) return;

        this.previewSprite = new PIXI.Sprite(texture);
        this.previewSprite.alpha = BUILD_VALID_ALPHA;
        this.previewSprite.visible = false;
        this.game.entityLayer.addChild(this.previewSprite);
    }

    /**
     * Remove preview sprite
     */
    removePreviewSprite() {
        if (this.previewSprite) {
            this.game.entityLayer.removeChild(this.previewSprite);
            this.previewSprite.destroy();
            this.previewSprite = null;
        }
    }

    /**
     * Update preview position based on mouse
     */
    updatePreview(screenX, screenY) {
        if (!this.isActive || !this.previewSprite) return;

        const tile = this.game.input.screenToTile(screenX, screenY);

        if (tile.x === this.currentTile.x && tile.y === this.currentTile.y) {
            return;
        }
        this.currentTile = tile;

        const { tileWidth, tileHeight } = this.game.config;
        const pos = tileToWorld(tile.x, tile.y, tileWidth, tileHeight);

        this.previewSprite.x = pos.x;
        this.previewSprite.y = pos.y;
        this.previewSprite.zIndex = pos.y + PREVIEW_Z_OFFSET;
        this.previewSprite.visible = true;

        this.canPlace = this.checkPlacement(tile.x, tile.y);
        this.updatePreviewVisual();
    }

    /**
     * Update preview visual based on placement validity
     */
    updatePreviewVisual() {
        if (this.canPlace) {
            this.previewSprite.tint = BUILD_VALID_COLOR;
            this.previewSprite.alpha = BUILD_VALID_ALPHA;
        } else {
            this.previewSprite.tint = BUILD_INVALID_COLOR;
            this.previewSprite.alpha = BUILD_INVALID_ALPHA;
        }
    }

    /**
     * Check if building can be placed at tile position
     * Uses EntityBehaviorFactory for type-specific rules
     */
    checkPlacement(tileX, tileY) {
        const behavior = EntityBehaviorFactory.create(this.game, this.entityTypeId);
        if (!behavior) {
            this.placementError = 'Invalid entity type';
            return false;
        }

        const result = behavior.canBuildAt(tileX, tileY);

        if (!result.allowed) {
            this.placementError = result.error;
            this.targetEntity = null;
            return false;
        }

        this.targetEntity = result.targetEntity;
        this.placementError = null;
        return true;
    }

    /**
     * Handle click to place building
     */
    onClick(e) {
        if (!this.isActive || !this.canPlace) return;
        if (e.target !== this.game.app.canvas) return;

        const tile = this.game.input.screenToTile(e.clientX, e.clientY);
        this.placeBuilding(tile.x, tile.y);
    }

    /**
     * Place building at tile position
     * Sends tile coordinates directly (not pixels)
     */
    async placeBuilding(tileX, tileY) {
        const requestBody = {
            entity_type_id: this.entityTypeId,
            x: tileX,
            y: tileY,
            state: 'built'
        };

        // Include target entity if building on resource node
        if (this.targetEntity) {
            requestBody.target_entity_id = this.targetEntity.entity_id;
        }

        try {
            const response = await fetch(this.game.config.createEntityUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCSRFToken()
                },
                body: JSON.stringify(requestBody)
            });

            const data = await response.json();

            if (data.result === 'ok' && data.entity) {
                // Remove target entity from client if it was replaced
                if (this.targetEntity && data.targetRemoved) {
                    const targetKey = `entity_${this.targetEntity.entity_id}`;
                    this.game.entityData.delete(targetKey);
                    const targetSprite = this.game.loadedEntities.get(targetKey);
                    if (targetSprite) {
                        this.game.entityLayer.removeChild(targetSprite);
                        targetSprite.destroy();
                        this.game.loadedEntities.delete(targetKey);
                    }
                }

                this.game.renderEntities([data.entity]);
                this.handleEyeEntityPlacement(data.entity);
            } else if (data.result !== 'ok') {
                console.error('Failed to place building:', data.error);
            }
        } catch (e) {
            console.error('Error placing building:', e);
        }
    }

    /**
     * Handle fog of war update for eye entities
     */
    handleEyeEntityPlacement(entity) {
        if (!this.game.fogOfWar) return;

        this.game.fogOfWar.addEyeEntity(
            entity.entity_id,
            entity.entity_type_id,
            parseInt(entity.x),
            parseInt(entity.y)
        );
        this.game.loadViewport();
    }
}

export default BuildMode;
