import { describe, it, expect, beforeEach } from 'vitest';
import { ConveyorVariantManager } from '../../resources/js/modules/ConveyorVariantManager.js';
import { createMockGame } from '../helpers/mockGame.js';

/**
 * ConveyorVariantManager Tests
 *
 * Tests variant calculation (0-15) for all conveyor types and configurations.
 * Variant is a 4-bit mask: [DOWN][UP][RIGHT][LEFT]
 *
 * Test organization:
 * 1. Isolated conveyors (variant 0)
 * 2. Single direction connections (variants 1, 2, 4, 8)
 * 3. Two-way connections (variants 3, 5, 6, 9, 10, 12)
 * 4. Three-way junctions (variants 7, 11, 13, 14)
 * 5. Four-way junction (variant 15)
 * 6. Underground belt interactions
 * 7. Edge cases
 */
describe('ConveyorVariantManager', () => {
    let game;
    let manager;

    beforeEach(() => {
        game = createMockGame();
        manager = new ConveyorVariantManager(game);
    });

    describe('Isolated conveyor (variant 0)', () => {
        it('should return variant 0 for single conveyor with no neighbors', () => {
            // Map: ###
            //      #C#
            //      ###
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 100, x: 1, y: 1 }]
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' }
            };

            const entity = game.entityData.get('entity_1');
            const variant = manager.calculateVariant(entity);

            expect(variant).toBe(0);
        });

        it('should return variant 0 for conveyor with non-conveyor neighbors', () => {
            // Non-conveyor neighbors should be ignored
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 100, x: 1, y: 1 }],
                ['entity_2', { entity_id: 2, entity_type_id: 200, x: 0, y: 1 }],  // building
                ['entity_3', { entity_id: 3, entity_type_id: 300, x: 2, y: 1 }]   // building
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                200: { folder: 'furnace', type: 'crafting' },
                300: { folder: 'drill', type: 'mining' }
            };

            const entity = game.entityData.get('entity_1');
            const variant = manager.calculateVariant(entity);

            expect(variant).toBe(0);
        });
    });

    describe('Single direction connections', () => {
        it('should return variant 1 (LEFT) for conveyor with left neighbor moving right', () => {
            // Map: C→C
            //      1  2
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 100, x: 0, y: 0 }],
                ['entity_2', { entity_id: 2, entity_type_id: 100, x: 1, y: 0 }]
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' }  // moves right
            };

            const entity2 = game.entityData.get('entity_2');
            const variant = manager.calculateVariant(entity2);

            expect(variant).toBe(1); // Bit 0: LEFT connection
        });

        it('should return variant 2 (RIGHT) for conveyor moving right to neighbor', () => {
            // Map: C→C
            //      1  2
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 100, x: 0, y: 0 }],
                ['entity_2', { entity_id: 2, entity_type_id: 100, x: 1, y: 0 }]
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' }
            };

            const entity1 = game.entityData.get('entity_1');
            const variant = manager.calculateVariant(entity1);

            expect(variant).toBe(2); // Bit 1: RIGHT connection
        });

        it('should return variant 4 (UP) for conveyor with top neighbor moving down', () => {
            // Map:  1
            //       ↓
            //       2
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 101, x: 0, y: 0 }],  // down
                ['entity_2', { entity_id: 2, entity_type_id: 100, x: 0, y: 1 }]   // right
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                101: { folder: 'conveyor_down', type: 'conveyor' }
            };

            const entity2 = game.entityData.get('entity_2');
            const variant = manager.calculateVariant(entity2);

            expect(variant).toBe(4); // Bit 2: UP connection
        });

        it('should return variant 8 (DOWN) for conveyor moving down to neighbor', () => {
            // Map:  1
            //       ↓
            //       2
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 101, x: 0, y: 0 }],  // down
                ['entity_2', { entity_id: 2, entity_type_id: 100, x: 0, y: 1 }]   // right
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                101: { folder: 'conveyor_down', type: 'conveyor' }
            };

            const entity1 = game.entityData.get('entity_1');
            const variant = manager.calculateVariant(entity1);

            expect(variant).toBe(8); // Bit 3: DOWN connection
        });
    });

    describe('Two-way connections', () => {
        it('should return variant 3 (LEFT+RIGHT) for straight horizontal line', () => {
            // Map: C→C→C
            //      1  2  3
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 100, x: 0, y: 0 }],
                ['entity_2', { entity_id: 2, entity_type_id: 100, x: 1, y: 0 }],
                ['entity_3', { entity_id: 3, entity_type_id: 100, x: 2, y: 0 }]
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' }
            };

            const entity2 = game.entityData.get('entity_2');
            const variant = manager.calculateVariant(entity2);

            expect(variant).toBe(3); // Bits 0+1: LEFT+RIGHT
        });

        it('should return variant 12 (UP+DOWN) for straight vertical line', () => {
            // Map:  1
            //       ↓
            //       2
            //       ↓
            //       3
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 101, x: 0, y: 0 }],  // down
                ['entity_2', { entity_id: 2, entity_type_id: 101, x: 0, y: 1 }],  // down
                ['entity_3', { entity_id: 3, entity_type_id: 100, x: 0, y: 2 }]   // right
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                101: { folder: 'conveyor_down', type: 'conveyor' }
            };

            const entity2 = game.entityData.get('entity_2');
            const variant = manager.calculateVariant(entity2);

            expect(variant).toBe(12); // Bits 2+3: UP+DOWN
        });

        it('should return variant 5 (LEFT+UP) for L-junction corner', () => {
            // Map: #2
            //      1↓
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 100, x: 0, y: 1 }],  // right
                ['entity_2', { entity_id: 2, entity_type_id: 101, x: 1, y: 0 }],  // down
                ['entity_3', { entity_id: 3, entity_type_id: 100, x: 1, y: 1 }]   // right (corner)
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                101: { folder: 'conveyor_down', type: 'conveyor' }
            };

            const entity3 = game.entityData.get('entity_3');
            const variant = manager.calculateVariant(entity3);

            expect(variant).toBe(5); // Bits 0+2: LEFT+UP
        });

        it('should return variant 6 (RIGHT+UP) for L-junction corner', () => {
            // Map: 2#
            //       ↓3
            game.entityData = new Map([
                ['entity_2', { entity_id: 2, entity_type_id: 101, x: 0, y: 0 }],  // down
                ['entity_3', { entity_id: 3, entity_type_id: 100, x: 0, y: 1 }],  // right (corner)
                ['entity_4', { entity_id: 4, entity_type_id: 100, x: 1, y: 1 }]   // right
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                101: { folder: 'conveyor_down', type: 'conveyor' }
            };

            const entity3 = game.entityData.get('entity_3');
            const variant = manager.calculateVariant(entity3);

            expect(variant).toBe(6); // Bits 1+2: RIGHT+UP
        });

        it('should return variant 9 (LEFT+DOWN) for L-junction corner', () => {
            // Map: 1↓
            //      #3
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 100, x: 0, y: 0 }],  // right
                ['entity_3', { entity_id: 3, entity_type_id: 101, x: 1, y: 0 }],  // down (corner)
                ['entity_4', { entity_id: 4, entity_type_id: 100, x: 1, y: 1 }]   // right
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                101: { folder: 'conveyor_down', type: 'conveyor' }
            };

            const entity3 = game.entityData.get('entity_3');
            const variant = manager.calculateVariant(entity3);

            expect(variant).toBe(9); // Bits 0+3: LEFT+DOWN
        });

        it('should return variant 10 (RIGHT+DOWN) for L-junction corner', () => {
            // Map:  3→5
            //       4
            //       ↑
            game.entityData = new Map([
                ['entity_3', { entity_id: 3, entity_type_id: 100, x: 0, y: 0 }],  // right (corner)
                ['entity_4', { entity_id: 4, entity_type_id: 103, x: 0, y: 1 }],  // up (moving toward entity 3)
                ['entity_5', { entity_id: 5, entity_type_id: 100, x: 1, y: 0 }]   // right
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                103: { folder: 'conveyor_up', type: 'conveyor' }
            };

            const entity3 = game.entityData.get('entity_3');
            const variant = manager.calculateVariant(entity3);

            expect(variant).toBe(10); // Bits 1+3: RIGHT+DOWN
        });
    });

    describe('Three-way junctions (T-junctions)', () => {
        it('should return variant 7 (LEFT+RIGHT+UP) for T-junction', () => {
            // Map:  2
            //      1↓3
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 100, x: 0, y: 1 }],  // right
                ['entity_2', { entity_id: 2, entity_type_id: 101, x: 1, y: 0 }],  // down
                ['entity_3', { entity_id: 3, entity_type_id: 100, x: 1, y: 1 }],  // right (T-junction)
                ['entity_4', { entity_id: 4, entity_type_id: 100, x: 2, y: 1 }]   // right
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                101: { folder: 'conveyor_down', type: 'conveyor' }
            };

            const entity3 = game.entityData.get('entity_3');
            const variant = manager.calculateVariant(entity3);

            expect(variant).toBe(7); // Bits 0+1+2: LEFT+RIGHT+UP
        });

        it('should return variant 11 (LEFT+RIGHT+DOWN) for T-junction', () => {
            // Map: 1→3→5
            //       4
            //       ↑
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 100, x: 0, y: 0 }],  // right
                ['entity_3', { entity_id: 3, entity_type_id: 100, x: 1, y: 0 }],  // right (T-junction)
                ['entity_4', { entity_id: 4, entity_type_id: 103, x: 1, y: 1 }],  // up (moving toward entity 3)
                ['entity_5', { entity_id: 5, entity_type_id: 100, x: 2, y: 0 }]   // right
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                103: { folder: 'conveyor_up', type: 'conveyor' }
            };

            const entity3 = game.entityData.get('entity_3');
            const variant = manager.calculateVariant(entity3);

            expect(variant).toBe(11); // Bits 0+1+3: LEFT+RIGHT+DOWN
        });

        it('should return variant 13 (LEFT+UP+DOWN) for T-junction', () => {
            // Map:  2
            //      1↓3
            //       ↓4
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 100, x: 0, y: 1 }],  // right
                ['entity_2', { entity_id: 2, entity_type_id: 101, x: 1, y: 0 }],  // down
                ['entity_3', { entity_id: 3, entity_type_id: 101, x: 1, y: 1 }],  // down (T-junction)
                ['entity_4', { entity_id: 4, entity_type_id: 100, x: 1, y: 2 }]   // right
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                101: { folder: 'conveyor_down', type: 'conveyor' }
            };

            const entity3 = game.entityData.get('entity_3');
            const variant = manager.calculateVariant(entity3);

            expect(variant).toBe(13); // Bits 0+2+3: LEFT+UP+DOWN
        });

        it('should return variant 14 (RIGHT+UP+DOWN) for T-junction', () => {
            // Map:  2
            //       ↓
            //       3→5
            //       4
            //       ↑
            game.entityData = new Map([
                ['entity_2', { entity_id: 2, entity_type_id: 101, x: 0, y: 0 }],  // down
                ['entity_3', { entity_id: 3, entity_type_id: 100, x: 0, y: 1 }],  // right (T-junction)
                ['entity_4', { entity_id: 4, entity_type_id: 103, x: 0, y: 2 }],  // up (moving toward entity 3)
                ['entity_5', { entity_id: 5, entity_type_id: 100, x: 1, y: 1 }]   // right
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                101: { folder: 'conveyor_down', type: 'conveyor' },
                103: { folder: 'conveyor_up', type: 'conveyor' }
            };

            const entity3 = game.entityData.get('entity_3');
            const variant = manager.calculateVariant(entity3);

            expect(variant).toBe(14); // Bits 1+2+3: RIGHT+UP+DOWN
        });
    });

    describe('Four-way junction (variant 15)', () => {
        it('should return variant 15 for conveyor with connections on all 4 sides', () => {
            // Map:  2
            //       ↓
            //      1→3→5
            //       4
            //       ↑
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 100, x: 0, y: 1 }],  // right
                ['entity_2', { entity_id: 2, entity_type_id: 101, x: 1, y: 0 }],  // down
                ['entity_3', { entity_id: 3, entity_type_id: 100, x: 1, y: 1 }],  // right (4-way junction)
                ['entity_4', { entity_id: 4, entity_type_id: 103, x: 1, y: 2 }],  // up (moving toward entity 3)
                ['entity_5', { entity_id: 5, entity_type_id: 100, x: 2, y: 1 }]   // right
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                101: { folder: 'conveyor_down', type: 'conveyor' },
                103: { folder: 'conveyor_up', type: 'conveyor' }
            };

            const entity3 = game.entityData.get('entity_3');
            const variant = manager.calculateVariant(entity3);

            expect(variant).toBe(15); // Bits 0+1+2+3: ALL connections
        });
    });

    describe('Underground belt interactions', () => {
        it('should connect regular conveyor to underground_belt_in (RIGHT)', () => {
            // Map: C→U  (C=conveyor_right, U=underground_belt_in)
            //      1  2
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 100, x: 0, y: 0 }],
                ['entity_2', { entity_id: 2, entity_type_id: 812, x: 1, y: 0 }]
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                812: { folder: 'underground_belt_in', type: 'conveyor' }
            };

            const entity1 = game.entityData.get('entity_1');
            const variant = manager.calculateVariant(entity1);

            expect(variant).toBe(2); // RIGHT connection to underground
        });

        it('should connect underground_belt_in to regular conveyor (LEFT)', () => {
            // Map: C→U  (C=conveyor_right, U=underground_belt_in)
            //      1  2
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 100, x: 0, y: 0 }],
                ['entity_2', { entity_id: 2, entity_type_id: 812, x: 1, y: 0 }]
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                812: { folder: 'underground_belt_in', type: 'conveyor' }
            };

            const entity2 = game.entityData.get('entity_2');
            const variant = manager.calculateVariant(entity2);

            expect(variant).toBe(1); // LEFT connection from regular conveyor
        });

        it('should connect underground_belt_out to regular conveyor (RIGHT)', () => {
            // Map: O→C  (O=underground_belt_out, C=conveyor_right)
            //      1  2
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 816, x: 0, y: 0 }],
                ['entity_2', { entity_id: 2, entity_type_id: 100, x: 1, y: 0 }]
            ]);
            game.entityTypes = {
                816: { folder: 'underground_belt_out', type: 'conveyor' },
                100: { folder: 'conveyor', type: 'conveyor' }
            };

            const entity1 = game.entityData.get('entity_1');
            const variant = manager.calculateVariant(entity1);

            expect(variant).toBe(2); // RIGHT connection to regular conveyor
        });

        it('should connect regular conveyor to underground_belt_out (LEFT)', () => {
            // Map: O→C  (O=underground_belt_out, C=conveyor_right)
            //      1  2
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 816, x: 0, y: 0 }],
                ['entity_2', { entity_id: 2, entity_type_id: 100, x: 1, y: 0 }]
            ]);
            game.entityTypes = {
                816: { folder: 'underground_belt_out', type: 'conveyor' },
                100: { folder: 'conveyor', type: 'conveyor' }
            };

            const entity2 = game.entityData.get('entity_2');
            const variant = manager.calculateVariant(entity2);

            expect(variant).toBe(1); // LEFT connection from underground out
        });

        it('should work with all underground belt orientations (down)', () => {
            // Map:  C
            //       ↓
            //       U
            //       1
            //       ↓
            //       2
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 101, x: 0, y: 0 }],  // down
                ['entity_2', { entity_id: 2, entity_type_id: 813, x: 0, y: 1 }]   // underground_belt_in_down
            ]);
            game.entityTypes = {
                101: { folder: 'conveyor_down', type: 'conveyor' },
                813: { folder: 'underground_belt_in_down', type: 'conveyor' }
            };

            const entity1 = game.entityData.get('entity_1');
            const variant = manager.calculateVariant(entity1);

            expect(variant).toBe(8); // DOWN connection to underground
        });
    });

    describe('Edge cases', () => {
        it('should NOT connect when neighbor points away', () => {
            // Map: C→←C  (both moving away from each other, no connection)
            //      1  2
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 102, x: 0, y: 0 }],  // left (moving away)
                ['entity_2', { entity_id: 2, entity_type_id: 100, x: 1, y: 0 }]   // right (moving away)
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                102: { folder: 'conveyor_left', type: 'conveyor' }
            };

            const entity1 = game.entityData.get('entity_1');
            const variant = manager.calculateVariant(entity1);

            expect(variant).toBe(0); // No connection (both pointing away from each other)
        });

        it('should work with mixed speeds (normal and dual)', () => {
            // Map: C→D  (C=conveyor, D=dual_conveyor)
            //      1  2
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 100, x: 0, y: 0 }],
                ['entity_2', { entity_id: 2, entity_type_id: 808, x: 1, y: 0 }]
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                808: { folder: 'conveyor', type: 'conveyor' }  // dual uses same folder
            };

            const entity1 = game.entityData.get('entity_1');
            const variant = manager.calculateVariant(entity1);

            expect(variant).toBe(2); // RIGHT connection (speed doesn't matter for connections)
        });

        it('should handle conveyors at map edge (no neighbors out of bounds)', () => {
            // Single conveyor at 0,0 (no negative coordinates possible)
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 100, x: 0, y: 0 }]
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' }
            };

            const entity = game.entityData.get('entity_1');
            const variant = manager.calculateVariant(entity);

            expect(variant).toBe(0); // No connections (neighbors don't exist)
        });

        it('should handle blueprint state conveyors', () => {
            // Blueprint state shouldn't affect variant calculation
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 100, x: 0, y: 0, state: 'blueprint' }],
                ['entity_2', { entity_id: 2, entity_type_id: 100, x: 1, y: 0, state: 'built' }]
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' }
            };

            const entity1 = game.entityData.get('entity_1');
            const variant = manager.calculateVariant(entity1);

            expect(variant).toBe(2); // RIGHT connection (state doesn't affect connections)
        });
    });

    describe('All orientations', () => {
        it('should work with conveyor_up orientation', () => {
            // Map:  2
            //       ↑
            //       1
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 103, x: 0, y: 1 }],  // up
                ['entity_2', { entity_id: 2, entity_type_id: 100, x: 0, y: 0 }]   // right
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                103: { folder: 'conveyor_up', type: 'conveyor' }
            };

            const entity1 = game.entityData.get('entity_1');
            const variant = manager.calculateVariant(entity1);

            expect(variant).toBe(4); // UP connection
        });

        it('should work with conveyor_left orientation', () => {
            // Map: 2←1
            game.entityData = new Map([
                ['entity_1', { entity_id: 1, entity_type_id: 102, x: 1, y: 0 }],  // left
                ['entity_2', { entity_id: 2, entity_type_id: 100, x: 0, y: 0 }]   // right
            ]);
            game.entityTypes = {
                100: { folder: 'conveyor', type: 'conveyor' },
                102: { folder: 'conveyor_left', type: 'conveyor' }
            };

            const entity1 = game.entityData.get('entity_1');
            const variant = manager.calculateVariant(entity1);

            expect(variant).toBe(1); // LEFT connection
        });
    });
});
