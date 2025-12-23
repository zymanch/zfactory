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
        this.landingAtlases = {};          // Texture atlases for landings
        this.atlasPadding = 0;             // Padding between sprites in atlas
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

        // Third pass: auto-insert sky to the left of landings with empty space on the left
        const skyTilesToInsert = [];
        for (const tile of tiles) {
            // Skip sky itself
            if (tile.landing_id === LANDING_SKY_ID) {
                continue;
            }

            // Check if there's no tile to the left of this one
            const leftKey = tileKey(tile.x - 1, tile.y);
            const leftLandingId = this.tileDataMap.get(leftKey);

            // If left is empty, insert sky
            if (leftLandingId === undefined) {
                skyTilesToInsert.push({ x: tile.x - 1, y: tile.y });
            }
        }

        // Insert sky tiles
        for (const pos of skyTilesToInsert) {
            const key = tileKey(pos.x, pos.y);
            this.tileDataMap.set(key, LANDING_SKY_ID);
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
     * Create a tile sprite with transition support using texture atlases
     * @param {number} landingId - Current tile landing ID
     * @param {number} tileX - Tile X position
     * @param {number} tileY - Tile Y position
     * @returns {PIXI.Sprite|null}
     */
    createTileWithTransitions(landingId, tileX, tileY) {
        const landing = this.game.gameData.landings[landingId];
        if (!landing) return null;

        const atlasName = landing.image_url.replace('.png', '') + '_atlas';
        const atlas = this.landingAtlases[atlasName];

        if (!atlas) {
            console.warn('Atlas not loaded:', atlasName);
            return null;
        }

        // Get adjacent tile landing IDs (null if sky/empty)
        const topLandingId = this.getLandingAt(tileX, tileY - 1);
        const rightLandingId = this.getLandingAt(tileX + 1, tileY);

        // Determine adjacency info for atlas coordinates
        const adjacencyInfo = {
            top: topLandingId !== undefined ? topLandingId : null,
            right: rightLandingId !== undefined ? rightLandingId : null
        };

        // Get atlas coordinates
        const coords = this.getAtlasCoordinates(landingId, adjacencyInfo);

        // Create texture from atlas region with 0.5px inset to prevent bleeding
        const { tileWidth, tileHeight } = this.game.config;
        const inset = 0.5;

        const rect = new PIXI.Rectangle(
            coords.col * tileWidth + inset,
            coords.row * tileHeight + inset,
            tileWidth - inset * 2,
            tileHeight - inset * 2
        );

        const texture = new PIXI.Texture({
            source: atlas.source,
            frame: rect
        });

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

    /**
     * Вычисляет координаты в атласе на основе соседей
     * Использует landing_id напрямую вместо atlas_z
     *
     * @param {number} landingId ID текущего лендинга
     * @param {object} adjacencyInfo { top: landingId|null, right: landingId|null }
     * @returns {object} { row, col }
     */
    getAtlasCoordinates(landingId, adjacencyInfo) {
        const { top, right } = adjacencyInfo;
        const landing = this.game.gameData.landings[landingId];
        const variationsCount = landing?.variations_count || 5;

        // Если оба соседа совпадают с текущим лендингом - используем вариации из row 0
        if (top === landingId && right === landingId) {
            return {
                row: 0,
                col: Math.floor(Math.random() * variationsCount)
            };
        }

        // Иначе используем переходы
        let row, col;

        // Определяем строку по соседу сверху
        // Формула: row = top_landing_id + 1
        if (top === null) {
            row = LANDING_SKY_ID + 1;  // 10
        } else {
            row = top + 1;  // Для lava (id=5) сверху: row = 6
        }

        // Определяем колонку по соседу справа
        // Формула: col = right_landing_id
        if (right === null) {
            col = LANDING_SKY_ID;  // 9
        } else {
            col = right;  // Для lava (id=5) справа: col = 5
        }

        return { row, col };
    }
}

export default TileLayerManager;
