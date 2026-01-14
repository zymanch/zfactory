import { SpatialIndex } from './SpatialIndex.js';
import { TransporterState } from './TransporterState.js';
import { ManipulatorState } from './ManipulatorState.js';
import { BuildingState } from './BuildingState.js';
import { SplitterState } from './SplitterState.js';
import { getCSRFToken } from '../utils.js';

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

        // Splitter entity type IDs (800-811)
        this.SPLITTER_TYPE_IDS = new Set([
            800, 801, 802, 803,  // Splitter Normal
            804, 805, 806, 807,  // Splitter Dual
            808, 809, 810, 811   // Fast Splitter
        ]);

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
     * Load initial state from game config data
     */
    loadInitialState() {
        const game = this.game;

        // Split transport states into transporter, splitter, and manipulator
        const transporterStates = [];
        const splitterStates = [];
        const manipulatorStates = [];

        for (const ts of (game.initialTransportStates || [])) {
            const entityId = ts.entity_id;
            if (this.transporters.has(entityId)) {
                transporterStates.push(ts);
            } else if (this.splitters.has(entityId)) {
                splitterStates.push(ts);
            } else if (this.manipulators.has(entityId)) {
                manipulatorStates.push(ts);
            }
        }

        // Load using existing loadState method
        this.loadState({
            entityResources: game.initialEntityResources || [],
            craftingStates: game.initialCraftingStates || [],
            transporterStates: transporterStates,
            splitterStates: splitterStates,
            manipulatorStates: manipulatorStates
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
        if (this.logicTickCounter >= this.LOGIC_TICK_INTERVAL) {
            this.logicTickCounter = 0;
            this.logicTick();
        }

        // Auto-save check (time-based, ok to run every frame)
        this.checkAutoSave();
    }

    /**
     * Logic tick - heavy operations that run every LOGIC_TICK_INTERVAL ticks
     */
    logicTick() {
        // Update crafting progress and completion
        this.updateCrafting();

        // Check conveyor status transitions
        this.updateTransporterStatus();

        // Process transfers between conveyors
        this.processTransporterTransfers();

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
     * Only updates positions, status changes happen in logic tick
     */
    updateTransporterAnimation() {
        for (const [entityId, state] of this.transporters) {
            if (state.isEmpty()) continue;

            const speed = state.getSpeed();  // pixels per frame

            // Move from -centerPx (entry) to +centerPx (exit)
            if (state.position_px < state.centerPositionPx) {
                state.position_px = Math.min(state.centerPositionPx, state.position_px + speed);
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

            // Check if reached end (+centerPx) and should wait for transfer
            if (state.position_px >= state.centerPositionPx && state.status === 'carrying') {
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
        // Phase 1: Determine who will transfer
        for (const [entityId, state] of this.transporters) {
            state.willTransfer = false;

            if (state.status !== 'waiting_transfer') continue;
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
                // Start at entry (-centerPx)
                targetState.setResource(t.resourceId, t.resourceAmount, fromDirection, -targetState.centerPositionPx);
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
        // Resources start at entry (-centerPx)
        toState.setResource(fromState.resourceId, fromState.resourceAmount, fromDirection, -toState.centerPositionPx);
        fromState.clear();
    }

    /**
     * Animation: Move manipulator arms (runs every tick)
     * Only updates arm positions, state transitions happen in logic tick
     */
    updateManipulatorAnimation() {
        for (const [entityId, state] of this.manipulators) {
            const speed = state.getArmSpeed();  // pixels per frame

            switch (state.status) {
                case 'picking':
                    // Move arm towards source (-centerPx)
                    state.position_px = Math.max(-state.centerPositionPx, state.position_px - speed);
                    break;

                case 'carrying':
                    // Move arm towards target (+centerPx)
                    state.position_px = Math.min(state.centerPositionPx, state.position_px + speed);
                    break;
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

                case 'picking':
                    // Check if arm reached source position (-centerPx)
                    if (state.position_px <= -state.centerPositionPx) {
                        const pickedResource = this.takeResourceFrom(state.sourceEntityId, 'manipulator');
                        if (pickedResource) {
                            const resourceInfo = this.game.resources[pickedResource.resourceId];
                            console.log(`[Pickup] Manipulator ${state.entityId} ← Entity ${state.sourceEntityId}: ${pickedResource.amount}x ${resourceInfo?.name || pickedResource.resourceId}`);
                            state.pickResource(pickedResource.resourceId, pickedResource.amount);
                            this.pendingSync = true;
                        } else {
                            state.status = 'idle';
                            state.position_px = 0;
                        }
                    }
                    break;

                case 'carrying':
                    // Check if arm reached target position (+centerPx)
                    if (state.position_px >= state.centerPositionPx) {
                        state.status = 'placing';
                    }
                    break;

                case 'placing':
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
            state.status = 'picking';
            state.position_px = 0;  // Start from center
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
            state.clear();
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
            // Manipulator can take from any position
            if (requesterType === 'manipulator') {
                return { resourceId: transporter.resourceId, amount: transporter.resourceAmount };
            }
            // Transporter can only take when at end (+centerPx)
            if (transporter.position_px >= transporter.centerPositionPx) {
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
            transporter.setResource(resourceId, amount, fromDirection, 0); // Start at center (0)
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
     */
    getSaveData() {
        const data = {
            entityResources: [],
            craftingStates: [],
            transporterStates: [],
            splitterStates: [],
            manipulatorStates: [],
            electricitySystems: []
        };

        // Building resources and crafting
        for (const [entityId, state] of this.buildings) {
            data.entityResources.push(...state.getResourceSaveData());

            const craftingData = state.getCraftingSaveData();
            if (craftingData) {
                data.craftingStates.push(craftingData);
            }
        }

        // Transporter states
        for (const [entityId, state] of this.transporters) {
            const saveData = state.getSaveData();
            if (saveData) {
                data.transporterStates.push(saveData);
            }
        }

        // Splitter states
        for (const [entityId, state] of this.splitters) {
            const saveData = state.getSaveData();
            if (saveData) {
                data.splitterStates.push(saveData);
            }
        }

        // Manipulator states
        for (const [entityId, state] of this.manipulators) {
            const saveData = state.getSaveData();
            if (saveData) {
                data.manipulatorStates.push(saveData);
            }
        }

        // Electricity systems
        if (this.game.electricityManager) {
            for (const [systemId, system] of this.game.electricityManager.systems) {
                data.electricitySystems.push({
                    system_id: systemId,
                    total_electricity: system.total_electricity
                });
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
     */
    loadState(data) {
        // Load building resources
        if (data.entityResources) {
            for (const [entityId, state] of this.buildings) {
                state.loadResources(data.entityResources);
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
}

export default ResourceTransportManager;
