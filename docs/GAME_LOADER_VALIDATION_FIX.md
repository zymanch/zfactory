# GameLoader Validation Fix (2026-01-15)

## Problem

Game was failing to load with cryptic error:
```
Uncaught TypeError: Cannot read properties of undefined (reading 'entities')
at new Game (game.js:2:315414)
```

## Root Cause (Multiple Issues)

### Issue 1: Old Initialization Code Conflict
1. **Duplicate initialization**: Both `game.js` and `bootstrap.js` had `DOMContentLoaded` listeners
2. **Old game.js code**: Called `new ZFactoryGame(window.gameConfig.configUrl)` with single parameter
3. **New constructor signature**: Expects 4 parameters: `(configData, entitiesData, tilesData, graphics)`
4. **Race condition**: Old code ran first, crashed before new bootstrap.js could run
5. **Error**: `Cannot read properties of undefined (reading 'entities')` in constructor

### Issue 2: Wrong Webpack Entry Point
1. **webpack.mix.js**: Used `game.js` as entry point
2. **After refactoring**: `game.js` is just a class, `bootstrap.js` is the entry point
3. **Result**: Webpack compiled wrong code (only dependencies, not full app)
4. **Symptom**: game.js was 18.6 KiB instead of ~980 KiB

### Issue 3: API Response Format Mismatch
1. **GameLoader**: Returned full API response `{ result: 'ok', entities: [...] }`
2. **Game constructor**: Expected just entities array `[...]`
3. **Result**: Constructor tried to access `entitiesData.entities` but got `undefined`

### Issue 4: Insufficient API Validation (Original Issue)
1. **API requires authentication**: Returns HTTP 302 redirect when not logged in
2. **No validation**: GameLoader tried to parse HTML as JSON
3. **No user feedback**: Silent failures with cryptic errors

### Issue 5: Wrong Conveyor Atlas Paths
1. **assetManifest paths**: Generated `/conveyor/left/` instead of `/conveyor/conveyor_left/`
2. **Real folder structure**: `conveyor/conveyor_left/`, `conveyor/conveyor_up/`, `conveyor/conveyor_down/`, `conveyor/` (right)
3. **Result**: 20 conveyor atlas textures failed to load (500 errors)
4. **Impact**: Conveyor belts didn't render correctly

### Issue 7: Non-Existent Pipe Atlas Files
1. **assetManifest**: Generated paths for pipe atlases with fluid states (water, oil, gas, empty)
2. **Generated keys**: `pipe_storage_tank_water`, `pipe_pump_oil`, etc. (16 total)
3. **Reality**: These files don't exist - pipes use entity_type.atlas_url instead
4. **Result**: 16 pipe atlas textures failed to load (500 errors)
5. **Impact**: Unnecessary failed requests, but no visual impact (fluid visualization handled by PipeRenderer at runtime)

### Issue 8: Wrong tilesData Type Check
1. **loadMapTiles() check**: `if (this.tilesData && this.tilesData.length > 0)`
2. **Reality**: `this.tilesData` is an object (dictionary `{"x_y": landing_id}`), not an array
3. **Result**: Check always failed (objects don't have `.length`), tiles never rendered
4. **Impact**: Map was completely invisible

### Issue 6: Field Name Mismatch
1. **GameLoader validation**: Checked for `data.landings` (with 's')
2. **API response**: Returns `landing` (without 's')
3. **Result**: "Missing required fields" error even when data was correct

## Solutions (4 Fixes Applied)

### Fix 1: Remove Duplicate Initialization

Removed old DOMContentLoaded code from `game.js` (lines 1001-1007):
```javascript
// REMOVED:
document.addEventListener('DOMContentLoaded', () => {
    const game = new ZFactoryGame(window.gameConfig.configUrl);
    game.init();
});
```

This code conflicted with new `bootstrap.js` initialization and called constructor with wrong signature.

### Fix 2: Update Webpack Entry Point

Changed `webpack.mix.js`:
```javascript
// OLD:
.js('resources/js/game.js', 'public/js')

// NEW:
.js('resources/js/bootstrap.js', 'public/js/game.js')
```

Now webpack correctly bundles all modules from bootstrap.js entry point.

### Fix 3: Return Clean Data from GameLoader

Modified GameLoader methods to return unwrapped data:
```javascript
// loadEntities()
return data.entities;  // Not { result: 'ok', entities: [...] }

// loadTiles()
return data.tiles;     // Not { result: 'ok', tiles: {...} }
```

Bootstrap wraps tiles for constructor compatibility:
```javascript
const tilesData = { tiles: tiles };
```

### Fix 4: Add Comprehensive API Validation

Added validation to GameLoader (`resources/js/core/GameLoader.js`):

### 1. HTTP Status Check
```javascript
if (!response.ok) {
    throw new Error(`Failed to load: HTTP ${response.status} ${response.statusText}`);
}
```

### 2. Content-Type Validation
```javascript
const contentType = response.headers.get('content-type');
if (!contentType || !contentType.includes('application/json')) {
    throw new Error(`Expected JSON, got ${contentType}. You may need to log in.`);
}
```

### 3. Response Structure Validation
```javascript
if (!data || typeof data !== 'object') {
    throw new Error('Invalid response format');
}

if (data.result !== 'ok') {
    throw new Error(data.error || 'Unknown error');
}
```

### 4. Required Fields Validation
```javascript
// For /game/config
if (!data.landings || !data.entityTypes || !data.config) {
    throw new Error('Missing required fields');
}

// For /game/entities
if (!Array.isArray(data.entities)) {
    throw new Error('Missing or invalid entities field');
}

// For /map/tiles
if (!data.tiles || typeof data.tiles !== 'object') {
    throw new Error('Missing or invalid tiles field');
}
```

### 5. Return Clean Data (CRITICAL FIX)

**Problem**: GameLoader was returning full API response `{ result: 'ok', entities: [...] }`, but Game constructor expected just the data.

**Solution**: Extract clean data from validated response:
```javascript
// loadEntities()
return data.entities;  // Returns array, not full response

// loadTiles()
return data.tiles;  // Returns object, not full response

// loadConfig()
return data;  // Returns full object (has many fields)
```

Bootstrap wraps tiles for compatibility:
```javascript
const tilesData = { tiles: tiles };
const game = new ZFactoryGame(config, entitiesData, tilesData, graphics);
```

### 5. User-Friendly Error Display

**NEW (2026-01-15)**: Errors now shown in modal dialog instead of just console/loading screen:

```javascript
catch (error) {
    console.error('[Bootstrap] Failed to initialize game:', error);

    // Hide loading screen
    document.getElementById('loading').style.display = 'none';

    // Show error in modal dialog with refresh/close buttons
    showErrorModal('Failed to Initialize Game', errorMessage, errorDetails);
}
```

**Modal Features**:
- ⚠️ Red warning icon and border
- Clear error message with details
- "Refresh Page" button (reloads page)
- "Close" button (dismisses modal)
- Dark theme consistent with game UI
- Centered overlay with backdrop
- HTML escaping to prevent XSS
- Smooth fade-in and slide-in animations
- ESC key and backdrop click to close

**Reusable Module**: `resources/js/modules/windows/ErrorModal.js`

Can be used anywhere in the application:
```javascript
import { ErrorModal } from './modules/windows/ErrorModal.js';

// Simple usage
ErrorModal.show('Error Title', 'Error message', 'Optional details');

// Advanced usage with custom handlers
const modal = new ErrorModal();
modal.show('Custom Error', 'Something went wrong', null, {
    showRefresh: false,
    showClose: true,
    onClose: () => console.log('Modal closed')
});
```

## Error Messages

### Before Fix
```
Uncaught TypeError: Cannot read properties of undefined (reading 'entities')
```
User sees: Loading screen stuck, cryptic error in console.

### After Fix
```
Error loading game: Failed to load entities: Expected JSON, got text/html. You may need to log in.

Please refresh the page and log in.
```
User sees: Clear error message in loading screen (red text) with actionable instructions.

## Common Scenarios

| Scenario | HTTP Status | Error Message |
|----------|-------------|---------------|
| Not logged in | 302 → 200 | "Expected JSON, got text/html. You may need to log in." |
| API error | 500 | "Failed to load: HTTP 500 Internal Server Error" |
| Network error | N/A | Native fetch error (network timeout, etc.) |
| Missing field | 200 | "Missing or invalid entities field" |
| Invalid JSON | 200 | Native JSON parse error |

## Files Modified

### 1. `resources/js/core/GameLoader.js`
- Added HTTP status validation (all 3 methods)
- Added Content-Type validation (all 3 methods)
- Added response structure validation (all 3 methods)
- Added required fields validation (all 3 methods)
- Updated error messages to be user-friendly
- **CRITICAL FIX**: Changed return values to return clean data instead of full response:
  - `loadConfig()` returns full response object (contains many fields)
  - `loadEntities()` returns `data.entities` array (not full response)
  - `loadTiles()` returns `data.tiles` object (not full response)

### 2. `resources/js/bootstrap.js`
- Improved error display in loading screen
- Added suggestion for authentication errors
- Added red color styling for error messages
- Preserved multi-line error messages with `white-space: pre-wrap`
- **CRITICAL FIX**: Wrap tiles in `{ tiles: tiles }` object for Game constructor compatibility
- Updated console.log to handle array entities directly

### 3. `src/views/game/index.php`
- Added cache busting with file modification timestamp: `/js/game.js?v={timestamp}`

### 4. `resources/js/game.js`
- **CRITICAL FIX**: Removed old DOMContentLoaded initialization code (lines 1001-1007)
- Old code conflicted with new bootstrap.js initialization
- Old code called `new ZFactoryGame(window.gameConfig.configUrl)` with single parameter
- New constructor expects 4 parameters: `(configData, entitiesData, tilesData, graphics)`

### 5. `webpack.mix.js`
- **CRITICAL FIX**: Changed entry point from `game.js` to `bootstrap.js`
- Old: `.js('resources/js/game.js', 'public/js')`
- New: `.js('resources/js/bootstrap.js', 'public/js/game.js')`
- game.js is now just a class, not an entry point

### 6. `src/commands/actions/game/Config.php` (2 fixes)

**Fix A: Conveyor atlas paths**
- **Problem**: Generated `/conveyor/left/` instead of `/conveyor/conveyor_left/`
- **Fix**: Added folder mapping logic
- Applied to all 4 orientations: right (conveyor/), left, up, down

**Fix B: Removed non-existent pipe atlases**
- **Problem**: Generated 16 pipe atlas keys for non-existent files (fluid states)
- **Old code**: `pipe_storage_tank_water`, `pipe_pump_oil`, etc.
- **Fix**: Removed old pipe atlas generation code
- **Reason**: Pipes use entity_type.atlas_url; fluid visualization handled by PipeRenderer at runtime

### 7. `resources/js/game.js` (loadMapTiles fix)
- **CRITICAL FIX**: Fixed tilesData type check
- Old: `if (this.tilesData && this.tilesData.length > 0)` (wrong - tilesData is object, not array)
- New: `if (this.tilesData && Object.keys(this.tilesData).length > 0)` (correct)
- **Impact**: Without this fix, map tiles never rendered

### 8. `docs/GAME_ENGINE.md`
- Added documentation for GameLoader validation
- Updated Bootstrap Flow section with error handling example

## Testing

### Manual Test - Not Logged In
1. Clear cookies (log out)
2. Navigate to `/game/play`
3. Expected result: Clear error message about needing to log in

### Manual Test - Logged In
1. Log in
2. Navigate to `/game/play`
3. Expected result: Game loads successfully

### cURL Test
```bash
# Without authentication (returns HTML)
curl -s "http://zfactory.local/game/entities"
# → null (followed redirect to /site/index)

# Check headers
curl -I "http://zfactory.local/game/entities"
# → HTTP/1.1 302 Found
# → Location: http://zfactory.local/site/index
```

## Prevention

This fix prevents similar issues by:
1. **Early detection**: Catches authentication/API errors before they propagate
2. **Clear messages**: Users know exactly what went wrong and what to do
3. **Type safety**: Validates data structure before passing to game logic
4. **Graceful degradation**: Game shows error instead of crashing

## Future Improvements

1. **Retry logic**: Auto-retry failed requests (for network issues)
2. **Offline detection**: Detect if user is offline and show specific message
3. **Session timeout**: Detect session expiry and offer re-login without refresh
4. **Loading progress**: Show which endpoint is currently loading
5. **Health check**: Pre-flight API health check before loading game data
