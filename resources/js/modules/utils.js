/**
 * Utility functions shared across modules
 */

/**
 * Convert screen coordinates to world coordinates
 * @param {number} screenX - Screen X position
 * @param {number} screenY - Screen Y position
 * @param {Object} camera - Camera object with x, y properties
 * @param {number} zoom - Current zoom level
 * @returns {{x: number, y: number}} World coordinates
 */
export function screenToWorld(screenX, screenY, camera, zoom) {
    return {
        x: camera.x + screenX / zoom,
        y: camera.y + screenY / zoom
    };
}

/**
 * Convert world coordinates to screen coordinates
 * @param {number} worldX - World X position
 * @param {number} worldY - World Y position
 * @param {Object} camera - Camera object with x, y properties
 * @param {number} zoom - Current zoom level
 * @returns {{x: number, y: number}} Screen coordinates
 */
export function worldToScreen(worldX, worldY, camera, zoom) {
    return {
        x: (worldX - camera.x) * zoom,
        y: (worldY - camera.y) * zoom
    };
}

/**
 * Convert world coordinates to tile coordinates
 * @param {number} worldX - World X position
 * @param {number} worldY - World Y position
 * @param {number} tileWidth - Tile width in pixels
 * @param {number} tileHeight - Tile height in pixels
 * @returns {{x: number, y: number}} Tile coordinates
 */
export function worldToTile(worldX, worldY, tileWidth, tileHeight) {
    return {
        x: Math.floor(worldX / tileWidth),
        y: Math.floor(worldY / tileHeight)
    };
}

/**
 * Convert tile coordinates to world (pixel) coordinates
 * @param {number} tileX - Tile X position
 * @param {number} tileY - Tile Y position
 * @param {number} tileWidth - Tile width in pixels
 * @param {number} tileHeight - Tile height in pixels
 * @returns {{x: number, y: number}} World coordinates
 */
export function tileToWorld(tileX, tileY, tileWidth, tileHeight) {
    return {
        x: tileX * tileWidth,
        y: tileY * tileHeight
    };
}

/**
 * Create tile key string from coordinates
 * @param {number} x - X coordinate
 * @param {number} y - Y coordinate
 * @returns {string} Key in format "x_y"
 */
export function tileKey(x, y) {
    return `${x}_${y}`;
}

/**
 * Parse tile key string to coordinates
 * @param {string} key - Key in format "x_y"
 * @returns {{x: number, y: number}} Coordinates
 */
export function parseTileKey(key) {
    const [x, y] = key.split('_').map(Number);
    return { x, y };
}

/**
 * Generate asset URL with version query string
 * @param {string} basePath - Base path to assets
 * @param {string} path - Asset path
 * @param {number} version - Asset version number
 * @returns {string} Full URL with version
 */
export function assetUrl(basePath, path, version = 1) {
    return `${basePath}${path}?v=${version}`;
}

/**
 * Generate entity icon URL
 * @param {Object} entityType - Entity type object
 * @param {string} tilesPath - Base tiles path
 * @param {number} version - Asset version
 * @returns {string} Icon URL
 */
export function getEntityIconUrl(entityType, tilesPath, version = 1) {
    const path = entityType.icon_url
        ? `entities/${entityType.icon_url}`
        : `entities/${entityType.image_url}/normal.${entityType.extension || 'svg'}`;
    return assetUrl(tilesPath, path, version);
}

/**
 * Get CSRF token from meta tag
 * @returns {string} CSRF token or empty string
 */
export function getCSRFToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * Check AABB collision between two rectangles
 * @param {number} x1 - First rect X
 * @param {number} y1 - First rect Y
 * @param {number} w1 - First rect width
 * @param {number} h1 - First rect height
 * @param {number} x2 - Second rect X
 * @param {number} y2 - Second rect Y
 * @param {number} w2 - Second rect width
 * @param {number} h2 - Second rect height
 * @returns {boolean} True if overlapping
 */
export function rectsOverlap(x1, y1, w1, h1, x2, y2, w2, h2) {
    return x1 < x2 + w2 && x1 + w1 > x2 && y1 < y2 + h2 && y1 + h1 > y2;
}

/**
 * Create debounced function
 * @param {Function} fn - Function to debounce
 * @param {number} delay - Delay in ms
 * @returns {Function} Debounced function
 */
export function debounce(fn, delay) {
    let timeoutId = null;
    return function(...args) {
        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), delay);
    };
}

export default {
    screenToWorld,
    worldToScreen,
    worldToTile,
    tileToWorld,
    tileKey,
    parseTileKey,
    assetUrl,
    getEntityIconUrl,
    getCSRFToken,
    rectsOverlap,
    debounce
};
