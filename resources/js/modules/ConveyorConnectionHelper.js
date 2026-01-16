/**
 * ConveyorConnectionHelper - Utility for checking entity connection capabilities
 *
 * Replaces hardcoded orientation checks with data-driven approach using
 * input_connections and output_connections from entity_type table.
 *
 * Examples:
 * - Regular conveyor (right): input='left', output='right'
 * - Splitter (right): input='left', output='up,down'
 * - Underground IN: input='left', output=null
 * - Underground OUT: input=null, output='right'
 */
export class ConveyorConnectionHelper {
    /**
     * Check if entity can receive resources from specified direction
     * @param {Object} entityType - The entity type object from game.entityTypes
     * @param {string} direction - Direction: 'up', 'down', 'left', 'right'
     * @param {number|null} position - Position index (0-based) for large entities, null for any position
     * @returns {boolean}
     *
     * Examples:
     * canReceiveFrom({input_connections: 'left'}, 'left') => true
     * canReceiveFrom({input_connections: 'left,up'}, 'up') => true
     * canReceiveFrom({input_connections: 'up_1'}, 'up', 0) => true (position 0 = up_1)
     */
    static canReceiveFrom(entityType, direction, position = null) {
        if (!entityType) return false;

        // Handle both array (from API) and string (legacy) formats
        const inputs = Array.isArray(entityType.input_connections)
            ? entityType.input_connections
            : (entityType.input_connections ? entityType.input_connections.split(',').map(s => s.trim()) : []);

        if (inputs.length === 0) return false;

        // For 1x1 entities or when position not specified - check base direction
        if (position === null) {
            return inputs.includes(direction);
        }

        // For large entities - check specific position (1-based: up_1, up_2, etc)
        const positionedInput = `${direction}_${position + 1}`;

        // Match either specific position or generic direction
        return inputs.includes(positionedInput) || inputs.includes(direction);
    }

    /**
     * Check if entity can output resources to specified direction
     * @param {Object} entityType - The entity type object from game.entityTypes
     * @param {string} direction - Direction: 'up', 'down', 'left', 'right'
     * @param {number|null} position - Position index (0-based) for large entities, null for any position
     * @returns {boolean}
     *
     * Examples:
     * canOutputTo({output_connections: 'right'}, 'right') => true
     * canOutputTo({output_connections: 'up,down'}, 'up') => true (splitter)
     * canOutputTo({output_connections: null}, 'right') => false (underground IN)
     */
    static canOutputTo(entityType, direction, position = null) {
        if (!entityType) return false;

        // Handle both array (from API) and string (legacy) formats
        const outputs = Array.isArray(entityType.output_connections)
            ? entityType.output_connections
            : (entityType.output_connections ? entityType.output_connections.split(',').map(s => s.trim()) : []);

        if (outputs.length === 0) return false;

        if (position === null) {
            return outputs.includes(direction);
        }

        const positionedOutput = `${direction}_${position + 1}`;
        return outputs.includes(positionedOutput) || outputs.includes(direction);
    }

    /**
     * Check if entity is underground belt input (resources go underground)
     * @param {Object} entityType
     * @returns {boolean}
     *
     * Example: Underground IN has input but no output
     */
    static isUndergroundIn(entityType) {
        if (!entityType) return false;

        const hasInput = Array.isArray(entityType.input_connections)
            ? entityType.input_connections.length > 0
            : !!entityType.input_connections;

        const hasOutput = Array.isArray(entityType.output_connections)
            ? entityType.output_connections.length > 0
            : !!entityType.output_connections;

        return hasInput && !hasOutput;
    }

    /**
     * Check if entity is underground belt output (resources come from underground)
     * @param {Object} entityType
     * @returns {boolean}
     *
     * Example: Underground OUT has output but no input
     */
    static isUndergroundOut(entityType) {
        if (!entityType) return false;

        const hasInput = Array.isArray(entityType.input_connections)
            ? entityType.input_connections.length > 0
            : !!entityType.input_connections;

        const hasOutput = Array.isArray(entityType.output_connections)
            ? entityType.output_connections.length > 0
            : !!entityType.output_connections;

        return !hasInput && hasOutput;
    }

    /**
     * Check if entity is a splitter (multiple outputs)
     * @param {Object} entityType
     * @returns {boolean}
     *
     * Example: Splitter has 2 outputs like 'up,down'
     */
    static isSplitter(entityType) {
        if (!entityType) return false;

        // Handle both array (from API) and string (legacy) formats
        const outputs = Array.isArray(entityType.output_connections)
            ? entityType.output_connections
            : (entityType.output_connections ? entityType.output_connections.split(',').map(s => s.trim()) : []);

        return outputs.length > 1;
    }
}

export default ConveyorConnectionHelper;
