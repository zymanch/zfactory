/**
 * Integration tests for Pipe System Manager
 * Tests BFS calculation, priority distribution, and fluid management
 */
import { describe, it, expect } from 'vitest';
import { MapBuilder } from '../helpers/MapBuilder.js';
import { createGameInstance, initializeManagers } from '../helpers/GameSimulator.js';

describe('Pipe System Tests', () => {
    it('should calculate connected pipe systems using BFS', async () => {
        // Create 3 connected horizontal pipes + 1 separate
        const state = MapBuilder.build(
            ['1111#2#'], // 4 pipes in a row, then separate pipe
            {
                '1': { type: 'pipe' },
                '2': { type: 'pipe' }
            }
        );

        const game = createGameInstance(state);
        await initializeManagers(game);

        // Calculate systems
        game.pipeSystemManager.calculateSystems();

        // Should have 2 systems
        expect(game.pipeSystemManager.systems.size).toBe(2);

        // First system should have 4 pipes (entities 1,2,3,4)
        const system1 = game.pipeSystemManager.getSystemForEntity(1);
        expect(system1.entity_ids).toHaveLength(4);

        // Second system should have 1 pipe (entity 5)
        const system2 = game.pipeSystemManager.getSystemForEntity(5);
        expect(system2.entity_ids).toHaveLength(1);
    });

    it('should calculate system capacity from pipe powers', async () => {
        const state = MapBuilder.build(
            ['111#'],
            {
                '1': { type: 'pipe' } // power=100 each
            }
        );

        const game = createGameInstance(state);
        await initializeManagers(game);

        game.pipeSystemManager.calculateSystems();

        const system = game.pipeSystemManager.getSystemForEntity(1);

        // Capacity should be sum of powers: 3 × 100 = 300
        expect(system.max_capacity).toBe(300);
        expect(system.current_amount).toBe(0);
        expect(system.resource_id).toBeNull();
    });

    it('should add fluid to pipe system', async () => {
        const state = MapBuilder.build(
            ['11#'],
            {
                '1': { type: 'pipe' }
            }
        );

        const game = createGameInstance(state);
        await initializeManagers(game);

        game.pipeSystemManager.calculateSystems();

        // Add water (resource_id=300) to system
        const success = game.pipeSystemManager.addFluid(1, 300, 50);

        expect(success).toBe(true);

        const system = game.pipeSystemManager.getSystemForEntity(1);
        expect(system.resource_id).toBe(300);
        expect(system.current_amount).toBe(50);
    });

    it('should prevent mixing different fluids', async () => {
        const state = MapBuilder.build(
            ['1#'],
            {
                '1': { type: 'pipe' }
            }
        );

        const game = createGameInstance(state);
        await initializeManagers(game);

        game.pipeSystemManager.calculateSystems();

        // Add water first
        game.pipeSystemManager.addFluid(1, 300, 50);

        // Try to add oil (should fail)
        const success = game.pipeSystemManager.addFluid(1, 301, 50);

        expect(success).toBe(false);

        const system = game.pipeSystemManager.getSystemForEntity(1);
        expect(system.resource_id).toBe(300); // Still water
        expect(system.current_amount).toBe(50); // Unchanged
    });

    it('should prevent overflow', async () => {
        const state = MapBuilder.build(
            ['1#'],
            {
                '1': { type: 'pipe' } // power=100
            }
        );

        const game = createGameInstance(state);
        await initializeManagers(game);

        game.pipeSystemManager.calculateSystems();

        // Try to add more than capacity
        const success = game.pipeSystemManager.addFluid(1, 300, 150);

        expect(success).toBe(false);

        const system = game.pipeSystemManager.getSystemForEntity(1);
        expect(system.current_amount).toBe(0); // Nothing added
    });

    it('should take fluid from pipe system', async () => {
        const state = MapBuilder.build(
            ['1#'],
            {
                '1': { type: 'pipe' }
            }
        );

        const game = createGameInstance(state);
        await initializeManagers(game);

        game.pipeSystemManager.calculateSystems();

        // Add fluid
        game.pipeSystemManager.addFluid(1, 300, 100);

        // Take 40
        const taken = game.pipeSystemManager.takeFluid(1, 300, 40);

        expect(taken).toBe(40);

        const system = game.pipeSystemManager.getSystemForEntity(1);
        expect(system.current_amount).toBe(60);
    });

    it('should clear resource_id when empty', async () => {
        const state = MapBuilder.build(
            ['1#'],
            {
                '1': { type: 'pipe' }
            }
        );

        const game = createGameInstance(state);
        await initializeManagers(game);

        game.pipeSystemManager.calculateSystems();

        // Add and remove all fluid
        game.pipeSystemManager.addFluid(1, 300, 50);
        game.pipeSystemManager.takeFluid(1, 300, 50);

        const system = game.pipeSystemManager.getSystemForEntity(1);
        expect(system.current_amount).toBe(0);
        expect(system.resource_id).toBeNull();
    });

    it('should handle L-shaped pipe connections', async () => {
        // Create L-shaped pipe system
        const state = MapBuilder.build(
            [
                '11#',
                '#1#'
            ],
            {
                '1': { type: 'pipe' }
            }
        );

        const game = createGameInstance(state);
        await initializeManagers(game);

        game.pipeSystemManager.calculateSystems();

        // All 3 should be in same system
        const system = game.pipeSystemManager.getSystemForEntity(1);
        expect(system.entity_ids).toHaveLength(3);
    });

    it('should recalculate systems when pipes change', async () => {
        const state = MapBuilder.build(
            ['11#'],
            {
                '1': { type: 'pipe' }
            }
        );

        const game = createGameInstance(state);
        await initializeManagers(game);

        game.pipeSystemManager.calculateSystems();
        expect(game.pipeSystemManager.systems.size).toBe(1);

        // Add new disconnected pipe manually
        const pipe3 = {
            entity_id: 999,
            entity_type_id: 131,
            x: 0,
            y: 2,  // Note: gridSize=1 in MapBuilder
            state: 'built'
        };
        game.entityData.set('entity_999', pipe3);

        // Recalculate
        game.pipeSystemManager.calculateSystems();

        // Now should have 2 systems
        expect(game.pipeSystemManager.systems.size).toBe(2);
    });
});
