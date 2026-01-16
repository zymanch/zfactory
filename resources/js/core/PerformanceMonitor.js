/**
 * PerformanceMonitor - tracks execution time of game managers
 *
 * Usage:
 *   const monitor = new PerformanceMonitor();
 *   monitor.start('electricityManager');
 *   // ... code to measure ...
 *   monitor.end('electricityManager');
 *
 *   // View stats in console
 *   monitor.printStats();
 */
export class PerformanceMonitor {
    constructor(sampleSize = 60) {
        this.sampleSize = sampleSize; // Number of frames to average over
        this.timings = new Map(); // Map<name, {samples: [], startTime}>
        this.enabled = true;
    }

    /**
     * Start timing a section
     */
    start(name) {
        if (!this.enabled) return;

        if (!this.timings.has(name)) {
            this.timings.set(name, {
                samples: [],
                startTime: null,
                totalTime: 0,
                count: 0,
                min: Infinity,
                max: 0
            });
        }

        const timing = this.timings.get(name);
        timing.startTime = performance.now();
    }

    /**
     * End timing a section
     */
    end(name) {
        if (!this.enabled) return;

        const endTime = performance.now();
        const timing = this.timings.get(name);

        if (!timing || timing.startTime === null) {
            console.warn(`PerformanceMonitor: No start time for "${name}"`);
            return;
        }

        const duration = endTime - timing.startTime;
        timing.startTime = null;

        // Add to samples (rolling window)
        timing.samples.push(duration);
        if (timing.samples.length > this.sampleSize) {
            timing.samples.shift();
        }

        // Update statistics
        timing.totalTime += duration;
        timing.count++;
        timing.min = Math.min(timing.min, duration);
        timing.max = Math.max(timing.max, duration);
    }

    /**
     * Get average time for a section
     */
    getAverage(name) {
        const timing = this.timings.get(name);
        if (!timing || timing.samples.length === 0) return 0;

        const sum = timing.samples.reduce((a, b) => a + b, 0);
        return sum / timing.samples.length;
    }

    /**
     * Get all statistics
     */
    getStats() {
        const stats = [];

        for (const [name, timing] of this.timings) {
            if (timing.samples.length === 0) continue;

            const avg = this.getAverage(name);
            stats.push({
                name,
                avg: avg.toFixed(2),
                min: timing.min.toFixed(2),
                max: timing.max.toFixed(2),
                samples: timing.samples.length,
                total: timing.totalTime.toFixed(2),
                count: timing.count
            });
        }

        // Sort by average time (descending)
        stats.sort((a, b) => parseFloat(b.avg) - parseFloat(a.avg));

        return stats;
    }

    /**
     * Print statistics to console
     */
    printStats() {
        const stats = this.getStats();

        console.log('\n=== Performance Stats (last 60 frames) ===');
        console.log('Manager'.padEnd(30) + 'Avg(ms)'.padEnd(10) + 'Min(ms)'.padEnd(10) + 'Max(ms)'.padEnd(10) + '%');
        console.log('-'.repeat(70));

        // Calculate total time for percentage
        const totalAvg = stats.reduce((sum, s) => sum + parseFloat(s.avg), 0);

        for (const stat of stats) {
            const percentage = ((parseFloat(stat.avg) / totalAvg) * 100).toFixed(1);
            console.log(
                stat.name.padEnd(30) +
                stat.avg.padEnd(10) +
                stat.min.padEnd(10) +
                stat.max.padEnd(10) +
                percentage + '%'
            );
        }

        console.log('-'.repeat(70));
        console.log('Total'.padEnd(30) + totalAvg.toFixed(2) + 'ms');
        console.log('FPS estimate: ' + (1000 / totalAvg).toFixed(1));
        console.log('\nUse game.perfMonitor.reset() to clear stats');
        console.log('Use game.perfMonitor.enabled = false to disable monitoring');
    }

    /**
     * Reset all statistics
     */
    reset() {
        this.timings.clear();
        console.log('Performance stats reset');
    }

    /**
     * Enable/disable monitoring
     */
    setEnabled(enabled) {
        this.enabled = enabled;
        console.log(`Performance monitoring ${enabled ? 'enabled' : 'disabled'}`);
    }
}

export default PerformanceMonitor;
