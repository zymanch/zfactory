import { SpatialIndex } from './SpatialIndex.js';
import { TransporterState } from './TransporterState.js';
import { ManipulatorState } from './ManipulatorState.js';
import { BuildingState } from './BuildingState.js';
import { SplitterState } from './SplitterState.js';
import { getCSRFToken } from '../utils.js';
import { ConveyorConnectionHelper } from '../ConveyorConnectionHelper.js';

/**
 * ResourceTransportManager - Main controller for resource movement
 * Handles conveyors, manipulators, building crafting
 */
export class ResourceTransportManager {
    constructor(game) {
        this.game = game;

        // State maps
        this.transporters = new Map();  // entity_id → TransporterState
        this.manipulators = new Map();  // entity_id → ManipulatorState
        this.splitters = new Map();     // entity_id → SplitterState
        this.buildings = new Map();     // entity_id → BuildingState

        // Spatial index for fast position lookups
        this.spatialIndex = new SpatialIndex();

        // Auto-save
        this.autoSaveInterval = (game.config?.autoSaveInterval || 60) * 1000; // ms
        this.lastSaveTime = 0;
        this.pendingSync = false;

        // Logic tick optimization: heavy logic runs every N ticks
        this.logicTickCounter = 0;
        this.LOGIC_TICK_INTERVAL = 30;  // Heavy logic runs every 30 ticks (~2x per second at 60fps)

        // Initialized flag
        this.initialized = false;

        // Visual debug indicator
        this.createLogicTickIndicator();
    }

    /**
     * Initialize the transport system
     */
    init() {
        this.buildStateFromEntities();
        this.calculateLinks();
        this.loadInitialState();
        this.initialized = true;
        this.tryStartAllCrafts();
    }

    /**
     * Load initial state from entities data
     * NEW (2026-01): Extract state from entity properties instead of separate arrays
     */
    loadInitialState() {
        const game = this.game;

        // Extract states from entity properties
        const entityResourcesMap = {};
        const craftingStatesArray = [];
        const transporterStates = [];
        const splitterStates = [];
        const manipulatorStates = [];

        // Support both production (entitiesData) and tests (entities)
        const entities = game.entitiesData || game.entities || [];

        for (const entity of entities) {
            const entityId = entity.entity_id;

            // Extract resources if present
            if (entity.resources && entity.resources.length > 0) {
                entityResourcesMap[entityId] = entity.resources;
            }

            // Extract crafting state if present
            if (entity.craftingState) {
                craftingStatesArray.push({
                    entity_id: entityId,
                    ...entity.craftingState
                });
            }

            // Extract transport state if present
            if (entity.transportState) {
                const ts = { entity_id: entityId, ...entity.transportState };

                if (this.transporters.has(entityId)) {
                    transporterStates.push(ts);
                } else if (this.splitters.has(entityId)) {
                    splitterStates.push(ts);
                } else if (this.manipulators.has(entityId)) {
                    manipulatorStates.push(ts);
                }
            }
        }

        // Backward compatibility: fallback to old format if new format is empty
        const finalEntityResources = Object.keys(entityResourcesMap).length > 0
            ? entityResourcesMap
            : (game.initialEntityResources || []);

        const finalCraftingStates = craftingStatesArray.length > 0
            ? craftingStatesArray
            : (game.initialCraftingStates || []);

        const finalTransporterStates = transporterStates.length > 0
            ? transporterStates
            : (game.initialTransportStates || []).filter(ts => this.transporters.has(ts.entity_id));

        const finalSplitterStates = splitterStates.length > 0
            ? splitterStates
            : (game.initialTransportStates || []).filter(ts => this.splitters.has(ts.entity_id));

        const finalManipulatorStates = manipulatorStates.length > 0
            ? manipulatorStates
            : (game.initialTransportStates || []).filter(ts => this.manipulators.has(ts.entity_id));

        // Load using existing loadState method
        this.loadState({
            entityResources: finalEntityResources,
            craftingStates: finalCraftingStates,
            transporterStates: finalTransporterStates,
            splitterStates: finalSplitterStates,
            manipulatorStates: finalManipulatorStates
        });
    }

    /**
     * Build state objects from game entities
     */
    buildStateFromEntities() {
        this.transporters.clear();
        this.manipulators.clear();
        this.splitters.clear();
        this.buildings.clear();
        this.spatialIndex.clear();

        for (const [key, entity] of this.game.entityData) {
            const entityType = this.game.entityTypes[entity.entity_type_id];
            if (!entityType) continue;

            // Add to spatial index with entity dimensions
            const width = parseInt(entityType.width) || 1;
            const height = parseInt(entityType.height) || 1;
            this.spatialIndex.add(entity, width, height);

            switch (entityType.type) {
                case 'conveyor':  // Conveyors (including underground belts)
                case 'transporter':
                    // Check if this is a splitter (has multiple outputs)
                    if (ConveyorConnectionHelper.isSplitter(entityType)) {
                        this.splitters.set(entity.entity_id, new SplitterState(entity, entityType, this.game));
                    } else {
                        this.transporters.set(entity.entity_id, new TransporterState(entity, entityType, this.game));
                    }
                    break;

                case 'manipulator':
                    this.manipulators.set(entity.entity_id, new ManipulatorState(entity, entityType, this.game));
                    break;

                case 'building':
                case 'mining':
                case 'storage':
                case 'special':
                case 'electricity':  // Add electricity type (generators, solar panels)
                    this.buildings.set(entity.entity_id, new BuildingState(entity, entityType, this.game));
                    break;
            }
        }
    }

    /**
     * Calculate links between entities
     */
    calculateLinks() {
        // Clear existing links
        for (const state of this.transporters.values()) {
            state.targetEntityId = null;
            state.sourceEntityIds = [];
            state.straightSourceId = null;
        }

        for (const state of this.splitters.values()) {
            state.inputEntityId = null;
            state.leftOutputEntityId = null;
            state.rightOutputEntityId = null;
        }

        for (const state of this.manipulators.values()) {
            state.sourceEntityId = null;
            state.targetEntityId = null;
        }

        // Calculate transporter targets
        for (const [entityId, state] of this.transporters) {
            const targetPos = this.getNextPosition(state.x, state.y, state.orientation);
            const targetEntityId = this.spatialIndex.getAt(targetPos.x, targetPos.y);

            if (targetEntityId) {
                // Only set target if it's NOT a manipulator
                // Manipulators pick from transporters (sourceEntityId), not receive from them (targetEntityId)
                if (!this.manipulators.has(targetEntityId)) {
                    state.targetEntityId = targetEntityId;
                }
            }
        }

        // Calculate transporter sources (reverse links)
        for (const [entityId, state] of this.transporters) {
            if (state.targetEntityId) {
                const targetState = this.transporters.get(state.targetEntityId);
                if (targetState) {
                    targetState.sourceEntityIds.push(entityId);

                    // Check if this is a straight source (same orientation)
                    if (state.orientation === targetState.orientation) {
                        targetState.straightSourceId = entityId;
                    }
                }
            }
        }

        // Calculate splitter links
        for (const [entityId, state] of this.splitters) {
            // Input: opposite direction from orientation
            const inputPos = state.getInputPosition();
            state.inputEntityId = this.spatialIndex.getAt(inputPos.x, inputPos.y);

            // Left output
            const leftPos = state.getLeftOutputPosition();
            state.leftOutputEntityId = this.spatialIndex.getAt(leftPos.x, leftPos.y);

            // Right output
            const rightPos = state.getRightOutputPosition();
            state.rightOutputEntityId = this.spatialIndex.getAt(rightPos.x, rightPos.y);
        }

        // Calculate manipulator source/target
        for (const [entityId, state] of this.manipulators) {
            const sourcePos = state.getSourcePosition();
            const targetPos = state.getTargetPosition();

            state.sourceEntityId = this.spatialIndex.getAt(sourcePos.x, sourcePos.y);
            state.targetEntityId = this.spatialIndex.getAt(targetPos.x, targetPos.y);
        }

        // Calculate underground conveyor pairs (IN → OUT)
        for (const [entityId, state] of this.transporters) {
            if (!state.isUndergroundIn) continue;

            // Find nearest underground OUT in same orientation
            let bestOut = null;
            let bestDistance = Infinity;

            for (const [outId, outState] of this.transporters) {
                if (!outState.isUndergroundOut) continue;
                if (outState.orientation !== state.orientation) continue;

                // Check if OUT is in front of IN in correct direction
                const dx = outState.x - state.x;
                const dy = outState.y - state.y;
                let isAhead = false;
                let distance = 0;

                switch (state.orientation) {
                    case 'right':
                        isAhead = dx > 0 && dy === 0;
                        distance = dx;
                        break;
                    case 'left':
                        isAhead = dx < 0 && dy === 0;
                        distance = Math.abs(dx);
                        break;
                    case 'down':
                        isAhead = dy > 0 && dx === 0;
                        distance = dy;
                        break;
                    case 'up':
                        isAhead = dy < 0 && dx === 0;
                        distance = Math.abs(dy);
                        break;
                }

                if (isAhead && distance < bestDistance && distance > 1) {
                    bestDistance = distance;
                    bestOut = outId;
                }
            }

            if (bestOut) {
                state.undergroundPairId = bestOut;
            }
        }
    }

    /**
     * Get next position based on orientation
     */
    getNextPosition(x, y, orientation, distance = 1) {
        switch (orientation) {
            case 'up':    return { x, y: y - distance };
            case 'down':  return { x, y: y + distance };
            case 'left':  return { x: x - distance, y };
            case 'right': return { x: x + distance, y };
            default:      return { x, y };
        }
    }

    /**
     * Main tick function - called every game tick (60fps)
     * Animation runs every tick, heavy logic runs every LOGIC_TICK_INTERVAL ticks
     */
    tick() {
        if (!this.initialized) return;

        // Animation tick (every frame) - smooth visual movement
        this.updateTransporterAnimation();
        this.updateSplitterAnimation();
        this.updateManipulatorAnimation();

        // Logic tick (every N frames) - state changes, transfers, crafting
        this.logicTickCounter++;

        // Conveyor logic tick (every 30 frames at counter=0)
        if (this.logicTickCounter >= this.LOGIC_TICK_INTERVAL) {
            this.logicTickCounter = 0;
            this.conveyorLogicTick();
        }

        // Manipulator logic tick (every 30 frames at counter=15, offset by 15 frames)
        if (this.logicTickCounter === 15) {
            this.manipulatorLogicTick();
        }

        // Auto-save check (time-based, ok to run every frame)
        this.checkAutoSave();
    }

    /**
     * Conveyor logic tick - runs every 30 frames at counter=0
     * Handles conveyors, splitters, and buildings
     */
    conveyorLogicTick() {
        // Flash visual indicator (red)
        this.flashLogicTickIndicator('red');

        // Update crafting progress and completion
        this.updateCrafting();

        // Check conveyor status transitions
        this.updateTransporterStatus();

        // Process transfers between conveyors
        this.processTransporterTransfers();
    }

    /**
     * Manipulator logic tick - runs every 30 frames at counter=15 (offset by 15 frames)
     * Handles manipulator pickup/place actions
     */
    manipulatorLogicTick() {
        // Flash visual indicator (green)
        this.flashLogicTickIndicator('green');

        // Process manipulator state transitions (pickup/place actions)
        this.processManipulatorActions();
    }

    /**
     * Update crafting processes in buildings
     */
    updateCrafting() {
        for (const [entityId, state] of this.buildings) {
            if (!state.isCrafting()) continue;

            state.craftingTicksRemaining--;

            if (state.craftingTicksRemaining <= 0) {
                // Crafting complete - add output
                const recipe = this.game.recipes[state.craftingRecipeId];
                if (recipe) {
                    const outputResourceId = parseInt(recipe.output_resource_id);
                    const outputAmount = parseInt(recipe.output_amount) || 1;
                    const outputResource = this.game.resources[recipe.output_resource_id];

                    console.log(`[Craft Complete] Entity ${entityId}: +${outputAmount} ${outputResource?.name || outputResourceId}`);
                    console.log(`[Craft Complete] About to check resource type...`);

                    // Check if output is fluid (resource_id 300-303)
                    const isFluid = outputResourceId >= 300 && outputResourceId <= 303;
                    // Check if output is electricity (resource_id 400)
                    const isElectricity = outputResourceId === 400;
                    console.log(`[Craft Complete] Is fluid: ${isFluid}, is electricity: ${isElectricity}`);

                    if (isFluid) {
                        console.log(`[Craft Complete] Entering fluid branch...`);
                        console.log(`[Pump] Fluid output detected: ${outputResourceId}, amount: ${outputAmount}`);

                        // Safety check for pipeSystemManager
                        if (!this.game) {
                            console.error(`[Pump] ERROR: this.game is undefined`);
                            return;
                        }

                        if (!this.game.pipeSystemManager) {
                            console.warn(`[Pump] pipeSystemManager not available, skipping fluid output`);
                            return;
                        }

                        // Try to push fluid to connected pipe system
                        const pipeEntityId = this.findOutputPipe(state.x, state.y);
                        console.log(`[Pump] Found pipe at output: ${pipeEntityId}`);

                        if (pipeEntityId) {
                            const success = this.game.pipeSystemManager.addFluid(
                                pipeEntityId,
                                outputResourceId,
                                outputAmount
                            );
                            if (!success) {
                                console.warn(`[Pump] Failed to add fluid to pipe system (overflow or mixing?)`);
                            } else {
                                console.log(`[Pump] Successfully added ${outputAmount} fluid to pipe system`);
                            }
                        } else {
                            console.warn(`[Pump] No pipe connected at output position (${state.x}, ${state.y})`);
                        }
                    } else if (isElectricity) {
                        // Safety check for electricityManager
                        if (!this.game || !this.game.electricityManager) {
                            state.addResource(outputResourceId, outputAmount);
                        } else {
                            // Get network of connected electricity entities
                            const network = this.game.electricityManager.getNetwork(entityId);
                            const batteries = [];

                            // Find all batteries in network (entity types 910-912)
                            for (const connectedId of network) {
                                const entityData = this.game.entityData.get(`entity_${connectedId}`);
                                if (!entityData) continue;

                                const entityTypeId = entityData.entity_type_id;
                                if (entityTypeId >= 910 && entityTypeId <= 912) {
                                    const batteryState = this.buildings.get(connectedId);
                                    if (batteryState) {
                                        batteries.push({
                                            id: connectedId,
                                            state: batteryState,
                                            entityType: this.game.entityTypes[entityTypeId]
                                        });
                                    }
                                }
                            }

                            let remainingOutput = outputAmount;

                            // Fill batteries sequentially (one at a time to full capacity)
                            for (const battery of batteries) {
                                if (remainingOutput <= 0) break;

                                const capacity = parseInt(battery.entityType.storage_per_resource) || 0;
                                const currentAmount = battery.state.getResourceAmount(outputResourceId);
                                const freeSpace = capacity - currentAmount;

                                if (freeSpace > 0) {
                                    const toTransfer = Math.min(remainingOutput, freeSpace);
                                    battery.state.addResource(outputResourceId, toTransfer);
                                    remainingOutput -= toTransfer;
                                }
                            }

                            // If electricity remains (no batteries or all full), store in generator
                            if (remainingOutput > 0) {
                                state.addResource(outputResourceId, remainingOutput);
                            }
                        }
                    } else {
                        // Normal resource - store in building
                        console.log(`[Craft Complete] Entering normal resource branch...`);
                        console.log(`[Craft Complete] About to call addResource(${outputResourceId}, ${outputAmount})`);
                        state.addResource(outputResourceId, outputAmount);
                        console.log(`[Craft Complete] addResource() completed`);
                    }
                }

                console.log(`[Craft Complete] Clearing crafting state...`);
                state.craftingRecipeId = null;
                state.craftingTicksRemaining = 0;
                this.pendingSync = true;

                console.log(`[Craft Complete] About to tryStartCraftForEntity(${entityId})...`);
                // Try to start a new craft immediately
                this.tryStartCraftForEntity(entityId);
                console.log(`[Craft Complete] tryStartCraftForEntity completed`);
            }
        }
    }

    /**
     * Animation: Move resources along conveyor belts (runs every tick)
     * Simply increment ticks - position calculated on-the-fly by renderer
     */
    updateTransporterAnimation() {
        for (const [entityId, state] of this.transporters) {
            if (state.isEmpty()) continue;

            // Don't animate if waiting for transfer
            if (state.status === 'waiting_transfer') continue;

            // Increment ticks based on power
            const increment = state.getTickIncrement();
            state.ticks += increment;

            // Check if reached end (30 ticks for power=100)
            if (state.ticks >= state.TICKS_PER_TILE) {
                state.ticks = state.TICKS_PER_TILE;
                state.status = 'waiting_transfer';
            }
        }
    }

    /**
     * Animation: Move resources along splitters (runs every tick)
     */
    updateSplitterAnimation() {
        for (const [entityId, state] of this.splitters) {
            state.update();
        }
    }

    /**
     * Logic: Check conveyor status transitions (runs every logic tick)
     */
    updateTransporterStatus() {
        for (const [entityId, state] of this.transporters) {
            if (state.isEmpty()) continue;

            // Check if reached end (30 ticks) and should wait for transfer
            if (state.ticks >= state.TICKS_PER_TILE && state.status === 'carrying') {
                state.status = 'waiting_transfer';
            }
        }
    }

    /**
     * Process transfers between conveyors (simultaneous for cycles)
     * NOTE: Dual-lane conveyor support is DISABLED in this version.
     * Dual-lane support requires storing 2 resources per conveyor as 2 separate entity_resource records.
     * This would require significant backend changes and is deferred to future implementation.
     * For now, all conveyors work as single-lane.
     */
    processTransporterTransfers() {
        // Phase 0: Handle underground conveyor transfer (when resource reaches end of IN)
        for (const [entityId, state] of this.transporters) {
            if (!state.isUndergroundIn) continue;
            if (state.status !== 'waiting_transfer') continue;
            if (!state.undergroundPairId) continue;

            const outState = this.transporters.get(state.undergroundPairId);
            if (!outState || !outState.isEmpty()) continue;

            // Calculate underground distance in tiles (edge-to-edge)
            const dx = Math.abs(outState.x - state.x);
            const dy = Math.abs(outState.y - state.y);
            const distanceTiles = (dx + dy) - 1;

            // Transfer to OUT with negative ticks for underground travel
            // Distance in tiles × ticks per tile = underground ticks
            const undergroundTicks = -(distanceTiles * state.TICKS_PER_TILE);

            const fromDirection = this.calculateFromDirection(state.undergroundPairId, entityId);
            outState.resourceId = state.resourceId;
            outState.resourceAmount = state.resourceAmount;
            outState.fromDirection = fromDirection;
            outState.ticks = undergroundTicks;
            outState.status = 'carrying';

            state.clear();
        }

        // Phase 1: Determine who will transfer
        for (const [entityId, state] of this.transporters) {
            state.willTransfer = false;

            if (state.status !== 'waiting_transfer') continue;

            // Underground IN conveyors transfer via teleportation (Phase 0), not via normal target
            if (state.isUndergroundIn) continue;

            if (!state.targetEntityId) continue;

            const canAccept = this.canEntityAccept(state.targetEntityId, state.resourceId, state.resourceAmount);

            if (canAccept === 'yes') {
                state.willTransfer = true;
            } else if (canAccept === 'yes_if_freed') {
                // Check if target is also waiting to transfer (cycle support)
                const targetState = this.transporters.get(state.targetEntityId);
                if (targetState && targetState.status === 'waiting_transfer') {
                    state.willTransfer = true;
                }
            }
        }

        // Phase 2: Collect all transfers
        const transfers = [];
        for (const [entityId, state] of this.transporters) {
            if (!state.willTransfer) continue;

            transfers.push({
                fromId: entityId,
                toId: state.targetEntityId,
                resourceId: state.resourceId,
                resourceAmount: state.resourceAmount
            });
        }

        if (transfers.length === 0) return;

        // Safety check
        if (!this.transporters || !this.buildings) {
            console.error('[ResourceTransport] transporters or buildings not initialized');
            return;
        }

        // Phase 3: Clear all sources
        for (const t of transfers) {
            const state = this.transporters.get(t.fromId);
            if (state) {
                state.clear();
            }
        }

        // Phase 4: Fill all targets
        const buildingsReceived = [];
        for (const t of transfers) {
            const targetState = this.transporters.get(t.toId);
            const resource = this.game.resources[t.resourceId];
            const resourceName = resource?.name || t.resourceId;

            if (targetState) {
                // Target is a transporter
                const fromDirection = this.calculateFromDirection(t.toId, t.fromId);
                // Let setResource determine starting position and phase based on entry direction
                targetState.setResource(t.resourceId, t.resourceAmount, fromDirection);
            } else {
                // Target is a building
                const buildingState = this.buildings.get(t.toId);
                if (buildingState) {
                    buildingState.addResource(t.resourceId, t.resourceAmount);
                    buildingsReceived.push(t.toId);
                }
            }
        }

        // Try to start crafting for buildings that received resources
        for (const entityId of buildingsReceived) {
            this.tryStartCraftForEntity(entityId);
        }

        // Phase 5: Pull from sources of freed conveyors
        if (this.transporters) {
            for (const t of transfers) {
                const freedState = this.transporters.get(t.fromId);
                if (freedState && freedState.isEmpty()) {
                    this.pullFromSources(freedState);
                }
            }
        }

        this.pendingSync = true;
    }

    /**
     * Determine from_direction based on source entity position
     * @param {number} targetEntityId - Target conveyor entity ID
     * @param {number} fromEntityId - Source entity ID
     * @returns {string} - 'up'|'down'|'left'|'right'
     */
    calculateFromDirection(targetEntityId, fromEntityId) {
        const targetEntity = this.game.entityData.get(`entity_${targetEntityId}`);
        const fromEntity = this.game.entityData.get(`entity_${fromEntityId}`);

        if (!targetEntity || !fromEntity) return 'down'; // default

        const dx = targetEntity.x - fromEntity.x;
        const dy = targetEntity.y - fromEntity.y;

        // Determine direction based on coordinate difference
        if (Math.abs(dx) > Math.abs(dy)) {
            return dx > 0 ? 'left' : 'right'; // Source is to the left or right
        } else {
            return dy > 0 ? 'up' : 'down'; // Source is above or below
        }
    }

    /**
     * Pull resources from source conveyors
     */
    pullFromSources(freedState) {
        // Priority 1: straight source (same orientation)
        if (freedState.straightSourceId) {
            const sourceState = this.transporters.get(freedState.straightSourceId);
            if (sourceState && sourceState.status === 'waiting_transfer') {
                this.doSingleTransfer(sourceState, freedState);
                this.pullFromSources(sourceState);
                return;
            }
        }

        // Priority 2: side sources (round-robin)
        const sideSourceIds = freedState.sourceEntityIds.filter(id => id !== freedState.straightSourceId);
        if (sideSourceIds.length === 0) return;

        freedState.lastSideSourceIndex = ((freedState.lastSideSourceIndex || 0) + 1) % sideSourceIds.length;

        for (let i = 0; i < sideSourceIds.length; i++) {
            const idx = (freedState.lastSideSourceIndex + i) % sideSourceIds.length;
            const sourceId = sideSourceIds[idx];

            const sourceState = this.transporters.get(sourceId);
            if (sourceState && sourceState.status === 'waiting_transfer') {
                this.doSingleTransfer(sourceState, freedState);
                this.pullFromSources(sourceState);
                return;
            }
        }
    }

    /**
     * Transfer resource from one conveyor to another
     */
    doSingleTransfer(fromState, toState) {
        const fromDirection = this.calculateFromDirection(toState.entityId, fromState.entityId);
        // Let setResource determine starting position and phase
        toState.setResource(fromState.resourceId, fromState.resourceAmount, fromDirection);
        fromState.clear();
    }

    /**
     * Animation: Move manipulator arms (runs every tick)
     * Only updates arm positions, state transitions happen in logic tick
     */
    updateManipulatorAnimation() {
        for (const [entityId, state] of this.manipulators) {
            if (state.status === 'idle') continue;

            const speed = state.getTickSpeed();  // ticks per frame

            if (state.status === 'picking') {
                // Moving to source: 0 → 30
                state.ticks = Math.min(state.maxTicks, state.ticks + speed);

                if (state.ticks >= state.maxTicks) {
                    state.status = 'waiting_pick';  // Ready to pick
                }
            } else if (state.status === 'carrying') {
                // Moving to target: 30 → 0
                state.ticks = Math.max(0, state.ticks - speed);

                if (state.ticks <= 0) {
                    state.status = 'waiting_place';  // Ready to place
                }

                // Save holder position to resource's position_px
                state.saveHolderPosition();
            } else if (state.status === 'returning') {
                // Returning to source after placing: 0 → 30
                state.ticks = Math.min(state.maxTicks, state.ticks + speed);

                if (state.ticks >= state.maxTicks) {
                    state.status = 'idle';  // Back at source, ready to pick again
                }
            }
        }
    }

    /**
     * Logic: Process manipulator state transitions (runs every logic tick)
     * Handles actual resource pickup/place operations
     */
    processManipulatorActions() {
        for (const [entityId, state] of this.manipulators) {
            switch (state.status) {
                case 'idle':
                    this.tryPickupResource(state);
                    break;

                case 'waiting_place':
                    // Reached target - try to place resource
                    this.tryPlaceResource(state);
                    break;
            }
        }
    }

    /**
     * Try to start picking up a resource
     */
    tryPickupResource(state) {
        if (!state.sourceEntityId) return;
        // Note: targetEntityId can be null - manipulator can pick up even without target

        const canGive = this.canEntityGive(state.sourceEntityId, 'manipulator');

        if (canGive) {
            // IMPORTANT: Take resource IMMEDIATELY to prevent conveyor from transferring it away
            const pickedResource = this.takeResourceFrom(state.sourceEntityId, 'manipulator');

            if (pickedResource) {
                state.pickResource(pickedResource.resourceId, pickedResource.amount);
                state.resource = { resourceId: pickedResource.resourceId, amount: pickedResource.amount };
                state.status = 'carrying';
                state.ticks = state.maxTicks;  // Start at source position (max ticks)
                this.pendingSync = true;
            }
        }
    }

    /**
     * Try to place a resource
     */
    tryPlaceResource(state) {
        if (!state.targetEntityId) return;

        const canAccept = this.canEntityAccept(state.targetEntityId, state.resourceId, state.resourceAmount);

        if (canAccept === 'yes') {
            this.placeResourceTo(state.targetEntityId, state.resourceId, state.resourceAmount);

            // Clear resource but start returning animation
            state.resourceId = null;
            state.resourceAmount = 0;
            state.resource = null;
            state.status = 'returning';
            // ticks stays at 0 (at target), will animate to maxTicks (source)

            this.pendingSync = true;
        }
        // If 'no' or 'yes_if_freed', keep waiting
    }

    /**
     * Try to start crafting for all buildings (called on game load)
     */
    tryStartAllCrafts() {
        for (const [entityId, state] of this.buildings) {
            this.tryStartCraftForEntity(entityId);
        }
    }

    /**
     * Try to start crafting for a specific entity
     * Called when: game loads, resource received, crafting completes
     */
    tryStartCraftForEntity(entityId) {
        const state = this.buildings.get(entityId);
        if (!state) return;
        if (state.isCrafting()) return;

        if (state.type === 'mining') {
            this.tryStartMiningCraft(state);
        } else if (state.type === 'building' || state.type === 'electricity') {
            this.tryStartBuildingCraft(state);
        }
    }

    /**
     * Try to start mining craft (deposit → raw OR fluid extraction without input)
     */
    tryStartMiningCraft(state) {
        for (const recipeId of state.recipeIds) {
            const recipe = this.game.recipes[recipeId];
            if (!recipe) continue;

            // Check deposit resource (if recipe requires input)
            // Fluid pumps (water, lava) have no input - they extract from landing
            if (recipe.input1_resource_id) {
                const inputAmount = state.getResourceAmount(parseInt(recipe.input1_resource_id));
                if (inputAmount < parseInt(recipe.input1_amount)) continue;
            }

            // Check output limit
            const outputAmount = state.getResourceAmount(parseInt(recipe.output_resource_id));
            const maxOutput = parseInt(state.storagePerResource) || 10;
            if (outputAmount >= maxOutput) continue;

            // Consume input if present
            if (recipe.input1_resource_id) {
                state.removeResource(parseInt(recipe.input1_resource_id), parseInt(recipe.input1_amount));
            }

            // Start crafting
            state.craftingRecipeId = recipeId;
            state.craftingTicksRemaining = state.calculateCraftTime(parseInt(recipe.ticks));
            this.pendingSync = true;

            const outputResource = this.game.resources[recipe.output_resource_id];
            console.log(`[Craft Start] Mining ${state.entityId}: ${recipe.name || 'recipe ' + recipeId} → ${outputResource?.name || recipe.output_resource_id} (${state.craftingTicksRemaining} ticks)`);
            return;
        }
    }

    /**
     * Check if resource is a fluid (type='liquid')
     * @param {number} resourceId
     * @returns {boolean}
     */
    isFluidResource(resourceId) {
        const resource = this.game.resources[resourceId];
        return resource && resource.type === 'liquid';
    }

    /**
     * Try to start building craft
     */
    tryStartBuildingCraft(state) {
        for (const recipeId of state.recipeIds) {
            const recipe = this.game.recipes[recipeId];
            if (!recipe) continue;

            // Check all inputs
            // Input1 is optional (e.g., solar panels have no input)
            if (recipe.input1_resource_id) {
                const input1ResourceId = parseInt(recipe.input1_resource_id);
                const input1AmountNeeded = parseInt(recipe.input1_amount);

                // Skip check if input amount is 0 (not consumed, like sunlight)
                if (input1AmountNeeded > 0) {
                    // Check if this is a fluid resource
                    if (this.isFluidResource(input1ResourceId)) {
                        // Check fluid availability via PipeSystemManager
                        const pipeSystem = this.game.pipeManager?.getSystemForEntity(state.entityId);
                        if (!pipeSystem || pipeSystem.resource_id !== input1ResourceId ||
                            pipeSystem.current_amount < input1AmountNeeded) {
                            console.log(`[Craft Check] Entity ${state.entityId} lacks fluid ${input1ResourceId}: needs ${input1AmountNeeded}, has ${pipeSystem?.current_amount || 0}`);
                            continue;
                        }
                    } else {
                        // Normal solid resource check
                        const input1Amount = state.getResourceAmount(input1ResourceId);
                        if (input1Amount < input1AmountNeeded) continue;
                    }
                }
            }

            if (recipe.input2_resource_id) {
                const input2ResourceId = parseInt(recipe.input2_resource_id);
                const input2AmountNeeded = parseInt(recipe.input2_amount || 1);

                // Skip check if input amount is 0
                if (input2AmountNeeded > 0) {
                    // Check if this is a fluid resource
                    if (this.isFluidResource(input2ResourceId)) {
                        // Check fluid availability via PipeSystemManager
                        const pipeSystem = this.game.pipeManager?.getSystemForEntity(state.entityId);
                        if (!pipeSystem || pipeSystem.resource_id !== input2ResourceId ||
                            pipeSystem.current_amount < input2AmountNeeded) {
                            console.log(`[Craft Check] Entity ${state.entityId} lacks fluid ${input2ResourceId}: needs ${input2AmountNeeded}, has ${pipeSystem?.current_amount || 0}`);
                            continue;
                        }
                    } else {
                        // Normal solid resource check
                        const input2Amount = state.getResourceAmount(input2ResourceId);
                        if (input2Amount < input2AmountNeeded) continue;
                    }
                }
            }

            if (recipe.input3_resource_id) {
                const input3ResourceId = parseInt(recipe.input3_resource_id);
                const input3Amount = parseInt(recipe.input3_amount || 1);

                // Special handling for electricity (resource_id 400)
                if (input3ResourceId === 400) {
                    // Check electricity system instead of building resources
                    if (!this.game.electricityManager.hasElectricity(state.entityId, input3Amount)) {
                        console.log(`[Craft Check] Entity ${state.entityId} lacks electricity: needs ${input3Amount}`);
                        continue;
                    }
                } else if (input3Amount > 0) {
                    // Check if this is a fluid resource
                    if (this.isFluidResource(input3ResourceId)) {
                        // Check fluid availability via PipeSystemManager
                        const pipeSystem = this.game.pipeManager?.getSystemForEntity(state.entityId);
                        if (!pipeSystem || pipeSystem.resource_id !== input3ResourceId ||
                            pipeSystem.current_amount < input3Amount) {
                            console.log(`[Craft Check] Entity ${state.entityId} lacks fluid ${input3ResourceId}: needs ${input3Amount}, has ${pipeSystem?.current_amount || 0}`);
                            continue;
                        }
                    } else {
                        // Normal solid resource check
                        const resourceAmount = state.getResourceAmount(input3ResourceId);
                        if (resourceAmount < input3Amount) continue;
                    }
                }
            }

            // Check output limit
            const outputAmount = state.getResourceAmount(parseInt(recipe.output_resource_id));
            const maxOutput = parseInt(state.storagePerResource) || 10;
            if (outputAmount >= maxOutput) continue;

            // Consume inputs (only if resource exists and amount > 0)
            if (recipe.input1_resource_id) {
                const input1ResourceId = parseInt(recipe.input1_resource_id);
                const input1AmountNeeded = parseInt(recipe.input1_amount);
                if (input1AmountNeeded > 0) {
                    // If fluid - consume from pipe system
                    if (this.isFluidResource(input1ResourceId)) {
                        const pipeSystem = this.game.pipeManager?.getSystemForEntity(state.entityId);
                        if (pipeSystem) {
                            this.game.pipeManager.consumeFluid(pipeSystem.pipe_system_id, input1AmountNeeded);
                            console.log(`[Fluid Consume] Entity ${state.entityId} consumed ${input1AmountNeeded} of fluid ${input1ResourceId} from pipe system`);
                        }
                    } else {
                        state.removeResource(input1ResourceId, input1AmountNeeded);
                    }
                }
            }
            if (recipe.input2_resource_id) {
                const input2ResourceId = parseInt(recipe.input2_resource_id);
                const input2AmountNeeded = parseInt(recipe.input2_amount || 1);
                if (input2AmountNeeded > 0) {
                    // If fluid - consume from pipe system
                    if (this.isFluidResource(input2ResourceId)) {
                        const pipeSystem = this.game.pipeManager?.getSystemForEntity(state.entityId);
                        if (pipeSystem) {
                            this.game.pipeManager.consumeFluid(pipeSystem.pipe_system_id, input2AmountNeeded);
                            console.log(`[Fluid Consume] Entity ${state.entityId} consumed ${input2AmountNeeded} of fluid ${input2ResourceId} from pipe system`);
                        }
                    } else {
                        state.removeResource(input2ResourceId, input2AmountNeeded);
                    }
                }
            }
            if (recipe.input3_resource_id) {
                const input3ResourceId = parseInt(recipe.input3_resource_id);
                const input3AmountNeeded = parseInt(recipe.input3_amount || 1);
                // Don't consume electricity from building - it's consumed from system
                if (input3ResourceId !== 400 && input3AmountNeeded > 0) {
                    // If fluid - consume from pipe system
                    if (this.isFluidResource(input3ResourceId)) {
                        const pipeSystem = this.game.pipeManager?.getSystemForEntity(state.entityId);
                        if (pipeSystem) {
                            this.game.pipeManager.consumeFluid(pipeSystem.pipe_system_id, input3AmountNeeded);
                            console.log(`[Fluid Consume] Entity ${state.entityId} consumed ${input3AmountNeeded} of fluid ${input3ResourceId} from pipe system`);
                        }
                    } else {
                        state.removeResource(input3ResourceId, input3AmountNeeded);
                    }
                }
            }

            // Start crafting
            state.craftingRecipeId = recipeId;
            state.craftingTicksRemaining = state.calculateCraftTime(parseInt(recipe.ticks));
            this.pendingSync = true;

            const outputResource = this.game.resources[recipe.output_resource_id];
            console.log(`[Craft Start] Building ${state.entityId}: ${recipe.name || 'recipe ' + recipeId} → ${outputResource?.name || recipe.output_resource_id} (${state.craftingTicksRemaining} ticks)`);
            return;
        }
    }

    /**
     * Check if entity can accept resource
     * @returns {'yes' | 'no' | 'yes_if_freed'}
     */
    canEntityAccept(entityId, resourceId, amount) {
        // Check transporter
        const transporter = this.transporters.get(entityId);
        if (transporter) {
            if (transporter.isEmpty()) return 'yes';
            if (transporter.isReadyToTransfer()) return 'yes_if_freed';
            return 'no';
        }

        // Check splitter
        const splitter = this.splitters.get(entityId);
        if (splitter) {
            return splitter.isIdle() ? 'yes' : 'no';
        }

        // Check manipulator
        const manipulator = this.manipulators.get(entityId);
        if (manipulator) {
            return manipulator.isIdle() ? 'yes' : 'no';
        }

        // Check building
        const building = this.buildings.get(entityId);
        if (building) {
            return building.canAcceptResource(resourceId, this.game);
        }

        return 'no';
    }

    /**
     * Check if entity can give resource
     */
    canEntityGive(entityId, requesterType) {
        // Check transporter
        const transporter = this.transporters.get(entityId);
        if (transporter) {
            if (!transporter.resourceId) return null;

            // Manipulator can take when resource is in middle of conveyor
            // Manipulator logic tick runs at frame offset +15, so resource should be in center
            if (requesterType === 'manipulator') {
                // Allow taking when resource is in center ±8 ticks window (7-23 out of 30)
                // Wider window to account for timing variations
                const inCenter = transporter.ticks >= 7 && transporter.ticks <= 23;
                if (inCenter) {
                    return { resourceId: transporter.resourceId, amount: transporter.resourceAmount };
                }
                return null;
            }

            // Transporter can only take when at end (30 ticks)
            if (transporter.ticks >= transporter.TICKS_PER_TILE) {
                return { resourceId: transporter.resourceId, amount: transporter.resourceAmount };
            }
            return null;
        }

        // Check manipulator
        const manipulator = this.manipulators.get(entityId);
        if (manipulator) {
            if (manipulator.position_px >= manipulator.centerPositionPx && manipulator.resourceId) {
                return { resourceId: manipulator.resourceId, amount: manipulator.resourceAmount };
            }
            return null;
        }

        // Check building
        const building = this.buildings.get(entityId);
        if (building) {
            return building.canGiveResource(requesterType, this.game);
        }

        return null;
    }

    /**
     * Take resource from entity
     */
    takeResourceFrom(entityId, requesterType) {
        // Transporter
        const transporter = this.transporters.get(entityId);
        if (transporter && transporter.resourceId) {
            const result = { resourceId: transporter.resourceId, amount: transporter.resourceAmount };
            transporter.clear();
            return result;
        }

        // Manipulator
        const manipulator = this.manipulators.get(entityId);
        if (manipulator && manipulator.resourceId) {
            const result = { resourceId: manipulator.resourceId, amount: manipulator.resourceAmount };
            manipulator.clear();
            return result;
        }

        // Building
        const building = this.buildings.get(entityId);
        if (building) {
            const canGive = building.canGiveResource(requesterType, this.game);
            if (canGive) {
                building.removeResource(canGive.resourceId, canGive.amount);
                return canGive;
            }
        }

        return null;
    }

    /**
     * Place resource to entity
     */
    placeResourceTo(entityId, resourceId, amount) {
        // Transporter
        const transporter = this.transporters.get(entityId);
        if (transporter) {
            const fromDirection = 'down'; // Default direction for manipulator placement
            const centerTicks = transporter.TICKS_PER_TILE / 2; // 15 ticks = center
            transporter.setResource(resourceId, amount, fromDirection, centerTicks);
            return true;
        }

        // Building
        const building = this.buildings.get(entityId);
        if (building) {
            // Check if this is HQ (special building)
            const entity = this.game.entityData.get(`entity_${entityId}`);
            if (entity) {
                const entityType = this.game.entityTypes[entity.entity_type_id];
                if (entityType && entityType.type === 'special') {
                    // This is HQ - add to user resources instead of entity resources
                    this.addToUserResources(resourceId, amount);
                    return true;
                }
            }

            // Regular building - add to entity resources
            building.addResource(resourceId, amount);
            // Try to start crafting when resource received
            this.tryStartCraftForEntity(entityId);
            return true;
        }

        return false;
    }

    /**
     * Add resources to user's global inventory (for HQ)
     */
    async addToUserResources(resourceId, amount) {
        const url = this.game.config.addUserResourceUrl;
        if (!url) {
            console.warn('[HQ] addUserResourceUrl not configured');
            return;
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCSRFToken()
                },
                body: JSON.stringify({
                    resource_id: resourceId,
                    amount: amount
                })
            });

            const data = await response.json();

            if (data.result === 'ok') {
                // Update local user resources
                this.game.userResources = data.userResources || {};

                // Update resource panel
                if (this.game.resourcePanel) {
                    this.game.resourcePanel.updateAll();
                }

                // Update build panel affordability
                if (this.game.buildPanel) {
                    this.game.buildPanel.updateAffordability();
                }

                const resourceInfo = this.game.resources[resourceId];
                console.log(`[HQ] Added to user resources: ${amount}x ${resourceInfo?.name || resourceId}`);
            } else {
                console.error('[HQ] Failed to add user resource:', data.error);
            }
        } catch (e) {
            console.error('[HQ] Error adding user resource:', e);
        }
    }

    /**
     * Check and perform auto-save
     */
    checkAutoSave() {
        const now = performance.now();
        if (now - this.lastSaveTime >= this.autoSaveInterval) {
            this.syncToServer();
            this.lastSaveTime = now;
        }
    }

    /**
     * Get data for saving to server
     * NEW (2026-01): Return dictionaries instead of arrays for entity states
     */
    getSaveData() {
        const data = {
            entityResources: {},      // Changed: dict instead of array
            craftingStates: {},       // Changed: dict instead of array
            transporterStates: {},    // Changed: dict instead of array
            splitterStates: {},       // Changed: dict instead of array
            manipulatorStates: {},    // Changed: dict instead of array
            electricitySystems: [],   // Unchanged: remains array
            entityDurability: {},     // NEW: entity durability for shake damage
            currentTick: this.game.gameTick  // For shake damage processing
        };

        // Building resources and crafting
        for (const [entityId, state] of this.buildings) {
            // Resources
            const resources = state.getResourceSaveData();
            if (resources.length > 0) {
                data.entityResources[entityId] = resources;  // Dict
            }

            // Crafting
            const craftingData = state.getCraftingSaveData();
            if (craftingData) {
                data.craftingStates[entityId] = craftingData;  // Dict
            }
        }

        // Transporter states
        for (const [entityId, state] of this.transporters) {
            const saveData = state.getSaveData();
            if (saveData) {
                data.transporterStates[entityId] = saveData;  // Dict
            }
        }

        // Splitter states
        for (const [entityId, state] of this.splitters) {
            const saveData = state.getSaveData();
            if (saveData) {
                data.splitterStates[entityId] = saveData;  // Dict
            }
        }

        // Manipulator states
        for (const [entityId, state] of this.manipulators) {
            const saveData = state.getSaveData();
            if (saveData) {
                data.manipulatorStates[entityId] = saveData;  // Dict
            }
        }

        // Electricity systems (unchanged)
        if (this.game.electricityManager && this.game.electricityManager.systems) {
            for (const [systemId, system] of this.game.electricityManager.systems) {
                data.electricitySystems.push({
                    system_id: systemId,
                    total_electricity: system.total_electricity
                });
            }
        }

        // Entity durability (for shake damage)
        for (const [key, entity] of this.game.entityData) {
            if (typeof entity.durability === 'number') {
                data.entityDurability[entity.entity_id] = entity.durability;
            }
        }

        return data;
    }

    /**
     * Sync state to server
     */
    async syncToServer() {
        if (!this.pendingSync) return;

        const saveData = this.getSaveData();

        try {
            const response = await fetch(this.game.config.saveStateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCSRFToken()
                },
                body: JSON.stringify(saveData)
            });

            if (response.ok) {
                this.pendingSync = false;
                console.log('Transport state saved');
            }
        } catch (e) {
            console.error('Failed to save transport state:', e);
        }
    }

    /**
     * Load state from server data
     * NEW (2026-01): entityResources can be map or array (backward compatibility)
     */
    loadState(data) {
        // Load building resources
        if (data.entityResources) {
            // Check if it's a map/object or array (backward compatibility)
            const isMap = !Array.isArray(data.entityResources);

            for (const [entityId, state] of this.buildings) {
                let resources;
                if (isMap) {
                    // NEW: entityResources is a map {entity_id => [...]}
                    resources = data.entityResources[entityId] || [];
                } else {
                    // OLD: entityResources is array [{entity_id: X, ...}]
                    resources = data.entityResources.filter(er => er.entity_id === entityId);
                }
                state.loadResources(resources);  // Pass pre-filtered array
            }
        }

        // Load crafting states
        if (data.craftingStates) {
            for (const cs of data.craftingStates) {
                const state = this.buildings.get(cs.entity_id);
                if (state) {
                    state.loadCraftingState(cs);
                }
            }
        }

        // Load transporter states
        if (data.transporterStates) {
            for (const ts of data.transporterStates) {
                const state = this.transporters.get(ts.entity_id);
                if (state) {
                    state.loadFromSaved(ts);
                }
            }
        }

        // Load splitter states
        if (data.splitterStates) {
            for (const ss of data.splitterStates) {
                const state = this.splitters.get(ss.entity_id);
                if (state) {
                    state.loadFromSaved(ss);
                }
            }
        }

        // Load manipulator states
        if (data.manipulatorStates) {
            for (const ms of data.manipulatorStates) {
                const state = this.manipulators.get(ms.entity_id);
                if (state) {
                    state.loadFromSaved(ms);
                }
            }
        }
    }

    /**
     * Handle entity added to map
     */
    onEntityAdded(entity) {
        const entityType = this.game.entityTypes[entity.entity_type_id];
        if (!entityType) return;

        // Add to spatial index with entity dimensions
        const width = parseInt(entityType.width) || 1;
        const height = parseInt(entityType.height) || 1;
        this.spatialIndex.add(entity, width, height);

        switch (entityType.type) {
            case 'conveyor':  // Conveyors (including underground belts)
            case 'transporter':
                // Check if this is a splitter
                if (this.SPLITTER_TYPE_IDS.has(entity.entity_type_id)) {
                    this.splitters.set(entity.entity_id, new SplitterState(entity, entityType, this.game));
                } else {
                    this.transporters.set(entity.entity_id, new TransporterState(entity, entityType, this.game));
                }
                break;
            case 'manipulator':
                this.manipulators.set(entity.entity_id, new ManipulatorState(entity, entityType, this.game));
                break;
            case 'building':
            case 'mining':
            case 'storage':
            case 'special':
                this.buildings.set(entity.entity_id, new BuildingState(entity, entityType, this.game));
                break;
        }

        this.calculateLinks();
        this.pendingSync = true;
    }

    /**
     * Find pipe at output position (bottom of building)
     * @param {number} buildingX - Building tile X
     * @param {number} buildingY - Building tile Y
     * @returns {number|null} - Pipe entity_id or null
     */
    findOutputPipe(buildingX, buildingY) {
        // Safety checks
        if (!this.spatialIndex) {
            console.error('[ResourceTransport] spatialIndex is not available');
            return null;
        }

        if (!this.game || !this.game.entityData) {
            console.error('[ResourceTransport] game.entityData is not available');
            return null;
        }

        // Output is at bottom of building (+1 tile Y)
        const outputX = buildingX;
        const outputY = buildingY + 1;

        // Find entity ID at output position
        const entityId = this.spatialIndex.getAt(outputX, outputY);
        if (!entityId) return null;

        // Get entity data
        const outputEntity = this.game.entityData.get(`entity_${entityId}`);
        if (!outputEntity) return null;

        // Check if it's a pipe (entity_type_id 131-141)
        const isPipe = [131, 132, 135, 136, 140, 141].includes(parseInt(outputEntity.entity_type_id));

        return isPipe ? entityId : null;
    }

    /**
     * Handle entity removed from map
     */
    onEntityRemoved(entityId) {
        // Get entity data
        const entity = this.game.entityData.get(`entity_${entityId}`);

        if (entity) {
            const entityType = this.game.entityTypes[entity.entity_type_id];
            if (entityType) {
                const width = parseInt(entityType.width) || 1;
                const height = parseInt(entityType.height) || 1;
                this.spatialIndex.remove(entity, width, height);
            }
        }

        // Remove from state maps
        this.transporters.delete(entityId);
        this.splitters.delete(entityId);
        this.manipulators.delete(entityId);
        this.buildings.delete(entityId);

        this.calculateLinks();
        this.pendingSync = true;
    }

    /**
     * Get crafting progress for an entity
     */
    getCraftingProgress(entityId) {
        const building = this.buildings.get(entityId);
        if (building) {
            return building.getCraftingProgress(this.game);
        }
        return null;
    }

    /**
     * Get transporter state for rendering
     */
    getTransporterState(entityId) {
        return this.transporters.get(entityId);
    }

    /**
     * Get manipulator state for rendering
     */
    getManipulatorState(entityId) {
        return this.manipulators.get(entityId);
    }

    /**
     * Get splitter state
     */
    getSplitterState(entityId) {
        return this.splitters.get(entityId);
    }

    /**
     * Get state for any entity (transporter, splitter, manipulator, or building)
     * Used by SplitterState to find target entities
     */
    getState(entityId) {
        return this.transporters.get(entityId) ||
               this.splitters.get(entityId) ||
               this.manipulators.get(entityId) ||
               this.buildings.get(entityId) ||
               null;
    }

    /**
     * Create visual indicator for logic tick (debug)
     */
    createLogicTickIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'logic-tick-indicator';
        indicator.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100px;
            height: 100px;
            background: rgba(128, 128, 128, 0.3);
            border: 4px solid #888888;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: white;
            font-weight: bold;
            z-index: 10000;
            transition: all 0.15s;
            pointer-events: none;
        `;
        indicator.textContent = 'LOGIC';
        document.body.appendChild(indicator);
        this.logicTickIndicator = indicator;
    }

    /**
     * Show/hide logic tick indicator (debug feature)
     * @param {boolean} visible - true to show, false to hide
     */
    setLogicTickIndicatorVisible(visible) {
        if (!this.logicTickIndicator) return;
        this.logicTickIndicator.style.display = visible ? 'flex' : 'none';
    }

    /**
     * Flash logic tick indicator
     * @param {string} color - 'red' for conveyors, 'green' for manipulators
     */
    flashLogicTickIndicator(color = 'red') {
        if (!this.logicTickIndicator) return;

        const colors = {
            red: { bg: 'rgba(255, 0, 0, 0.9)', border: '#ff0000', text: 'CONV' },
            green: { bg: 'rgba(0, 255, 0, 0.9)', border: '#00ff00', text: 'MANIP' }
        };

        const style = colors[color];

        // Flash effect
        this.logicTickIndicator.style.background = style.bg;
        this.logicTickIndicator.style.borderColor = style.border;
        this.logicTickIndicator.style.transform = 'scale(1.2)';
        this.logicTickIndicator.textContent = style.text;

        // Reset after 150ms
        setTimeout(() => {
            if (this.logicTickIndicator) {
                this.logicTickIndicator.style.background = 'rgba(128, 128, 128, 0.3)';
                this.logicTickIndicator.style.borderColor = '#888888';
                this.logicTickIndicator.style.transform = 'scale(1)';
                this.logicTickIndicator.textContent = 'LOGIC';
            }
        }, 150);
    }
}

export default ResourceTransportManager;
