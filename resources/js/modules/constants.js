/**
 * Game constants
 */

// Sprite state suffixes for entities (7 states for first row of atlas)
export const SPRITE_STATES = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected', 'deleting', 'crafting'];

// Original 5 states (backward compatibility)
export const SPRITE_STATES_ORIGINAL = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];

// Construction frames (9 frames for second row of atlas)
export const CONSTRUCTION_FRAMES = [10, 20, 30, 40, 50, 60, 70, 80, 90];

// Special landing type IDs
export const LANDING_SKY_ID = 9;
export const LANDING_ISLAND_EDGE_ID = 10;

// Z-index values for layers
export const Z_INDEX = {
    SKY: 0,
    ISLAND_EDGE: 0.5,
    TERRAIN: 1,
    FOG: 9999
};

// Preview sprite offset
export const PREVIEW_Z_OFFSET = 1000;

// Throttle intervals (ms)
export const VIEWPORT_RELOAD_INTERVAL = 200;
export const SAVE_DEBOUNCE_DELAY = 500;
export const CAMERA_SAVE_DELAY = 5000;

// Fog of war settings
export const FOG_COLOR = 0x000000;
export const FOG_FULL_ALPHA = 0.95;
export const FOG_EDGE_ALPHA = 0.5;

// Build mode colors
export const BUILD_VALID_COLOR = 0x00ff00;
export const BUILD_INVALID_COLOR = 0xff0000;
export const BUILD_VALID_ALPHA = 0.7;
export const BUILD_INVALID_ALPHA = 0.5;

export default {
    SPRITE_STATES,
    SPRITE_STATES_ORIGINAL,
    CONSTRUCTION_FRAMES,
    LANDING_SKY_ID,
    LANDING_ISLAND_EDGE_ID,
    Z_INDEX,
    PREVIEW_Z_OFFSET,
    VIEWPORT_RELOAD_INTERVAL,
    SAVE_DEBOUNCE_DELAY,
    CAMERA_SAVE_DELAY,
    FOG_COLOR,
    FOG_FULL_ALPHA,
    FOG_EDGE_ALPHA,
    BUILD_VALID_COLOR,
    BUILD_INVALID_COLOR,
    BUILD_VALID_ALPHA,
    BUILD_INVALID_ALPHA
};
