/**
 * Vitest Global Setup
 *
 * This file runs before all tests and sets up global mocks and utilities.
 */

import { vi } from 'vitest';

// Mock global objects that are expected in browser environment
global.APP_CONFIG = {
    gameUpdateInterval: 1000,
    fps: 60,
    gridSize: 64
};

// Mock console methods to reduce noise in tests (optional)
// Uncomment if you want to suppress console output during tests
// global.console = {
//     ...console,
//     log: vi.fn(),
//     debug: vi.fn(),
//     info: vi.fn(),
//     warn: vi.fn(),
// };

// Mock localStorage
const localStorageMock = {
    getItem: vi.fn(),
    setItem: vi.fn(),
    removeItem: vi.fn(),
    clear: vi.fn()
};
global.localStorage = localStorageMock;

// Mock alert, confirm, prompt
global.alert = vi.fn();
global.confirm = vi.fn(() => true);
global.prompt = vi.fn();

// Clean up after each test
afterEach(() => {
    vi.clearAllMocks();
});
