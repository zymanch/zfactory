/**
 * Client-side deposit entity behaviors
 * Mirrors server-side DepositEntityBehavior.php logic
 */

/**
 * Base behavior for extraction buildings that require deposits
 */
export class DepositEntityBehavior {
    constructor(game, entityType) {
        this.game = game;
        this.entityType = entityType;
        this.requiredDepositType = null;
        this.allowedDepositTypeIds = [];

        this.determineRequiredDepositType();
    }

    /**
     * Determine required deposit type based on entity_type_id
     */
    determineRequiredDepositType() {
        const typeId = this.entityType.entity_type_id;

        // Sawmills (500-502) require trees
        if (typeId >= 500 && typeId <= 502) {
            this.requiredDepositType = 'tree';
            this.loadAllowedDepositTypes();
        }
        // Stone Quarries (503-505) require rocks
        else if (typeId >= 503 && typeId <= 505) {
            this.requiredDepositType = 'rock';
            this.loadAllowedDepositTypes();
        }
        // Ore Drills (102, 108, 506) require iron/copper ores
        else if ([102, 108, 506].includes(typeId)) {
            this.requiredDepositType = 'ore';
            this.allowedDepositTypeIds = [300, 301]; // Iron, Copper
        }
        // Mines (507-509) require silver/gold ores
        else if (typeId >= 507 && typeId <= 509) {
            this.requiredDepositType = 'ore';
            this.allowedDepositTypeIds = [304, 305]; // Silver, Gold
        }
        // Quarries (510-512) require aluminum/titanium ores
        else if (typeId >= 510 && typeId <= 512) {
            this.requiredDepositType = 'ore';
            this.allowedDepositTypeIds = [302, 303]; // Aluminum, Titanium
        }
    }

    /**
     * Load allowed deposit types from game data
     */
    loadAllowedDepositTypes() {
        this.allowedDepositTypeIds = [];

        for (const depositTypeId in this.game.depositTypes) {
            const depositType = this.game.depositTypes[depositTypeId];
            if (depositType.type === this.requiredDepositType) {
                this.allowedDepositTypeIds.push(parseInt(depositTypeId));
            }
        }
    }

    /**
     * Check if building can be placed at position
     */
    canBuildAt(tileX, tileY) {
        const width = this.entityType.width || 1;
        const height = this.entityType.height || 1;

        // Find deposits in building area
        const deposits = this.game.depositManager.getDepositsInArea(tileX, tileY, width, height);

        // Must have at least one deposit
        if (deposits.length === 0) {
            return {
                allowed: false,
                error: this.getNoDepositErrorMessage()
            };
        }

        // Check that all deposits are of allowed types
        for (const deposit of deposits) {
            if (!this.allowedDepositTypeIds.includes(deposit.deposit_type_id)) {
                const depositType = this.game.depositTypes[deposit.deposit_type_id];
                const name = depositType ? depositType.name : 'this deposit';
                return {
                    allowed: false,
                    error: `Cannot build on ${name}. ${this.getRequiredDepositMessage()}`
                };
            }
        }

        return {
            allowed: true,
            error: null
        };
    }

    /**
     * Get error message for no deposits found
     */
    getNoDepositErrorMessage() {
        const messages = {
            'tree': 'Requires trees to place sawmill',
            'rock': 'Requires rocks to place stone quarry',
            'ore': 'Requires ore deposits to place mining building'
        };

        return messages[this.requiredDepositType] || 'Requires deposits to build';
    }

    /**
     * Get message about required deposit type
     */
    getRequiredDepositMessage() {
        const typeId = this.entityType.entity_type_id;

        if (typeId >= 500 && typeId <= 502) {
            return 'Sawmills require trees.';
        } else if (typeId >= 503 && typeId <= 505) {
            return 'Stone Quarries require rocks.';
        } else if ([102, 108, 506].includes(typeId)) {
            return 'Ore Drills require iron or copper ore.';
        } else if (typeId >= 507 && typeId <= 509) {
            return 'Mines require silver or gold ore.';
        } else if (typeId >= 510 && typeId <= 512) {
            return 'Quarries require aluminum or titanium ore.';
        }

        return 'Wrong deposit type.';
    }

    /**
     * Get visual preview info for building placement
     */
    getPreviewInfo(tileX, tileY) {
        const check = this.canBuildAt(tileX, tileY);

        return {
            canPlace: check.allowed,
            color: check.allowed ? 0x00ff00 : 0xff0000,
            alpha: 0.3,
            error: check.error
        };
    }

    /**
     * Check if entity should show hover tooltip
     */
    shouldShowHoverInfo() {
        return true;
    }

    /**
     * Check if entity is indestructible
     */
    isIndestructible() {
        return false;
    }
}
