/**
 * Regions Map Application
 * Manages region discovery, fog of war, and ship travel
 */

class RegionsApp {
    constructor() {
        this.regions = [];
        this.currentRegionId = null;
        this.shipViewRadius = 300;
        this.shipJumpDistance = 500;

        this.mapCanvas = document.getElementById('regions-map');
        this.ctx = this.mapCanvas.getContext('2d');

        // Map viewport
        this.viewportX = 0;
        this.viewportY = 0;
        this.zoom = 1; // 1:1 scale

        // Loaded images
        this.images = {};

        // Tooltip
        this.tooltip = document.getElementById('region-tooltip');

        this.init();
    }

    async init() {
        // Set canvas size
        this.resizeCanvas();
        window.addEventListener('resize', () => this.resizeCanvas());

        // Load regions
        await this.loadRegions();

        // Setup interactions
        this.setupMouseEvents();

        // Render
        this.render();
    }

    resizeCanvas() {
        const sidebar = document.getElementById('regions-sidebar');
        const sidebarWidth = sidebar.offsetWidth;

        this.mapCanvas.width = window.innerWidth - sidebarWidth;
        this.mapCanvas.height = window.innerHeight;

        this.render();
    }

    async loadRegions() {
        try {
            const response = await fetch('/regions/list', {
                method: 'GET',
                headers: {'Content-Type': 'application/json'}
            });

            const data = await response.json();

            if (data.result === 'ok') {
                this.regions = data.regions;
                this.currentRegionId = data.current_region_id;
                this.shipViewRadius = data.ship_view_radius;
                this.shipJumpDistance = data.ship_jump_distance;

                // Update UI
                document.getElementById('ship-view-radius').textContent = this.shipViewRadius;
                document.getElementById('ship-jump-distance').textContent = this.shipJumpDistance;

                // Center viewport on current region with zoom
                const currentRegion = this.regions.find(r => r.is_current);
                if (currentRegion) {
                    this.viewportX = -(currentRegion.x * this.zoom) + (this.mapCanvas.width / 2);
                    this.viewportY = -(currentRegion.y * this.zoom) + (this.mapCanvas.height / 2);
                }

                // Load region images
                await this.loadImages();

                // Render regions list
                this.renderRegionsList();

                console.log('[Regions] Loaded', this.regions.length, 'regions');
            } else {
                console.error('[Regions] Failed to load:', data.message);
            }
        } catch (error) {
            console.error('[Regions] Load error:', error);
        }
    }

    async loadImages() {
        const promises = this.regions.map(region => {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => {
                    this.images[region.region_id] = img;
                    resolve();
                };
                img.onerror = () => resolve(); // Skip failed images
                img.src = `/assets/images/regions/${region.image_url}`;
            });
        });

        await Promise.all(promises);
    }

    setupMouseEvents() {
        let isDragging = false;
        let lastX = 0;
        let lastY = 0;

        this.mapCanvas.addEventListener('mousedown', (e) => {
            isDragging = true;
            lastX = e.clientX;
            lastY = e.clientY;
        });

        this.mapCanvas.addEventListener('mousemove', (e) => {
            if (isDragging) {
                const dx = e.clientX - lastX;
                const dy = e.clientY - lastY;

                this.viewportX += dx;
                this.viewportY += dy;

                lastX = e.clientX;
                lastY = e.clientY;

                this.render();
            } else {
                // Show tooltip
                this.showTooltip(e);
            }
        });

        this.mapCanvas.addEventListener('mouseup', () => {
            isDragging = false;
        });

        this.mapCanvas.addEventListener('mouseleave', () => {
            isDragging = false;
            this.hideTooltip();
        });

        this.mapCanvas.addEventListener('click', (e) => {
            if (!isDragging) {
                this.handleClick(e);
            }
        });

        // Zoom with mouse wheel
        this.mapCanvas.addEventListener('wheel', (e) => {
            e.preventDefault();
            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            const oldZoom = this.zoom;
            this.zoom *= delta;
            this.zoom = Math.max(0.3, Math.min(2, this.zoom));

            // Adjust viewport to zoom towards mouse position
            const rect = this.mapCanvas.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            const zoomRatio = this.zoom / oldZoom;
            this.viewportX = mouseX - (mouseX - this.viewportX) * zoomRatio;
            this.viewportY = mouseY - (mouseY - this.viewportY) * zoomRatio;

            this.render();
        });
    }

    render() {
        // Clear canvas
        this.ctx.clearRect(0, 0, this.mapCanvas.width, this.mapCanvas.height);

        // Draw background (same as game)
        this.ctx.fillStyle = '#1a1a2e';
        this.ctx.fillRect(0, 0, this.mapCanvas.width, this.mapCanvas.height);

        // Draw grid
        this.drawGrid();

        // Draw regions
        for (const region of this.regions) {
            this.drawRegion(region);
        }

        // Draw connections to current region
        this.drawConnections();

        // Draw fog of war (circular visibility)
        this.drawFogOfWar();
    }

    drawGrid() {
        this.ctx.strokeStyle = 'rgba(255, 255, 255, 0.05)';
        this.ctx.lineWidth = 1;

        const gridSize = 500 * this.zoom;

        const startX = Math.floor(-this.viewportX / gridSize) * gridSize;
        const startY = Math.floor(-this.viewportY / gridSize) * gridSize;

        for (let x = startX; x < this.mapCanvas.width; x += gridSize) {
            this.ctx.beginPath();
            this.ctx.moveTo(x + this.viewportX, 0);
            this.ctx.lineTo(x + this.viewportX, this.mapCanvas.height);
            this.ctx.stroke();
        }

        for (let y = startY; y < this.mapCanvas.height; y += gridSize) {
            this.ctx.beginPath();
            this.ctx.moveTo(0, y + this.viewportY);
            this.ctx.lineTo(this.mapCanvas.width, y + this.viewportY);
            this.ctx.stroke();
        }
    }

    drawRegion(region) {
        const screenX = (region.x * this.zoom) + this.viewportX;
        const screenY = (region.y * this.zoom) + this.viewportY;
        const size = 80 * this.zoom;

        // Draw image if loaded
        const img = this.images[region.region_id];
        if (img) {
            this.ctx.save();
            this.ctx.globalAlpha = region.is_current ? 1 : 0.9;
            this.ctx.drawImage(img, screenX - size / 2, screenY - size / 2, size, size);
            this.ctx.restore();
        } else {
            // Fallback circle
            const colors = ['#8BC34A', '#FFC107', '#FF9800', '#F44336', '#9C27B0'];
            this.ctx.fillStyle = colors[region.difficulty - 1] || '#888';
            this.ctx.beginPath();
            this.ctx.arc(screenX, screenY, size / 2, 0, Math.PI * 2);
            this.ctx.fill();
        }

        // Draw border if current
        if (region.is_current) {
            this.ctx.strokeStyle = '#4CAF50';
            this.ctx.lineWidth = 3;
            this.ctx.beginPath();
            this.ctx.arc(screenX, screenY, size / 2 + 5, 0, Math.PI * 2);
            this.ctx.stroke();
        }

        // Draw label
        this.ctx.fillStyle = '#ffffff';
        this.ctx.font = `${12 * this.zoom}px Arial`;
        this.ctx.textAlign = 'center';
        this.ctx.fillText(region.name, screenX, screenY + size / 2 + 15 * this.zoom);
    }

    drawConnections() {
        const currentRegion = this.regions.find(r => r.is_current);
        if (!currentRegion) return;

        // 1. Draw possible travel routes (dashed green lines)
        const currentX = (currentRegion.x * this.zoom) + this.viewportX;
        const currentY = (currentRegion.y * this.zoom) + this.viewportY;

        for (const region of this.regions) {
            if (region.is_current) continue;

            const canTravel = region.can_travel;
            if (!canTravel) continue;

            const targetX = (region.x * this.zoom) + this.viewportX;
            const targetY = (region.y * this.zoom) + this.viewportY;

            this.ctx.strokeStyle = 'rgba(76, 175, 80, 0.3)';
            this.ctx.lineWidth = 2;
            this.ctx.setLineDash([5, 5]);
            this.ctx.beginPath();
            this.ctx.moveTo(currentX, currentY);
            this.ctx.lineTo(targetX, targetY);
            this.ctx.stroke();
            this.ctx.setLineDash([]);
        }

        // 2. Draw traveled route (solid yellow lines)
        for (const region of this.regions) {
            if (!region.is_visited || !region.from_region_id) continue;

            const fromRegion = this.regions.find(r => r.region_id === region.from_region_id);
            if (!fromRegion) continue;

            const fromX = (fromRegion.x * this.zoom) + this.viewportX;
            const fromY = (fromRegion.y * this.zoom) + this.viewportY;
            const toX = (region.x * this.zoom) + this.viewportX;
            const toY = (region.y * this.zoom) + this.viewportY;

            this.ctx.strokeStyle = 'rgba(255, 193, 7, 0.8)'; // Yellow
            this.ctx.lineWidth = 3;
            this.ctx.setLineDash([]);
            this.ctx.beginPath();
            this.ctx.moveTo(fromX, fromY);
            this.ctx.lineTo(toX, toY);
            this.ctx.stroke();
        }
    }

    drawFogOfWar() {
        const currentRegion = this.regions.find(r => r.is_current);
        if (!currentRegion) return;

        // Create temporary canvas for fog
        const fogCanvas = document.createElement('canvas');
        fogCanvas.width = this.mapCanvas.width;
        fogCanvas.height = this.mapCanvas.height;
        const fogCtx = fogCanvas.getContext('2d');

        // Draw full black fog on temp canvas
        fogCtx.fillStyle = 'rgba(0, 0, 0, 0.95)';
        fogCtx.fillRect(0, 0, fogCanvas.width, fogCanvas.height);

        // Cut out visible areas using destination-out
        fogCtx.globalCompositeOperation = 'destination-out';

        // 1. Cut out area around current region (view radius)
        const currentX = (currentRegion.x * this.zoom) + this.viewportX;
        const currentY = (currentRegion.y * this.zoom) + this.viewportY;
        const viewRadius = this.shipViewRadius * this.zoom;

        const gradient = fogCtx.createRadialGradient(
            currentX, currentY, viewRadius * 0.85,
            currentX, currentY, viewRadius * 1.2
        );
        gradient.addColorStop(0, 'rgba(255, 255, 255, 1)');    // Full cut in center
        gradient.addColorStop(0.5, 'rgba(255, 255, 255, 0.7)'); // Fade
        gradient.addColorStop(1, 'rgba(255, 255, 255, 0)');     // No cut outside

        fogCtx.fillStyle = gradient;
        fogCtx.fillRect(0, 0, fogCanvas.width, fogCanvas.height);

        // 2. Cut out all visited regions with their view_radius
        for (const region of this.regions) {
            if (region.is_visited && !region.is_current && region.visited_view_radius > 0) {
                const regionX = (region.x * this.zoom) + this.viewportX;
                const regionY = (region.y * this.zoom) + this.viewportY;
                const visitedRadius = region.visited_view_radius * this.zoom;

                // Create gradient for smooth edge
                const visitedGradient = fogCtx.createRadialGradient(
                    regionX, regionY, visitedRadius * 0.85,
                    regionX, regionY, visitedRadius * 1.2
                );
                visitedGradient.addColorStop(0, 'rgba(255, 255, 255, 1)');    // Full cut in center
                visitedGradient.addColorStop(0.5, 'rgba(255, 255, 255, 0.7)'); // Fade
                visitedGradient.addColorStop(1, 'rgba(255, 255, 255, 0)');     // No cut outside

                fogCtx.fillStyle = visitedGradient;
                fogCtx.beginPath();
                fogCtx.arc(regionX, regionY, visitedRadius * 1.2, 0, Math.PI * 2);
                fogCtx.fill();
            }
        }

        // Draw fog canvas onto main canvas
        this.ctx.drawImage(fogCanvas, 0, 0);
    }

    showTooltip(e) {
        const region = this.getRegionAtMouse(e);

        if (region) {
            this.tooltip.classList.remove('hidden');
            this.tooltip.style.left = e.clientX + 10 + 'px';
            this.tooltip.style.top = e.clientY + 10 + 'px';

            document.getElementById('tooltip-name').textContent = region.name;
            document.getElementById('tooltip-distance').textContent = `Distance: ${region.distance}`;
            document.getElementById('tooltip-difficulty').textContent = `Difficulty: ${region.difficulty}`;

            // Show resources information
            document.getElementById('tooltip-resources').innerHTML = region.resources || 'No resources';

            document.getElementById('tooltip-status').textContent = region.is_current ? 'Current Location' : (region.can_travel ? 'Can Travel' : 'Too Far');
        } else {
            this.hideTooltip();
        }
    }

    hideTooltip() {
        this.tooltip.classList.add('hidden');
    }

    handleClick(e) {
        const region = this.getRegionAtMouse(e);

        if (region && region.can_travel && !region.is_current) {
            this.travelToRegion(region.region_id);
        }
    }

    getRegionAtMouse(e) {
        const rect = this.mapCanvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;

        for (const region of this.regions) {
            const screenX = (region.x * this.zoom) + this.viewportX;
            const screenY = (region.y * this.zoom) + this.viewportY;
            const size = 80 * this.zoom;

            const dx = mouseX - screenX;
            const dy = mouseY - screenY;
            const dist = Math.sqrt(dx * dx + dy * dy);

            if (dist < size / 2) {
                return region;
            }
        }

        return null;
    }

    async travelToRegion(regionId) {
        try {
            const response = await fetch('/regions/travel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCSRFToken()
                },
                body: JSON.stringify({region_id: regionId})
            });

            const data = await response.json();

            if (data.result === 'ok') {
                console.log('[Regions] Traveled:', data.message);
                // Open game view
                window.location.href = '/game';
            } else {
                console.error('[Regions] Travel failed:', data.message);
                alert(data.message);
            }
        } catch (error) {
            console.error('[Regions] Travel error:', error);
        }
    }

    renderRegionsList() {
        const listContainer = document.getElementById('regions-list');
        listContainer.innerHTML = '';

        // Sort by distance
        const sorted = [...this.regions].sort((a, b) => a.distance - b.distance);

        for (const region of sorted) {
            const item = document.createElement('div');
            item.className = 'region-item';
            if (region.is_current) item.classList.add('current');
            if (!region.can_travel && !region.is_current) item.classList.add('disabled');

            item.innerHTML = `
                <div class="region-name">${region.name}</div>
                <div class="region-distance">Distance: ${region.distance}</div>
                <div class="region-difficulty">Difficulty: ${'★'.repeat(region.difficulty)}</div>
            `;

            if (region.can_travel && !region.is_current) {
                item.style.cursor = 'pointer';
                item.addEventListener('click', () => this.travelToRegion(region.region_id));
            }

            listContainer.appendChild(item);
        }
    }

    getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.regionsApp = new RegionsApp();
});
