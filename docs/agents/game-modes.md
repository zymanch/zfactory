# Game Modes System Documentation

## Purpose

Centralized game mode controller that ensures only one mode is active at a time, with proper lifecycle management and event handling.

## GameModeManager

### Location
`resources/js/modules/modes/gameModeManager.js`

### Purpose
Centralized game mode controller - ensures only one mode is active at a time.

**Refactored 2026-01**: Mode logic extracted into separate classes extending GameModeBase.

### Game Modes

**9 modes**:
- NORMAL - Default navigation mode
- BUILD - Building placement mode
- DELETE - Entity deletion mode
- ENTITY_INFO - Entity information window
- ENTITY_SELECTION_WINDOW - Building selection window
- LANDING_SELECTION_WINDOW - Landing selection window
- LANDING_EDIT - Landing tile editing mode
- DEPOSIT_SELECTION_WINDOW - Deposit selection window
- DEPOSIT_BUILD - Deposit placement mode

### Key Features

- Mode switching with deactivation/activation lifecycle
- Entity interactivity control (enable/disable hover based on mode)
- Mode-specific visual indicators (delete mode red banner)
- Triggers UI updates (hints panel, camera info)

### Key Methods

- `switchMode(newMode, data)` - switch to new mode with context data
- `returnToPreviousMode()` - go back to previous mode
- `returnToNormalMode()` - always return to NORMAL
- `isMode(mode)` - check if specific mode is active
- `setEntityInteractivity(enabled)` - enable/disable entity hover globally

## GameModeBase

### Location
`resources/js/modules/modes/gameModeBase.js`

### Purpose
**Added 2026-01**: Base class for all game modes providing lifecycle and event management.

### Lifecycle Management

- `init()` - One-time initialization (setup DOM, register global handlers)
- `activate(data)` - Activate mode with optional context data
- `onActivate(data)` - Hook for subclass activation logic
- `deactivate()` - Deactivate mode and cleanup
- `onDeactivate()` - Hook for subclass deactivation logic

### Event Management

- `addEventListener(target, eventName, handler, options)` - Register event listener
- `unbindAllEvents()` - Remove all registered event listeners (automatic on deactivate)
- Prevents memory leaks from accumulated event listeners
- Supports both DOM events and PIXI events (.on/.off)

### State Tracking

- `isActive` - Flag indicating if mode is currently active
- `canActivate(data)` - Validation hook for activation requirements

### Example Usage

```javascript
export class MyMode extends GameModeBase {
    init() {
        // One-time setup
        this.createUI();
        this.addEventListener(document, 'keydown', this.onKeyDown);
    }

    onActivate(data) {
        // Activate mode with data
        this.showUI();
    }

    onDeactivate() {
        // Cleanup (unbindAllEvents called automatically)
        this.hideUI();
    }
}
```

## Specific Modes

### BuildMode

**Location**: `resources/js/modules/modes/buildMode.js`

**Purpose**: Building placement on map

**Features**:
- Preview sprite follows mouse
- Green/red tint for valid/invalid placement
- Collision detection with existing entities and deposits
- Multi-tile entity support (width/height)
- AJAX POST to create entity
- **Deposit validation**: Extraction buildings check required deposit type
- **Rotation support**: Press **R** (or **К** on Russian layout) to rotate
  - Works for entities with orientation variants (conveyors, manipulators)
  - Cycles through: right → down → left → up
  - Groups variants by `parent_entity_type_id`

### LandingEditMode

**Location**: `resources/js/modules/modes/landingEditMode.js`

**Purpose**: Landing tile editing mode

**Features**:
- Select landing type from LandingWindow
- Click tiles to change their type
- Visual preview of selected landing
- Esc to exit mode

## UI Components

### CameraInfo

**Location**: `resources/js/modules/ui/CameraInfo.js`

**Purpose**: Top-left debug information panel

**Displays**:
- Current game mode display (NORMAL, BUILD, DELETE, etc.)
- Camera position (x, y) and zoom level
- Loaded tiles count
- Loaded entities count
- FPS with smoothed calculation (0.9 weight to previous frame)
- Updates every frame in game loop

### ControlsHint

**Location**: `resources/js/modules/ui/ControlsHint.js`

**Purpose**: Bottom-left keyboard hints panel

**Features**:
- Dynamic hints based on current game mode
- Mode-specific control lists
- Updates when mode changes (triggered by GameModeManager)

**Mode-Specific Hints**:
- **NORMAL**: WASD, Wheel, B, L, 1-0, Delete, Click entity
- **BUILD**: WASD, Wheel, R rotate, Click place, Esc cancel
- **DELETE**: WASD, Wheel, Click delete, Delete/Esc exit
- **ENTITY_INFO**: WASD, Wheel, Esc close
- **Windows**: Esc close

## Integration

### Initialization

```javascript
// In game.js
this.gameModeManager = new GameModeManager(this);

// Register all modes
this.gameModeManager.registerMode(GameMode.BUILD, new BuildMode(this));
this.gameModeManager.registerMode(GameMode.LANDING_EDIT, new LandingEditMode(this));
// ... register other modes

// Initialize all modes
this.gameModeManager.initAll();

// Start in NORMAL mode
this.gameModeManager.switchMode(GameMode.NORMAL);
```

### Mode Switching

```javascript
// Switch to BUILD mode with entity type data
this.game.gameModeManager.switchMode(GameMode.BUILD, {
    entityTypeId: 100,
    orientation: 'right'
});

// Return to previous mode
this.game.gameModeManager.returnToPreviousMode();

// Always return to NORMAL
this.game.gameModeManager.returnToNormalMode();
```

### Input Handling

```javascript
// In inputManager.js
handleKeyDown(event) {
    if (event.key === 'Escape') {
        // Cancel current mode
        this.game.gameModeManager.returnToNormalMode();
    }

    if (event.key === 'b') {
        // Open buildings window
        this.game.gameModeManager.switchMode(GameMode.ENTITY_SELECTION_WINDOW);
    }

    if (event.key === 'Delete') {
        // Enter delete mode
        this.game.gameModeManager.switchMode(GameMode.DELETE);
    }
}
```

## Workflows

### Adding New Mode

1. Create mode class extending GameModeBase
2. Implement `init()`, `onActivate()`, `onDeactivate()` hooks
3. Register in GameModeManager during initialization
4. Add mode enum constant
5. Add keyboard binding in InputManager
6. Update ControlsHint with new mode hints

### Event Cleanup Pattern

```javascript
class MyMode extends GameModeBase {
    init() {
        // Register events that persist across activations
        this.addEventListener(document, 'keydown', this.onKeyDown);
    }

    onActivate(data) {
        // Register temporary events for this activation
        this.addEventListener(this.canvas, 'click', this.onClick);
    }

    onDeactivate() {
        // unbindAllEvents() called automatically
        // Removes both persistent and temporary events
    }
}
```

## File Locations

- **GameModeManager**: `resources/js/modules/modes/gameModeManager.js`
- **GameModeBase**: `resources/js/modules/modes/gameModeBase.js`
- **BuildMode**: `resources/js/modules/modes/buildMode.js`
- **LandingEditMode**: `resources/js/modules/modes/landingEditMode.js`
- **CameraInfo**: `resources/js/modules/ui/CameraInfo.js`
- **ControlsHint**: `resources/js/modules/ui/ControlsHint.js`
