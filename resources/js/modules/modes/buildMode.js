import * as PIXI from 'pixi.js';
import { tileKey, tileToWorld, getCSRFToken } from '../utils.js';
import { BUILD_VALID_COLOR, BUILD_INVALID_COLOR, BUILD_VALID_ALPHA, BUILD_INVALID_ALPHA, PREVIEW_Z_OFFSET } from '../constants.js';
import { EntityBehaviorFactory } from '../entityBehaviors.js';
import { GameModeBase } from './gameModeBase.js';

/**
 * BuildMode - handles building placement on the map
 * Supports rotation for entities with orientation variants (R or К key)
 */
export class BuildMode extends GameModeBase {
    constructor(game) {
        super(game); // Call GameModeBase constructor

        this.entityTypeId = null;
        this.previewSprite = null;
        this.errorText = null;
        this.canPlace = false;
        this.currentTile = { x: -1, y: -1 };
        this.targetEntity = null;
        this.placementError = null;

        // Rotation support
        this.baseEntityTypeId = null;  // The parent entity type (or self if no parent)
        this.orientationVariants = []; // Array of entity type IDs for each orientation
        this.currentOrientationIndex = 0;
    }

    /**
     * Initialize build mode (one-time setup)
     */
    init() {
        // Register event listeners using base class method (auto-cleanup)
        this.addEventListener(this.game.app.canvas, 'click', this.onClick);
        this.addEventListener(document, 'keydown', this.onKeyDown);
    }

    /**
     * Handle keyboard input for rotation
     */
    onKeyDown(e) {
        if (!this.isActive) return;

        // R or К (Russian) key for rotation
        if (e.key === 'r' || e.key === 'R' || e.key === 'к' || e.key === 'К') {
            e.preventDefault();
            this.rotateBuilding();
        }
    }

    /**
     * Rotate building to next orientation
     */
    rotateBuilding() {
        if (this.orientationVariants.length <= 1) return;

        // Cycle to next orientation
        this.currentOrientationIndex = (this.currentOrientationIndex + 1) % this.orientationVariants.length;
        const newTypeId = this.orientationVariants[this.currentOrientationIndex];

        // Update entity type and recreate preview
        this.entityTypeId = newTypeId;
        this.createPreviewSprite();

        // Update preview position if we have a current tile
        if (this.currentTile.x >= 0 && this.currentTile.y >= 0) {
            const { tileWidth, tileHeight } = this.game.config;
            const pos = tileToWorld(this.currentTile.x, this.currentTile.y, tileWidth, tileHeight);
            this.previewSprite.x = pos.x;
            this.previewSprite.y = pos.y;
            this.previewSprite.zIndex = pos.y + PREVIEW_Z_OFFSET;
            this.previewSprite.visible = true;
            this.canPlace = this.checkPlacement(this.currentTile.x, this.currentTile.y);
            this.updatePreviewVisual();
        }
    }

    /**
     * Get orientation variants for an entity type
     * Returns array of entity type IDs that share the same base
     */
    getOrientationVariants(entityTypeId) {
        const entityType = this.game.entityTypes[entityTypeId];
        if (!entityType) return [entityTypeId];

        // Get base entity type ID (parent or self)
        const baseId = entityType.parent_entity_type_id
            ? parseInt(entityType.parent_entity_type_id)
            : parseInt(entityTypeId);

        // Collect all variants with same base (including base itself)
        const variants = [];
        const orientationOrder = ['right', 'down', 'left', 'up']; // Clockwise order

        for (const typeId in this.game.entityTypes) {
            const et = this.game.entityTypes[typeId];
            const etId = parseInt(typeId);
            const etParentId = et.parent_entity_type_id ? parseInt(et.parent_entity_type_id) : null;

            // Include if this is the base, or if parent matches base
            if (etId === baseId || etParentId === baseId) {
                variants.push({
                    id: etId,
                    orientation: et.orientation || 'none'
                });
            }
        }

        // Sort by orientation order
        variants.sort((a, b) => {
            const aIndex = orientationOrder.indexOf(a.orientation);
            const bIndex = orientationOrder.indexOf(b.orientation);
            return aIndex - bIndex;
        });

        return variants.map(v => v.id);
    }

    /**
     * Activate build mode with selected entity type
     */
    onActivate(data) {
        this.entityTypeId = data.entityTypeId || data;

        // Initialize orientation variants
        this.orientationVariants = this.getOrientationVariants(this.entityTypeId);
        this.currentOrientationIndex = this.orientationVariants.indexOf(parseInt(this.entityTypeId));
        if (this.currentOrientationIndex < 0) this.currentOrientationIndex = 0;

        this.createPreviewSprite();
        this.game.app.canvas.style.cursor = 'crosshair';
    }

    /**
     * Deactivate build mode
     */
    onDeactivate() {
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

        // Create error text
        this.errorText = new PIXI.Text('', {
            fontSize: 14,
            fill: 0xFF0000,
            fontWeight: 'bold',
            dropShadow: true,
            dropShadowColor: 0x000000,
            dropShadowBlur: 4,
            dropShadowDistance: 2,
            align: 'center'
        });
        this.errorText.anchor.set(0.5, 1); // Center horizontally, bottom vertically
        this.errorText.visible = false;
        this.game.entityLayer.addChild(this.errorText);
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

        if (this.errorText) {
            this.game.entityLayer.removeChild(this.errorText);
            this.errorText.destroy();
            this.errorText = null;
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
        if (!this.previewSprite) return;

        if (this.canPlace) {
            this.previewSprite.tint = BUILD_VALID_COLOR;
            this.previewSprite.alpha = BUILD_VALID_ALPHA;

            // Hide error text
            if (this.errorText) {
                this.errorText.visible = false;
            }
        } else {
            this.previewSprite.tint = BUILD_INVALID_COLOR;
            this.previewSprite.alpha = BUILD_INVALID_ALPHA;

            // Show error text with message
            if (this.errorText && this.placementError) {
                this.errorText.text = this.placementError;
                this.errorText.visible = true;

                // Position above preview sprite (centered)
                this.errorText.x = this.previewSprite.x + (this.previewSprite.width / 2);
                this.errorText.y = this.previewSprite.y - 10; // 10px above sprite
                this.errorText.zIndex = this.previewSprite.zIndex + 1;
            }
        }
    }

    /**
     * Check if user can afford building
     */
    canAffordBuilding(entityTypeId) {
        const costs = this.game.entityTypeCosts[entityTypeId];
        if (!costs) return true; // No cost = free

        for (const [resourceId, quantity] of Object.entries(costs)) {
            const available = this.game.userResources[resourceId] || 0;
            if (available < quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if building can be placed at tile position
     * Uses EntityBehaviorFactory for type-specific rules
     */
    checkPlacement(tileX, tileY) {
        // Check if user can afford building
        if (!this.canAffordBuilding(this.entityTypeId)) {
            this.placementError = 'Not enough resources';
            return false;
        }

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
            state: 'blueprint'
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
                // Update local user resources (deduct building cost)
                const costs = this.game.entityTypeCosts[this.entityTypeId];
                if (costs) {
                    for (const [resourceId, quantity] of Object.entries(costs)) {
                        const rid = parseInt(resourceId);
                        this.game.userResources[rid] = (this.game.userResources[rid] || 0) - quantity;
                        if (this.game.userResources[rid] < 0) {
                            this.game.userResources[rid] = 0;
                        }
                    }

                    // Update build panel affordability after resource change
                    if (this.game.buildPanel) {
                        this.game.buildPanel.updateAffordability();
                    }

                    // Update resource panel display
                    if (this.game.resourcePanel) {
                        this.game.resourcePanel.updateAll();
                    }
                }

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

                // Remove deposits from client if they were removed by building placement
                if (data.depositsRemoved && data.depositsRemoved.length > 0) {
                    const depositIds = data.depositsRemoved.map(d => d.deposit_id);
                    this.game.depositManager.removeDeposits(depositIds);
                }

                this.game.renderEntities([data.entity]);
                this.handleEyeEntityPlacement(data.entity);

                // Stay in build mode to allow continuous building
                // (removed automatic return to normal mode)
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
