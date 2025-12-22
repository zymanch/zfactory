import { SpatialIndex } from './SpatialIndex.js';
import { TransporterState } from './TransporterState.js';
import { ManipulatorState } from './ManipulatorState.js';
import { BuildingState } from './BuildingState.js';
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

        // Split transport states into transporter and manipulator
        const transporterStates = [];
        const manipulatorStates = [];

        for (const ts of (game.initialTransportStates || [])) {
            const entityId = ts.entity_id;
            if (this.transporters.has(entityId)) {
                transporterStates.push(ts);
            } else if (this.manipulators.has(entityId)) {
                manipulatorStates.push(ts);
            }
        }

        // Load using existing loadState method
        this.loadState({
            entityResources: game.initialEntityResources || [],
            craftingStates: game.initialCraftingStates || [],
            transporterStates: transporterStates,
            manipulatorStates: manipulatorStates
        });
    }

    /**
     * Build state objects from game entities
     */
    buildStateFromEntities() {
        this.transporters.clear();
        this.manipulators.clear();
        this.buildings.clear();
        this.spatialIndex.clear();

        for (const [key, entity] of this.game.entityData) {
            const entityType = this.game.entityTypes[entity.entity_type_id];
            if (!entityType) continue;

            this.spatialIndex.add(entity);

            switch (entityType.type) {
                case 'transporter':
                    this.transporters.set(entity.entity_id, new TransporterState(entity, entityType));
                    break;

                case 'manipulator':
                    this.manipulators.set(entity.entity_id, new ManipulatorState(entity, entityType));
                    break;

                case 'building':
                case 'mining':
                case 'storage':
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

        for (const state of this.manipulators.values()) {
            state.sourceEntityId = null;
            state.targetEntityId = null;
        }

        // Calculate transporter targets
        for (const [entityId, state] of this.transporters) {
            const targetPos = this.getNextPosition(state.x, state.y, state.orientation);
            const targetEntityId = this.spatialIndex.getAt(targetPos.x, targetPos.y);

            if (targetEntityId) {
                state.targetEntityId = targetEntityId;
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
                    const outputResource = this.game.resources[recipe.output_resource_id];
                    console.log(`[Craft Complete] Entity ${entityId}: +${recipe.output_amount || 1} ${outputResource?.name || recipe.output_resource_id}`);
                    state.addResource(
                        parseInt(recipe.output_resource_id),
                        parseInt(recipe.output_amount) || 1
                    );
                }

                state.craftingRecipeId = null;
                state.craftingTicksRemaining = 0;
                this.pendingSync = true;

                // Try to start a new craft immediately
                this.tryStartCraftForEntity(entityId);
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

            const speed = state.getSpeed();

            // Move towards center if entered from side
            if (state.lateralOffset !== 0) {
                if (state.lateralOffset > 0) {
                    state.lateralOffset = Math.max(0, state.lateralOffset - speed);
                } else {
                    state.lateralOffset = Math.min(0, state.lateralOffset + speed);
                }
            }

            // Move along the belt (stops at 1.0)
            if (state.lateralOffset === 0 && state.resourcePosition < 1.0) {
                state.resourcePosition = Math.min(1.0, state.resourcePosition + speed);
            }
        }
    }

    /**
     * Logic: Check conveyor status transitions (runs every logic tick)
     */
    updateTransporterStatus() {
        for (const [entityId, state] of this.transporters) {
            if (state.isEmpty()) continue;

            // Check if reached end and should wait for transfer
            if (state.resourcePosition >= 1.0 && state.status === 'carrying') {
                state.status = 'waiting_transfer';
            }
        }
    }

    /**
     * Process transfers between conveyors (simultaneous for cycles)
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
                resourceAmount: state.resourceAmount,
                fromOrientation: state.orientation
            });
        }

        if (transfers.length === 0) return;

        // Phase 3: Clear all sources
        for (const t of transfers) {
            const state = this.transporters.get(t.fromId);
            state.clear();
        }

        // Phase 4: Fill all targets
        const buildingsReceived = [];
        for (const t of transfers) {
            const targetState = this.transporters.get(t.toId);
            const resource = this.game.resources[t.resourceId];
            const resourceName = resource?.name || t.resourceId;

            if (targetState) {
                // Target is a transporter
                const lateralOffset = this.calculateLateralOffset(t.fromOrientation, targetState.orientation);
                // If entering from side (perpendicular), start at center position (0.5)
                // If entering from behind (same orientation), start at entry edge (0)
                const startPosition = lateralOffset !== 0 ? 0.5 : 0;
                targetState.setResource(t.resourceId, t.resourceAmount, startPosition, lateralOffset);
                console.log(`[Transfer] Conveyor ${t.fromId} → Conveyor ${t.toId}: ${t.resourceAmount}x ${resourceName}`);
            } else {
                // Target is a building
                const buildingState = this.buildings.get(t.toId);
                if (buildingState) {
                    buildingState.addResource(t.resourceId, t.resourceAmount);
                    buildingsReceived.push(t.toId);
                    console.log(`[Transfer] Conveyor ${t.fromId} → Building ${t.toId}: ${t.resourceAmount}x ${resourceName}`);
                }
            }
        }

        // Try to start crafting for buildings that received resources
        for (const entityId of buildingsReceived) {
            this.tryStartCraftForEntity(entityId);
        }

        // Phase 5: Pull from sources of freed conveyors
        for (const t of transfers) {
            const freedState = this.transporters.get(t.fromId);
            if (freedState && freedState.isEmpty()) {
                this.pullFromSources(freedState);
            }
        }

        this.pendingSync = true;
    }

    /**
     * Calculate lateral offset for resources entering from side
     *
     * Lateral offset is perpendicular to the conveyor's direction:
     * - RIGHT/LEFT conveyors: lateral axis is Y (positive = below center, negative = above)
     * - UP/DOWN conveyors: lateral axis is X (positive = right of center, negative = left)
     *
     * Source conveyor position relative to target:
     * - UP source → below target (transfers upward)
     * - DOWN source → above target (transfers downward)
     * - LEFT source → right of target (transfers leftward)
     * - RIGHT source → left of target (transfers rightward)
     */
    calculateLateralOffset(fromOrientation, toOrientation) {
        if (fromOrientation === toOrientation) return 0;

        // Map: [toOrientation][fromOrientation] = lateralOffset
        // For RIGHT/LEFT targets: up source = below = +0.5, down source = above = -0.5
        // For UP/DOWN targets: left source = right = +0.5, right source = left = -0.5
        const lateralMap = {
            'right': { 'up': 0.5, 'down': -0.5 },
            'left':  { 'up': 0.5, 'down': -0.5 },
            'up':    { 'left': 0.5, 'right': -0.5 },
            'down':  { 'left': 0.5, 'right': -0.5 }
        };

        return lateralMap[toOrientation]?.[fromOrientation] || 0;
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
        const lateralOffset = this.calculateLateralOffset(fromState.orientation, toState.orientation);
        // If entering from side (perpendicular), start at center position (0.5)
        const startPosition = lateralOffset !== 0 ? 0.5 : 0;
        toState.setResource(fromState.resourceId, fromState.resourceAmount, startPosition, lateralOffset);
        fromState.clear();
    }

    /**
     * Animation: Move manipulator arms (runs every tick)
     * Only updates arm positions, state transitions happen in logic tick
     */
    updateManipulatorAnimation() {
        for (const [entityId, state] of this.manipulators) {
            const speed = state.getSpeed();

            switch (state.status) {
                case 'picking':
                    // Move arm towards source
                    state.armPosition = Math.max(0, state.armPosition - speed);
                    break;

                case 'carrying':
                    // Move arm towards target
                    state.armPosition = Math.min(1.0, state.armPosition + speed);
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
                    // Check if arm reached source position
                    if (state.armPosition <= 0) {
                        const pickedResource = this.takeResourceFrom(state.sourceEntityId, 'manipulator');
                        if (pickedResource) {
                            const resourceInfo = this.game.resources[pickedResource.resourceId];
                            console.log(`[Pickup] Manipulator ${state.entityId} ← Entity ${state.sourceEntityId}: ${pickedResource.amount}x ${resourceInfo?.name || pickedResource.resourceId}`);
                            state.pickResource(pickedResource.resourceId, pickedResource.amount);
                            this.pendingSync = true;
                        } else {
                            state.status = 'idle';
                            state.armPosition = 0.5;
                        }
                    }
                    break;

                case 'carrying':
                    // Check if arm reached target position
                    if (state.armPosition >= 1.0) {
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
        if (!state.targetEntityId) return;

        const canGive = this.canEntityGive(state.sourceEntityId, 'manipulator');
        if (canGive) {
            state.status = 'picking';
            state.armPosition = 0.5;
        }
    }

    /**
     * Try to place a resource
     */
    tryPlaceResource(state) {
        if (!state.targetEntityId) return;

        const canAccept = this.canEntityAccept(state.targetEntityId, state.resourceId, state.resourceAmount);

        if (canAccept === 'yes') {
            const resource = this.game.resources[state.resourceId];
            const resourceName = resource?.name || state.resourceId;
            console.log(`[Transfer] Manipulator ${state.entityId} → Entity ${state.targetEntityId}: ${state.resourceAmount}x ${resourceName}`);

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
        } else if (state.type === 'building') {
            this.tryStartBuildingCraft(state);
        }
    }

    /**
     * Try to start mining craft (deposit → raw)
     */
    tryStartMiningCraft(state) {
        for (const recipeId of state.recipeIds) {
            const recipe = this.game.recipes[recipeId];
            if (!recipe) continue;

            // Check deposit resource
            const inputAmount = state.getResourceAmount(parseInt(recipe.input1_resource_id));
            if (inputAmount < parseInt(recipe.input1_amount)) continue;

            // Check output limit (max 10)
            const outputAmount = state.getResourceAmount(parseInt(recipe.output_resource_id));
            if (outputAmount >= 10) continue;

            // Start crafting
            state.removeResource(parseInt(recipe.input1_resource_id), parseInt(recipe.input1_amount));
            state.craftingRecipeId = recipeId;
            state.craftingTicksRemaining = state.calculateCraftTime(parseInt(recipe.ticks));
            this.pendingSync = true;

            const outputResource = this.game.resources[recipe.output_resource_id];
            console.log(`[Craft Start] Mining ${state.entityId}: ${recipe.name || 'recipe ' + recipeId} → ${outputResource?.name || recipe.output_resource_id} (${state.craftingTicksRemaining} ticks)`);
            return;
        }
    }

    /**
     * Try to start building craft
     */
    tryStartBuildingCraft(state) {
        for (const recipeId of state.recipeIds) {
            const recipe = this.game.recipes[recipeId];
            if (!recipe) continue;

            // Check all inputs
            const input1Amount = state.getResourceAmount(parseInt(recipe.input1_resource_id));
            if (input1Amount < parseInt(recipe.input1_amount)) continue;

            if (recipe.input2_resource_id) {
                const input2Amount = state.getResourceAmount(parseInt(recipe.input2_resource_id));
                if (input2Amount < parseInt(recipe.input2_amount || 1)) continue;
            }

            if (recipe.input3_resource_id) {
                const input3Amount = state.getResourceAmount(parseInt(recipe.input3_resource_id));
                if (input3Amount < parseInt(recipe.input3_amount || 1)) continue;
            }

            // Check output limit
            const outputAmount = state.getResourceAmount(parseInt(recipe.output_resource_id));
            if (outputAmount >= 10) continue;

            // Consume inputs
            state.removeResource(parseInt(recipe.input1_resource_id), parseInt(recipe.input1_amount));
            if (recipe.input2_resource_id) {
                state.removeResource(parseInt(recipe.input2_resource_id), parseInt(recipe.input2_amount || 1));
            }
            if (recipe.input3_resource_id) {
                state.removeResource(parseInt(recipe.input3_resource_id), parseInt(recipe.input3_amount || 1));
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
            // Transporter can only take when at end
            if (transporter.resourcePosition >= 1.0) {
                return { resourceId: transporter.resourceId, amount: transporter.resourceAmount };
            }
            return null;
        }

        // Check manipulator
        const manipulator = this.manipulators.get(entityId);
        if (manipulator) {
            if (manipulator.armPosition >= 1.0 && manipulator.resourceId) {
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
            transporter.setResource(resourceId, amount, 0.5, 0); // Start at center
            return true;
        }

        // Building
        const building = this.buildings.get(entityId);
        if (building) {
            building.addResource(resourceId, amount);
            // Try to start crafting when resource received
            this.tryStartCraftForEntity(entityId);
            return true;
        }

        return false;
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
            manipulatorStates: []
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

        // Manipulator states
        for (const [entityId, state] of this.manipulators) {
            const saveData = state.getSaveData();
            if (saveData) {
                data.manipulatorStates.push(saveData);
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

        this.spatialIndex.add(entity);

        switch (entityType.type) {
            case 'transporter':
                this.transporters.set(entity.entity_id, new TransporterState(entity, entityType));
                break;
            case 'manipulator':
                this.manipulators.set(entity.entity_id, new ManipulatorState(entity, entityType));
                break;
            case 'building':
            case 'mining':
            case 'storage':
                this.buildings.set(entity.entity_id, new BuildingState(entity, entityType, this.game));
                break;
        }

        this.calculateLinks();
        this.pendingSync = true;
    }

    /**
     * Handle entity removed from map
     */
    onEntityRemoved(entityId) {
        // Get entity data
        const entity = this.game.entityData.get(`entity_${entityId}`);

        if (entity) {
            this.spatialIndex.remove(entity);
        }

        // Remove from state maps
        this.transporters.delete(entityId);
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
}

export default ResourceTransportManager;
