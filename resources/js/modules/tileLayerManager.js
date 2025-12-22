import * as PIXI from 'pixi.js';
import { tileKey } from './utils.js';
import { Z_INDEX, LANDING_SKY_ID, LANDING_ISLAND_EDGE_ID } from './constants.js';

// Landing IDs that should not participate in transitions
const NON_TRANSITION_LANDINGS = new Set([LANDING_SKY_ID, LANDING_ISLAND_EDGE_ID]);

/**
 * TileLayerManager - manages terrain tile rendering
 * Handles sky tiles, island edge tiles, and real terrain tiles
 */
export class TileLayerManager {
    constructor(game) {
        this.game = game;
        this.loadedTiles = new Map();      // Real tiles from DB
        this.skyTiles = new Map();         // Sky tiles for empty spaces
        this.islandEdgeTiles = new Map();  // Island edge tiles (auto-generated)
        this.tileDataMap = new Map();      // Map of x_y -> landing_id
    }

    /**
     * Store tile data from server
     * Auto-insert island_edge tiles under landings with no tile below
     * @param {Array} tiles - Array of tile objects
     */
    storeTileData(tiles) {
        // First pass: store all tiles
        for (const tile of tiles) {
            const key = tileKey(tile.x, tile.y);
            this.tileDataMap.set(key, tile.landing_id);
        }

        // Second pass: auto-insert island_edge under landings with empty space below
        const islandEdgesToInsert = [];
        for (const tile of tiles) {
            // Skip sky and island_edge themselves
            if (tile.landing_id === LANDING_SKY_ID || tile.landing_id === LANDING_ISLAND_EDGE_ID) {
                continue;
            }

            // Check if there's no tile below this one
            const belowKey = tileKey(tile.x, tile.y + 1);
            const belowLandingId = this.tileDataMap.get(belowKey);

            // If below is empty or sky, insert island_edge
            if (belowLandingId === undefined || belowLandingId === LANDING_SKY_ID) {
                islandEdgesToInsert.push({ x: tile.x, y: tile.y + 1 });
            }
        }

        // Insert island_edge tiles
        for (const pos of islandEdgesToInsert) {
            const key = tileKey(pos.x, pos.y);
            this.tileDataMap.set(key, LANDING_ISLAND_EDGE_ID);
        }
    }

    /**
     * Check if a tile exists at position
     * @param {number} x - Tile X
     * @param {number} y - Tile Y
     * @returns {boolean}
     */
    hasTileAt(x, y) {
        return this.tileDataMap.has(tileKey(x, y));
    }

    /**
     * Get landing ID at position
     * @param {number} x - Tile X
     * @param {number} y - Tile Y
     * @returns {number|undefined}
     */
    getLandingAt(x, y) {
        return this.tileDataMap.get(tileKey(x, y));
    }

    /**
     * Check if real tile exists at position
     * @param {string} key - Tile key
     * @returns {boolean}
     */
    hasLoadedTile(key) {
        return this.loadedTiles.has(key);
    }

    /**
     * Render all terrain tiles including auto-generated island_edge (called once on init)
     * @param {Array} tiles - Array of tile objects
     */
    renderTiles(tiles) {
        const { tileWidth, tileHeight } = this.game.config;

        // Render all tiles from tileDataMap (includes auto-inserted island_edge)
        for (const [key, landingId] of this.tileDataMap) {
            if (!this.loadedTiles.has(key)) {
                // Parse x, y from key "x_y"
                const [x, y] = key.split('_').map(Number);

                const sprite = this.createTileWithTransitions(landingId, x, y);
                if (sprite) {
                    this.game.landingLayer.addChild(sprite);
                    this.loadedTiles.set(key, sprite);
                }
            }
        }

        this.game.updateDebug('tiles', this.loadedTiles.size);
    }

    /**
     * Create a tile sprite with transition support
     * @param {number} landingId - Current tile landing ID
     * @param {number} tileX - Tile X position
     * @param {number} tileY - Tile Y position
     * @returns {PIXI.Sprite|null}
     */
    createTileWithTransitions(landingId, tileX, tileY) {
        // Skip transitions for sky and island_edge
        if (NON_TRANSITION_LANDINGS.has(landingId)) {
            return this.createTileSprite(landingId, tileX, tileY, Z_INDEX.TERRAIN);
        }

        // Get adjacent tile landing IDs
        const topLandingId = this.getLandingAt(tileX, tileY - 1);
        const rightLandingId = this.getLandingAt(tileX + 1, tileY);

        // Check if transitions are needed with other terrain types
        const needsTopTransition = topLandingId !== undefined
            && topLandingId !== landingId
            && !NON_TRANSITION_LANDINGS.has(topLandingId)
            && this.game.hasLandingAdjacency(landingId, topLandingId);

        const needsRightTransition = rightLandingId !== undefined
            && rightLandingId !== landingId
            && !NON_TRANSITION_LANDINGS.has(rightLandingId)
            && this.game.hasLandingAdjacency(landingId, rightLandingId);

        // Check if edges border sky (undefined = empty = sky)
        const needsSkyTopTransition = topLandingId === undefined || topLandingId === LANDING_SKY_ID;
        const needsSkyRightTransition = rightLandingId === undefined || rightLandingId === LANDING_SKY_ID;

        // Determine texture key
        let textureKey;
        if (needsTopTransition && needsRightTransition) {
            // Corner transition (both top and right are different terrain)
            textureKey = `transition_${landingId}_${topLandingId}_rt`;
        } else if (needsTopTransition) {
            textureKey = `transition_${landingId}_${topLandingId}_t`;
        } else if (needsRightTransition) {
            textureKey = `transition_${landingId}_${rightLandingId}_r`;
        } else if (needsSkyTopTransition && needsSkyRightTransition) {
            // Both top and right border sky - try to use corner transition texture
            textureKey = `transition_${landingId}_${LANDING_SKY_ID}_rt`;
        } else if (needsSkyTopTransition) {
            // Top edge borders sky
            textureKey = `transition_${landingId}_${LANDING_SKY_ID}_t`;
        } else if (needsSkyRightTransition) {
            // Right edge borders sky
            textureKey = `transition_${landingId}_${LANDING_SKY_ID}_r`;
        } else {
            textureKey = `landing_${landingId}`;
        }

        // Try to get transition texture, fallback to base texture
        let texture = this.game.textures[textureKey];
        if (!texture) {
            texture = this.game.textures[`landing_${landingId}`];
        }

        if (!texture) return null;

        const { tileWidth, tileHeight } = this.game.config;
        const sprite = new PIXI.Sprite(texture);
        sprite.x = tileX * tileWidth;
        sprite.y = tileY * tileHeight;
        sprite.width = tileWidth;
        sprite.height = tileHeight;
        sprite.zIndex = Z_INDEX.TERRAIN;

        return sprite;
    }

    /**
     * Render sky tiles for viewport (fills empty spaces)
     * Uses transition textures when there's a real landing to the right
     * @param {number} startX - Start tile X
     * @param {number} startY - Start tile Y
     * @param {number} width - Viewport width in tiles
     * @param {number} height - Viewport height in tiles
     */
    renderSkyTiles(startX, startY, width, height) {
        const baseTexture = this.game.textures['landing_' + LANDING_SKY_ID];
        if (!baseTexture) return;

        const { tileWidth, tileHeight } = this.game.config;
        const newKeys = new Set();

        for (let x = startX; x < startX + width; x++) {
            for (let y = startY; y < startY + height; y++) {
                const key = tileKey(x, y);
                newKeys.add(key);

                // Skip if real tile exists
                if (this.tileDataMap.has(key)) continue;
                // Skip if already rendered
                if (this.skyTiles.has(key)) continue;

                // Check if there's a real landing to the right
                const rightLandingId = this.tileDataMap.get(tileKey(x + 1, y));
                let texture = baseTexture;

                if (rightLandingId !== undefined &&
                    rightLandingId !== LANDING_SKY_ID &&
                    rightLandingId !== LANDING_ISLAND_EDGE_ID) {
                    // Use transition texture: sky with landing on right
                    const transitionKey = `transition_${LANDING_SKY_ID}_${rightLandingId}_r`;
                    if (this.game.textures[transitionKey]) {
                        texture = this.game.textures[transitionKey];
                    }
                }

                const sprite = new PIXI.Sprite(texture);
                sprite.x = x * tileWidth;
                sprite.y = y * tileHeight;
                sprite.width = tileWidth;
                sprite.height = tileHeight;
                sprite.zIndex = Z_INDEX.SKY;

                this.game.landingLayer.addChild(sprite);
                this.skyTiles.set(key, sprite);
            }
        }

        this.cleanupSprites(this.skyTiles, newKeys);
    }


    /**
     * Create a tile sprite
     * @param {number} landingId - Landing type ID
     * @param {number} tileX - Tile X position
     * @param {number} tileY - Tile Y position
     * @param {number} zIndex - Z-index for layering
     * @returns {PIXI.Sprite|null}
     */
    createTileSprite(landingId, tileX, tileY, zIndex) {
        const texture = this.game.textures['landing_' + landingId];
        if (!texture) return null;

        const { tileWidth, tileHeight } = this.game.config;
        const sprite = new PIXI.Sprite(texture);
        sprite.x = tileX * tileWidth;
        sprite.y = tileY * tileHeight;
        sprite.width = tileWidth;
        sprite.height = tileHeight;
        sprite.zIndex = zIndex;

        return sprite;
    }

    /**
     * Remove sprites not in the new keys set
     * @param {Map} spriteMap - Map of key -> sprite
     * @param {Set} newKeys - Set of keys to keep
     */
    cleanupSprites(spriteMap, newKeys) {
        for (const [key, sprite] of spriteMap) {
            if (!newKeys.has(key)) {
                this.game.landingLayer.removeChild(sprite);
                sprite.destroy();
                spriteMap.delete(key);
            }
        }
    }

    /**
     * Get total loaded tiles count
     * @returns {number}
     */
    get tilesCount() {
        return this.loadedTiles.size;
    }

    /**
     * Update a single tile (for landing editor)
     * @param {number} x - Tile X
     * @param {number} y - Tile Y
     * @param {number} landingId - New landing ID
     */
    updateTile(x, y, landingId) {
        const key = tileKey(x, y);

        // Update data map
        this.tileDataMap.set(key, landingId);

        // Remove old sprite if exists
        const oldSprite = this.loadedTiles.get(key);
        if (oldSprite) {
            this.game.landingLayer.removeChild(oldSprite);
            oldSprite.destroy();
            this.loadedTiles.delete(key);
        }

        // Create new sprite with transitions
        const sprite = this.createTileWithTransitions(landingId, x, y);
        if (sprite) {
            this.game.landingLayer.addChild(sprite);
            this.loadedTiles.set(key, sprite);
        }

        // Also update adjacent tiles (they might need new transition sprites)
        this.refreshAdjacentTiles(x, y);

        this.game.updateDebug('tiles', this.loadedTiles.size);
    }

    /**
     * Remove a tile (for landing editor - set to sky)
     * @param {number} x - Tile X
     * @param {number} y - Tile Y
     */
    removeTile(x, y) {
        const key = tileKey(x, y);

        // Remove from data map
        this.tileDataMap.delete(key);

        // Remove sprite
        const sprite = this.loadedTiles.get(key);
        if (sprite) {
            this.game.landingLayer.removeChild(sprite);
            sprite.destroy();
            this.loadedTiles.delete(key);
        }

        // Refresh adjacent tiles
        this.refreshAdjacentTiles(x, y);

        this.game.updateDebug('tiles', this.loadedTiles.size);
    }

    /**
     * Refresh adjacent tiles to update their transitions
     * @param {number} x - Center tile X
     * @param {number} y - Center tile Y
     */
    refreshAdjacentTiles(x, y) {
        const adjacent = [
            [x - 1, y], [x + 1, y], [x, y - 1], [x, y + 1]
        ];

        for (const [ax, ay] of adjacent) {
            const key = tileKey(ax, ay);
            const landingId = this.tileDataMap.get(key);

            if (landingId !== undefined) {
                // Re-render this tile to update transitions
                const oldSprite = this.loadedTiles.get(key);
                if (oldSprite) {
                    this.game.landingLayer.removeChild(oldSprite);
                    oldSprite.destroy();
                    this.loadedTiles.delete(key);
                }

                const sprite = this.createTileWithTransitions(landingId, ax, ay);
                if (sprite) {
                    this.game.landingLayer.addChild(sprite);
                    this.loadedTiles.set(key, sprite);
                }
            }
        }
    }
}

export default TileLayerManager;
