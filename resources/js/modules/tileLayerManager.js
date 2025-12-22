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
     * @param {Array} tiles - Array of tile objects
     */
    storeTileData(tiles) {
        for (const tile of tiles) {
            const key = tileKey(tile.x, tile.y);
            this.tileDataMap.set(key, tile.landing_id);
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
     * Render all real terrain tiles (called once on init)
     * @param {Array} tiles - Array of tile objects
     */
    renderTiles(tiles) {
        const { tileWidth, tileHeight } = this.game.config;

        for (const tile of tiles) {
            const key = tileKey(tile.x, tile.y);

            if (!this.loadedTiles.has(key)) {
                const sprite = this.createTileWithTransitions(
                    tile.landing_id,
                    tile.x,
                    tile.y
                );
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

        // Check if transitions are needed
        const needsTopTransition = topLandingId !== undefined
            && topLandingId !== landingId
            && !NON_TRANSITION_LANDINGS.has(topLandingId)
            && this.game.hasLandingAdjacency(landingId, topLandingId);

        const needsRightTransition = rightLandingId !== undefined
            && rightLandingId !== landingId
            && !NON_TRANSITION_LANDINGS.has(rightLandingId)
            && this.game.hasLandingAdjacency(landingId, rightLandingId);

        // Determine texture key
        let textureKey;
        if (needsTopTransition && needsRightTransition) {
            // Corner transition (both top and right different)
            // For corner case, we use the same adjacent ID for simplicity
            // More complex: could support different top and right adjacents
            textureKey = `transition_${landingId}_${topLandingId}_rt`;
        } else if (needsTopTransition) {
            textureKey = `transition_${landingId}_${topLandingId}_t`;
        } else if (needsRightTransition) {
            textureKey = `transition_${landingId}_${rightLandingId}_r`;
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
     * @param {number} startX - Start tile X
     * @param {number} startY - Start tile Y
     * @param {number} width - Viewport width in tiles
     * @param {number} height - Viewport height in tiles
     */
    renderSkyTiles(startX, startY, width, height) {
        const texture = this.game.textures['landing_' + LANDING_SKY_ID];
        if (!texture) return;

        const newKeys = new Set();

        for (let x = startX; x < startX + width; x++) {
            for (let y = startY; y < startY + height; y++) {
                const key = tileKey(x, y);
                newKeys.add(key);

                // Skip if real tile exists
                if (this.tileDataMap.has(key)) continue;
                // Skip if already rendered
                if (this.skyTiles.has(key)) continue;

                const sprite = this.createTileSprite(LANDING_SKY_ID, x, y, Z_INDEX.SKY);
                if (sprite) {
                    this.game.landingLayer.addChild(sprite);
                    this.skyTiles.set(key, sprite);
                }
            }
        }

        this.cleanupSprites(this.skyTiles, newKeys);
    }

    /**
     * Render island edge tiles under terrain with empty space below
     * @param {number} startX - Start tile X
     * @param {number} startY - Start tile Y
     * @param {number} width - Viewport width in tiles
     * @param {number} height - Viewport height in tiles
     */
    renderIslandEdgeTiles(startX, startY, width, height) {
        const texture = this.game.textures['landing_' + LANDING_ISLAND_EDGE_ID];
        if (!texture) return;

        const newKeys = new Set();

        for (let x = startX; x < startX + width; x++) {
            for (let y = startY; y < startY + height; y++) {
                const aboveKey = tileKey(x, y - 1);
                const currentKey = tileKey(x, y);

                const aboveLandingId = this.tileDataMap.get(aboveKey);
                const currentLandingId = this.tileDataMap.get(currentKey);

                // Render if: tile above exists (not sky) AND current is empty/sky
                const hasRealTileAbove = aboveLandingId !== undefined && aboveLandingId !== LANDING_SKY_ID;
                const currentIsEmpty = currentLandingId === undefined || currentLandingId === LANDING_SKY_ID;

                if (hasRealTileAbove && currentIsEmpty) {
                    newKeys.add(currentKey);

                    if (!this.islandEdgeTiles.has(currentKey)) {
                        const sprite = this.createTileSprite(LANDING_ISLAND_EDGE_ID, x, y, Z_INDEX.ISLAND_EDGE);
                        if (sprite) {
                            this.game.landingLayer.addChild(sprite);
                            this.islandEdgeTiles.set(currentKey, sprite);
                        }
                    }
                }
            }
        }

        this.cleanupSprites(this.islandEdgeTiles, newKeys);
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
}

export default TileLayerManager;
