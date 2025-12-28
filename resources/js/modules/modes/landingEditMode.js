import { LANDING_SKY_ID, LANDING_ISLAND_EDGE_ID } from '../constants.js';

/**
 * LandingEditMode - click on map to change landing type (admin tool)
 */
export class LandingEditMode {
    constructor(game) {
        this.game = game;
        this.isActive = false;
        this.selectedLandingId = null;
        this.statusElement = null;

        // Depth limit for cascading adjacency resolution
        this.MAX_CASCADE_DEPTH = 3;
    }

    /**
     * Initialize mode
     */
    init() {
        this.createStatusIndicator();

        // Click handler for canvas
        document.addEventListener('click', (e) => this.onClick(e));
    }

    /**
     * Create status indicator element
     */
    createStatusIndicator() {
        this.statusElement = document.createElement('div');
        this.statusElement.id = 'landing-status';
        document.body.appendChild(this.statusElement);
    }

    /**
     * Activate edit mode with selected landing
     * (Now called by GameModeManager, no longer needs to deactivate other modes)
     */
    activate(landingId) {
        this.selectedLandingId = landingId;
        this.isActive = true;

        this.showStatusIndicator();
    }

    /**
     * Deactivate edit mode
     */
    deactivate() {
        this.isActive = false;
        this.selectedLandingId = null;
        this.hideStatusIndicator();
    }

    /**
     * Show status indicator with selected landing name
     */
    showStatusIndicator() {
        const landing = this.game.landingTypes[this.selectedLandingId];
        const isSky = this.selectedLandingId === LANDING_SKY_ID;
        const text = isSky
            ? `Режим редактирования: Удаление (Esc - выход)`
            : `Режим редактирования: ${landing?.name || 'Unknown'} (Esc - выход)`;

        this.statusElement.textContent = text;
        this.statusElement.classList.add('active');
    }

    /**
     * Hide status indicator
     */
    hideStatusIndicator() {
        this.statusElement.classList.remove('active');
    }

    /**
     * Handle click events
     */
    onClick(e) {
        if (!this.isActive) return;
        if (e.target !== this.game.app.canvas) return;

        const tile = this.game.input.screenToTile(e.clientX, e.clientY);
        this.changeLanding(tile.x, tile.y);
    }

    /**
     * Change landing at position with adjacency resolution
     */
    async changeLanding(x, y) {
        // Calculate all changes (main tile + affected neighbors)
        const changes = this.resolveChanges(x, y, this.selectedLandingId);

        if (changes.length === 0) return;

        // Send to server
        try {
            const response = await this.saveChanges(changes);

            if (response.result === 'ok') {
                this.applyChanges(response.updated, response.deleted);
            } else {
                console.error('Failed to update landing:', response.error);
            }
        } catch (e) {
            console.error('Error updating landing:', e);
        }
    }

    /**
     * Resolve changes with cascading adjacency fix
     * Returns array of { x, y, landing_id } (null for delete)
     */
    resolveChanges(x, y, newLandingId) {
        const changes = new Map(); // key "x_y" -> landing_id
        const processed = new Set();
        const queue = [{ x, y, depth: 0 }];

        // Initial change
        const isSky = newLandingId === LANDING_SKY_ID;
        changes.set(`${x}_${y}`, isSky ? null : newLandingId);

        while (queue.length > 0) {
            const { x: cx, y: cy, depth } = queue.shift();
            const key = `${cx}_${cy}`;

            if (processed.has(key)) continue;
            processed.add(key);

            if (depth >= this.MAX_CASCADE_DEPTH) continue;

            const currentLanding = changes.get(key);
            if (currentLanding === null) continue; // Deleted tile, no neighbors to check

            // Check cardinal neighbors
            const neighbors = [
                { x: cx - 1, y: cy },  // left
                { x: cx + 1, y: cy },  // right
                { x: cx, y: cy - 1 },  // top
                { x: cx, y: cy + 1 }   // bottom
            ];

            for (const neighbor of neighbors) {
                const nKey = `${neighbor.x}_${neighbor.y}`;

                // Skip if already in changes or processed
                if (changes.has(nKey) || processed.has(nKey)) continue;

                // Get neighbor's current landing
                const neighborLanding = this.game.tileManager.getLandingAt(neighbor.x, neighbor.y);

                // Skip if no tile (sky area)
                if (neighborLanding === undefined) continue;

                // Skip island_edge (auto-generated)
                if (neighborLanding === LANDING_ISLAND_EDGE_ID) continue;

                // Check compatibility
                if (this.isCompatible(currentLanding, neighborLanding)) continue;

                // Need to find replacement for neighbor
                const replacement = this.findCompatibleLanding(
                    currentLanding,
                    neighbor.x,
                    neighbor.y,
                    nKey
                );

                if (replacement !== null && replacement !== neighborLanding) {
                    changes.set(nKey, replacement);
                    queue.push({ x: neighbor.x, y: neighbor.y, depth: depth + 1 });
                }
            }
        }

        // Convert to array
        const result = [];
        for (const [key, landingId] of changes) {
            const [xStr, yStr] = key.split('_');
            result.push({
                x: parseInt(xStr),
                y: parseInt(yStr),
                landing_id: landingId
            });
        }

        return result;
    }

    /**
     * Check if two landing types are compatible
     */
    isCompatible(landingA, landingB) {
        if (landingA === landingB) return true;
        if (landingA === null || landingB === null) return true; // Deleted tiles are compatible
        if (landingA === LANDING_SKY_ID || landingB === LANDING_SKY_ID) return true;
        return this.game.hasLandingAdjacency(landingA, landingB);
    }

    /**
     * Find a landing that is compatible with the new landing and existing neighbors
     */
    findCompatibleLanding(mustBorderWith, x, y, excludeKey) {
        // Get all existing neighbors of (x, y) except the one that triggered the change
        const neighborLandings = [];
        const neighbors = [
            { x: x - 1, y: y },
            { x: x + 1, y: y },
            { x: x, y: y - 1 },
            { x: x, y: y + 1 }
        ];

        for (const n of neighbors) {
            const nKey = `${n.x}_${n.y}`;
            if (nKey === excludeKey) continue;

            const landing = this.game.tileManager.getLandingAt(n.x, n.y);
            if (landing !== undefined && landing !== LANDING_SKY_ID && landing !== LANDING_ISLAND_EDGE_ID) {
                neighborLandings.push(landing);
            }
        }

        // Try all real landings (1-8, excluding sky and island_edge)
        for (let candidateId = 1; candidateId <= 8; candidateId++) {
            // Must border the new landing
            if (!this.isCompatible(candidateId, mustBorderWith)) continue;

            // Must also border all existing unchanged neighbors
            let allCompatible = true;
            for (const neighborId of neighborLandings) {
                if (!this.isCompatible(candidateId, neighborId)) {
                    allCompatible = false;
                    break;
                }
            }

            if (allCompatible) return candidateId;
        }

        // Fallback: grass (1) is most versatile, then dirt (2)
        if (this.isCompatible(1, mustBorderWith)) return 1;
        if (this.isCompatible(2, mustBorderWith)) return 2;

        // Last resort: return grass anyway
        return 1;
    }

    /**
     * Save changes to server
     */
    async saveChanges(changes) {
        const url = this.game.config.updateLandingUrl;
        if (!url) {
            console.error('updateLandingUrl not configured');
            return { result: 'error', error: 'URL not configured' };
        }

        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ changes })
        });

        return response.json();
    }

    /**
     * Apply changes to client-side tiles
     */
    applyChanges(updated, deleted) {
        // Handle deleted tiles
        for (const tile of deleted) {
            this.game.tileManager.removeTile(tile.x, tile.y);
        }

        // Handle updated tiles
        for (const tile of updated) {
            this.game.tileManager.updateTile(tile.x, tile.y, tile.landing_id);
        }

        // Re-render island edges in viewport
        const viewport = this.game.calculateViewport();
        this.game.tileManager.renderIslandEdgeTiles(
            viewport.startX, viewport.startY,
            viewport.width, viewport.height
        );
    }
}

export default LandingEditMode;
