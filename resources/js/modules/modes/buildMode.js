import * as PIXI from 'pixi.js';
import { tileKey, tileToWorld, getCSRFToken } from '../utils.js';
import { BUILD_VALID_COLOR, BUILD_INVALID_COLOR, BUILD_VALID_ALPHA, BUILD_INVALID_ALPHA, PREVIEW_Z_OFFSET } from '../constants.js';
import EntityBehaviorFactory from '../entityBehaviors.js';
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

        // Drag-and-drop for mass building
        this.isDragging = false;
        this.wasDragging = false; // Prevent onClick after drag
        this.dragStartTile = { x: -1, y: -1 };
        this.dragEndTile = { x: -1, y: -1 };
        this.previewSprites = []; // Array of {sprite, x, y, valid, entityTypeId}

        // Prevent double placement
        this.isPlacing = false;
    }

    /**
     * Initialize build mode (one-time setup)
     */
    init() {
        // Register event listeners using base class method (auto-cleanup)
        this.addEventListener(this.game.app.canvas, 'click', this.onClick);
        this.addEventListener(this.game.app.canvas, 'mousedown', this.onMouseDown);
        this.addEventListener(this.game.app.canvas, 'mouseup', this.onMouseUp);
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

        // Update preview position if we have a current tile and preview sprite was created
        if (this.previewSprite && this.currentTile.x >= 0 && this.currentTile.y >= 0) {
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
     * Adjust tile coordinates based on building size
     * Places cursor at bottom-center of building
     */
    adjustTileForBuildingSize(mouseTileX, mouseTileY) {
        const entityType = this.game.entityTypes[this.entityTypeId];
        if (!entityType) return { x: mouseTileX, y: mouseTileY };

        const width = parseInt(entityType.width) || 1;
        const height = parseInt(entityType.height) || 1;

        // Formula:
        // x = mouseX - floor(width / 2)
        // y = mouseY - height + 1
        const adjustedX = mouseTileX - Math.floor(width / 2);
        const adjustedY = mouseTileY - height + 1;

        return { x: adjustedX, y: adjustedY };
    }

    /**
     * Update preview position based on mouse
     */
    updatePreview(screenX, screenY) {
        if (!this.isActive) return;

        // If drag active - handle drag preview
        if (this.isDragging) {
            this.updateDragPreview(screenX, screenY);
            return;
        }

        // Regular single preview
        if (!this.previewSprite) return;

        const mouseTile = this.game.input.screenToTile(screenX, screenY);
        const tile = this.adjustTileForBuildingSize(mouseTile.x, mouseTile.y);

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
        // Ignore click after drag
        if (this.wasDragging) {
            this.wasDragging = false;
            return;
        }

        if (!this.isActive || !this.canPlace) return;
        if (e.target !== this.game.app.canvas) return;

        const mouseTile = this.game.input.screenToTile(e.clientX, e.clientY);
        const tile = this.adjustTileForBuildingSize(mouseTile.x, mouseTile.y);
        this.placeBuilding(tile.x, tile.y);
    }

    /**
     * Handle mouse down for drag-and-drop building
     */
    onMouseDown(e) {
        if (!this.isActive || e.button !== 0) return;
        if (e.target !== this.game.app.canvas) return;

        const entityType = this.game.entityTypes[this.entityTypeId];
        if (!entityType) return;

        // Determine mode: drag for ship/transporter, click for others
        if (entityType.type === 'ship' || entityType.type === 'transporter') {
            this.startDragging(e);
        }
    }

    /**
     * Handle mouse up to finish dragging
     */
    onMouseUp(e) {
        if (!this.isActive || !this.isDragging || e.button !== 0) return;

        this.finishDragging(e);
    }

    /**
     * Place building at tile position
     * Sends tile coordinates directly (not pixels)
     */
    async placeBuilding(tileX, tileY) {
        // Prevent double placement
        if (this.isPlacing) {
            console.log('[BuildMode.placeBuilding] Already placing, ignoring duplicate call');
            return;
        }

        this.isPlacing = true;

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

                // Remove old HQ from client if it was replaced by new HQ
                if (data.oldHqRemoved) {
                    const oldHqKey = `entity_${data.oldHqRemoved.entity_id}`;
                    this.game.entityData.delete(oldHqKey);
                    const oldHqSprite = this.game.loadedEntities.get(oldHqKey);
                    if (oldHqSprite) {
                        this.game.entityLayer.removeChild(oldHqSprite);
                        oldHqSprite.destroy();
                        this.game.loadedEntities.delete(oldHqKey);
                    }
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
        } finally {
            // Reset flag to allow next placement
            this.isPlacing = false;
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

    /**
     * Start dragging for mass building
     */
    startDragging(e) {
        this.isDragging = true;
        this.wasDragging = false;

        const mouseTile = this.game.input.screenToTile(e.clientX, e.clientY);
        const tile = this.adjustTileForBuildingSize(mouseTile.x, mouseTile.y);

        this.dragStartTile = { x: tile.x, y: tile.y };
        this.dragEndTile = { x: tile.x, y: tile.y };

        // Hide single preview
        if (this.previewSprite) {
            this.previewSprite.visible = false;
        }

        this.clearMultiPreviews();
    }

    /**
     * Update drag preview based on mouse position
     */
    updateDragPreview(screenX, screenY) {
        const mouseTile = this.game.input.screenToTile(screenX, screenY);
        const tile = this.adjustTileForBuildingSize(mouseTile.x, mouseTile.y);

        if (tile.x === this.dragEndTile.x && tile.y === this.dragEndTile.y) {
            return; // Position unchanged
        }

        this.dragEndTile = { x: tile.x, y: tile.y };

        const entityType = this.game.entityTypes[this.entityTypeId];

        if (entityType.type === 'ship') {
            this.updateShipAreaPreview();
        } else if (entityType.type === 'transporter') {
            this.updateConveyorPathPreview();
        }
    }

    /**
     * Update ship area preview (rectangular selection)
     * Uses flood-fill to validate connectivity
     */
    updateShipAreaPreview() {
        const tiles = this.calculateRectArea(
            this.dragStartTile.x, this.dragStartTile.y,
            this.dragEndTile.x, this.dragEndTile.y
        );

        // For ship entities, use flood-fill validation to consider planned neighbors
        const validatedTiles = this.validateShipAreaWithFloodFill(tiles);

        this.createMultiPreviews(validatedTiles, this.entityTypeId);
    }

    /**
     * Calculate rectangular area between two points
     */
    calculateRectArea(x1, y1, x2, y2) {
        const minX = Math.min(x1, x2);
        const minY = Math.min(y1, y2);
        const maxX = Math.max(x1, x2);
        const maxY = Math.max(y1, y2);

        const tiles = [];
        for (let y = minY; y <= maxY; y++) {
            for (let x = minX; x <= maxX; x++) {
                tiles.push({ x, y });
            }
        }
        return tiles;
    }

    /**
     * Validate ship area using flood-fill to consider planned neighbors
     * Returns all tiles with validation status
     */
    validateShipAreaWithFloodFill(tiles) {
        // Create a set of planned positions for quick lookup
        const plannedSet = new Set();
        const tileMap = new Map();

        for (const tile of tiles) {
            const key = `${tile.x},${tile.y}`;
            plannedSet.add(key);
            tileMap.set(key, { ...tile, valid: false, visited: false });
        }

        // Find starting positions (valid by themselves - next to existing ship landing)
        const queue = [];

        for (const tile of tiles) {
            // Check if this position is valid on its own (has existing ship neighbor)
            const canPlace = this.checkPlacement(tile.x, tile.y);

            if (canPlace) {
                const key = `${tile.x},${tile.y}`;
                const tileInfo = tileMap.get(key);
                tileInfo.valid = true;
                tileInfo.visited = true;
                queue.push(tile);
            }
        }

        // If no valid starting positions, mark all as invalid and return
        if (queue.length === 0) {
            return Array.from(tileMap.values())
                .map(tile => ({ x: tile.x, y: tile.y, preValidated: true, valid: false }));
        }

        // Flood-fill from valid starting positions
        const directions = [
            { dx: 0, dy: 1 },  // down
            { dx: 0, dy: -1 }, // up
            { dx: 1, dy: 0 },  // right
            { dx: -1, dy: 0 }  // left
        ];

        while (queue.length > 0) {
            const current = queue.shift();

            // Check all 4 neighbors
            for (const dir of directions) {
                const nx = current.x + dir.dx;
                const ny = current.y + dir.dy;
                const neighborKey = `${nx},${ny}`;

                // If neighbor is in our planned area and not visited yet
                if (plannedSet.has(neighborKey)) {
                    const neighborTile = tileMap.get(neighborKey);

                    if (!neighborTile.visited) {
                        // Check basic placement rules (excluding ship neighbor check)
                        const canPlaceBasic = this.checkBasicPlacement(nx, ny);

                        if (canPlaceBasic) {
                            neighborTile.valid = true;
                            neighborTile.visited = true;
                            queue.push({ x: nx, y: ny });
                        } else {
                            // Mark as visited but invalid (obstacle, etc.)
                            neighborTile.visited = true;
                        }
                    }
                }
            }
        }

        // Return all tiles with validation status
        return Array.from(tileMap.values())
            .map(tile => ({ x: tile.x, y: tile.y, preValidated: true, valid: tile.valid }));
    }

    /**
     * Check basic placement rules without ship neighbor requirement
     * Used for flood-fill validation
     */
    checkBasicPlacement(tileX, tileY) {
        const entityType = this.game.entityTypes[this.entityTypeId];
        if (!entityType) {
            return false;
        }

        // For ship entities, check basic rules but skip neighbor requirement
        if (entityType.type === 'ship') {
            const behavior = EntityBehaviorFactory.create(this.game, this.entityTypeId);
            if (!behavior) return false;

            // 1. Check fog of war
            if (!behavior.areAllTilesVisible(tileX, tileY)) {
                return false;
            }

            // 2. Check entity collision
            if (behavior.hasEntityCollision(tileX, tileY)) {
                return false;
            }

            // 3. Check ship bounds (must be >= ship_attach)
            const region = this.game.gameData?.region;
            if (!region) return false;

            const shipAttachX = parseInt(region.ship_attach_x) || 0;
            const shipAttachY = parseInt(region.ship_attach_y) || 0;

            if (tileX < shipAttachX || tileY < shipAttachY) {
                return false; // Outside ship bounds
            }

            // Skip hasAdjacentMap check - that's handled by flood-fill
            return true;
        }

        // For non-ship entities, use standard validation
        return this.checkPlacement(tileX, tileY);
    }

    /**
     * Update conveyor path preview (linear path)
     */
    updateConveyorPathPreview() {
        const pathInfo = this.calculateConveyorPath(
            this.dragStartTile.x, this.dragStartTile.y,
            this.dragEndTile.x, this.dragEndTile.y
        );

        // Get entity_type_id with correct orientation
        const orientedTypeId = this.getOrientedEntityType(
            this.entityTypeId,
            pathInfo.orientation
        );

        this.createMultiPreviews(pathInfo.tiles, orientedTypeId);
    }

    /**
     * Calculate conveyor path and determine orientation
     */
    calculateConveyorPath(x1, y1, x2, y2) {
        const deltaX = x2 - x1;
        const deltaY = y2 - y1;

        // Determine direction (horizontal priority)
        const isHorizontal = Math.abs(deltaX) >= Math.abs(deltaY);

        let tiles, orientation;

        if (isHorizontal) {
            orientation = deltaX >= 0 ? 'right' : 'left';
            tiles = this.getHorizontalPath(x1, y1, x2);
        } else {
            orientation = deltaY >= 0 ? 'down' : 'up';
            tiles = this.getVerticalPath(x1, y1, y2);
        }

        // Stop at first obstacle
        tiles = this.stopAtObstacle(tiles);

        return { tiles, orientation };
    }

    /**
     * Get horizontal path from (x1, y) to (x2, y)
     */
    getHorizontalPath(x1, y, x2) {
        const tiles = [];
        const step = x2 >= x1 ? 1 : -1;

        for (let x = x1; step > 0 ? x <= x2 : x >= x2; x += step) {
            tiles.push({ x, y });
        }
        return tiles;
    }

    /**
     * Get vertical path from (x, y1) to (x, y2)
     */
    getVerticalPath(x, y1, y2) {
        const tiles = [];
        const step = y2 >= y1 ? 1 : -1;

        for (let y = y1; step > 0 ? y <= y2 : y >= y2; y += step) {
            tiles.push({ x, y });
        }
        return tiles;
    }

    /**
     * Stop path at first obstacle
     */
    stopAtObstacle(tiles) {
        const validTiles = [];

        for (const tile of tiles) {
            const canPlace = this.checkPlacement(tile.x, tile.y);
            if (!canPlace) break; // Stop at first obstacle
            validTiles.push(tile);
        }

        return validTiles;
    }

    /**
     * Get entity_type_id with correct orientation
     */
    getOrientedEntityType(baseTypeId, orientation) {
        const variants = this.getOrientationVariants(baseTypeId);

        for (const variantId of variants) {
            const entityType = this.game.entityTypes[variantId];
            if (entityType && entityType.orientation === orientation) {
                return variantId;
            }
        }

        return baseTypeId; // Fallback
    }

    /**
     * Create preview sprites for multiple tiles
     */
    createMultiPreviews(tiles, entityTypeId) {
        // Limit to 100 tiles for performance
        if (tiles.length > 100) {
            tiles = tiles.slice(0, 100);
        }

        this.clearMultiPreviews();

        const entityType = this.game.entityTypes[entityTypeId];
        if (!entityType) return;

        const texture = this.game.textures[`entity_${entityTypeId}_blueprint`];
        if (!texture) return;

        const { tileWidth, tileHeight } = this.game.config;

        for (const tile of tiles) {
            // Use pre-validated status if available (from flood-fill), otherwise check placement
            const canPlace = tile.preValidated !== undefined ? tile.valid : this.checkPlacement(tile.x, tile.y);

            const sprite = new PIXI.Sprite(texture);
            const pos = tileToWorld(tile.x, tile.y, tileWidth, tileHeight);

            sprite.x = pos.x;
            sprite.y = pos.y;
            sprite.zIndex = pos.y + PREVIEW_Z_OFFSET;
            sprite.tint = canPlace ? BUILD_VALID_COLOR : BUILD_INVALID_COLOR;
            sprite.alpha = canPlace ? BUILD_VALID_ALPHA : BUILD_INVALID_ALPHA;

            this.game.entityLayer.addChild(sprite);

            this.previewSprites.push({
                sprite,
                x: tile.x,
                y: tile.y,
                valid: canPlace,
                entityTypeId: entityTypeId
            });
        }
    }

    /**
     * Clear all preview sprites
     */
    clearMultiPreviews() {
        for (const item of this.previewSprites) {
            this.game.entityLayer.removeChild(item.sprite);
            item.sprite.destroy();
        }
        this.previewSprites = [];
    }

    /**
     * Get valid placements from preview sprites
     */
    getValidPlacements() {
        return this.previewSprites
            .filter(p => p.valid)
            .map(p => ({
                entity_type_id: p.entityTypeId,
                x: p.x,
                y: p.y,
                state: 'blueprint'
            }));
    }

    /**
     * Finish dragging and place buildings
     */
    async finishDragging(e) {
        this.isDragging = false;

        // Check if this was actually a drag or just a click
        const wasSingleClick = (this.dragStartTile.x === this.dragEndTile.x &&
                                this.dragStartTile.y === this.dragEndTile.y);

        if (wasSingleClick) {
            // Treat as regular click - don't set wasDragging flag
            this.wasDragging = false;
            this.clearMultiPreviews();

            // Restore single preview
            if (this.previewSprite) {
                this.previewSprite.visible = true;
            }

            // Place single building if valid
            if (this.canPlace) {
                await this.placeBuilding(this.dragStartTile.x, this.dragStartTile.y);
            }
            return;
        }

        // Was an actual drag - set flag to prevent onClick
        this.wasDragging = true;

        const placements = this.getValidPlacements();

        if (placements.length === 0) {
            this.clearMultiPreviews();

            // Restore single preview
            if (this.previewSprite) {
                this.previewSprite.visible = true;
            }
            return;
        }

        await this.placeMultipleBuildings(placements);
    }

    /**
     * Place multiple buildings via AJAX
     */
    async placeMultipleBuildings(placements) {
        try {
            const response = await fetch(this.game.config.createEntityUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCSRFToken()
                },
                body: JSON.stringify({ entities: placements })
            });

            const data = await response.json();

            if (data.result === 'ok' && data.entities) {
                // Deduct resources for each entity
                this.updateUserResourcesForMultiple(placements);

                // Remove deposits
                if (data.depositsRemoved && data.depositsRemoved.length > 0) {
                    const depositIds = data.depositsRemoved.map(d => d.deposit_id);
                    this.game.depositManager.removeDeposits(depositIds);
                }

                // Render new entities
                this.game.renderEntities(data.entities);

                // Handle eye entities for fog of war
                for (const entity of data.entities) {
                    this.handleEyeEntityPlacement(entity);
                }

                console.log(`Placed ${data.count} entities`);
            } else {
                console.error('Failed to place buildings:', data.error);
            }
        } catch (e) {
            console.error('Error placing buildings:', e);
        } finally {
            this.clearMultiPreviews();

            // Restore single preview
            if (this.previewSprite) {
                this.previewSprite.visible = true;
            }
        }
    }

    /**
     * Update user resources after mass building
     */
    updateUserResourcesForMultiple(placements) {
        for (const placement of placements) {
            const costs = this.game.entityTypeCosts[placement.entity_type_id];

            if (costs) {
                for (const [resourceId, quantity] of Object.entries(costs)) {
                    const rid = parseInt(resourceId);
                    this.game.userResources[rid] = (this.game.userResources[rid] || 0) - quantity;
                    if (this.game.userResources[rid] < 0) {
                        this.game.userResources[rid] = 0;
                    }
                }
            }
        }

        if (this.game.buildPanel) {
            this.game.buildPanel.updateAffordability();
        }

        if (this.game.resourcePanel) {
            this.game.resourcePanel.updateAll();
        }
    }
}

export default BuildMode;
