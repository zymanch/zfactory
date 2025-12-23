import * as PIXI from 'pixi.js';

export class CloudManager {
    constructor(game) {
        this.game = game;
        this.clouds = [];
        this.cloudTextures = [];
        this.cloudLayer = null;
        this.parallaxFactor = 0.3;
    }

    async init() {
        this.cloudLayer = new PIXI.Container();
        // Add to stage directly (not worldContainer) to avoid double transformation
        this.game.app.stage.addChildAt(this.cloudLayer, 0);
        await this.loadCloudTextures();
        this.generateClouds();
    }

    async loadCloudTextures() {
        for (let i = 1; i <= 12; i++) {
            const url = this.game.assetUrl(`/assets/clouds/cloud_${i}.svg`);
            try {
                const texture = await PIXI.Assets.load(url);
                this.cloudTextures.push(texture);
            } catch (e) {
                console.warn('Failed to load cloud:', url);
            }
        }
    }

    generateClouds() {
        const mapWidth = 3200;
        const mapHeight = 1800;
        const cloudCount = 12; // Fixed count for better distribution

        // Divide map into grid cells for even distribution
        const cols = 4;
        const rows = 3;
        const cellWidth = mapWidth / cols;
        const cellHeight = mapHeight / rows;

        for (let i = 0; i < cloudCount; i++) {
            const texture = this.cloudTextures[Math.floor(Math.random() * this.cloudTextures.length)];
            const width = texture.width;
            const height = texture.height;

            // Place clouds in grid cells with random offset
            const cellX = i % cols;
            const cellY = Math.floor(i / cols);

            // Random position within cell, with padding
            const padding = 100;
            const x = cellX * cellWidth + padding + Math.random() * (cellWidth - padding * 2 - width);
            const y = cellY * cellHeight + padding + Math.random() * (cellHeight - padding * 2 - height);

            const sprite = new PIXI.Sprite(texture);
            sprite.x = x;
            sprite.y = y;
            sprite.scale.set(5); // Make clouds 5x larger

            const speed = (Math.random() * 10 + 10) / 60; // 10-20 px/sec
            const direction = Math.random() > 0.5 ? 1 : -1;

            this.clouds.push({
                sprite: sprite,
                speed: speed * direction,
                width: width * 5, // Store scaled width
                height: height * 5 // Store scaled height
            });

            this.cloudLayer.addChild(sprite);
        }
    }

    checkCollision(x, y, width, height, existing) {
        const buffer = 20;
        for (const cloud of existing) {
            if (x < cloud.x + cloud.width + buffer &&
                x + width + buffer > cloud.x &&
                y < cloud.y + cloud.height + buffer &&
                y + height + buffer > cloud.y) {
                return true;
            }
        }
        return false;
    }

    update() {
        const mapWidth = 3200;

        for (const cloud of this.clouds) {
            cloud.sprite.x += cloud.speed;

            // Disappear only when fully off screen (right edge passes left border)
            if (cloud.speed > 0 && cloud.sprite.x > mapWidth) {
                cloud.sprite.x = -cloud.width;
            } else if (cloud.speed < 0 && cloud.sprite.x + cloud.width < 0) {
                cloud.sprite.x = mapWidth;
            }
        }
    }

    applyParallax() {
        const camera = this.game.camera;
        const zoom = this.game.zoom;

        // Apply parallax relative to stage (clouds move slower than world)
        // Since cloudLayer is in stage (not worldContainer), we apply transformation directly
        this.cloudLayer.x = -camera.x * zoom * this.parallaxFactor;
        this.cloudLayer.y = -camera.y * zoom * this.parallaxFactor;
        this.cloudLayer.scale.set(zoom); // Apply zoom to clouds
    }
}
