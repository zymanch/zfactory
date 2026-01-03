import { GameMode } from '../modes/gameModeManager.js';

/**
 * TechnologyWindow - окно дерева технологий
 * Показывает: все технологии, их статусы, стоимость, разблокируемое
 */
export class TechnologyWindow {
    constructor(game) {
        this.game = game;
        this.isOpen = false;
        this.element = null;
        this.technologies = [];
        this.selectedTech = null;
    }

    /**
     * Initialize window UI
     */
    init() {
        this.createElement();
    }

    /**
     * Create window HTML element
     */
    createElement() {
        this.element = document.createElement('div');
        this.element.id = 'technology-window';
        this.element.className = 'game-window technology-window';
        this.element.style.cssText = `
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 900px;
            max-width: 95vw;
            height: 600px;
            max-height: 85vh;
            background: rgba(20, 20, 30, 0.98);
            border: 2px solid #4a4a5a;
            border-radius: 8px;
            padding: 0;
            z-index: 10000;
            overflow: hidden;
            box-shadow: 0 4px 30px rgba(0,0,0,0.8);
            flex-direction: column;
        `;

        this.element.innerHTML = `
            <div class="window-header" style="background: #2a2a3a; padding: 12px 15px; border-bottom: 1px solid #4a4a5a; display: flex; justify-content: space-between; align-items: center;">
                <span class="window-title" style="font-size: 16px; font-weight: bold; color: #fff;">Research</span>
                <button class="window-close" style="background: transparent; border: none; color: #fff; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
            </div>
            <div class="window-body" style="display: flex; flex: 1; overflow: hidden;">
                <div class="tech-tree" style="flex: 1; overflow-x: auto; overflow-y: auto; padding: 20px;"></div>
                <div class="tech-details" style="width: 280px; border-left: 1px solid #4a4a5a; padding: 15px; overflow-y: auto; background: rgba(0,0,0,0.2);"></div>
            </div>
        `;

        document.body.appendChild(this.element);

        // Close button returns to NORMAL mode
        this.element.querySelector('.window-close').addEventListener('click', () => {
            this.game.gameModeManager.returnToNormalMode();
        });

        this.element.addEventListener('click', (e) => e.stopPropagation());

        // Tech node click
        this.element.querySelector('.tech-tree').addEventListener('click', (e) => {
            const node = e.target.closest('.tech-node');
            if (node) {
                const techId = parseInt(node.dataset.id);
                this.selectTechnology(techId);
            }
        });

        // Research button click
        this.element.querySelector('.tech-details').addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-research')) {
                const techId = parseInt(e.target.dataset.id);
                this.researchTechnology(techId);
            }
        });
    }

    /**
     * Open window
     */
    async open() {
        await this.loadTechnologies();
        this.renderTree();
        this.selectedTech = null;
        this.renderDetails();
        this.element.style.display = 'flex';
        this.isOpen = true;
    }

    /**
     * Close window (called by GameModeManager during deactivation)
     */
    close() {
        this.element.style.display = 'none';
        this.isOpen = false;
        this.selectedTech = null;
    }

    /**
     * Load technologies from server
     */
    async loadTechnologies() {
        try {
            const response = await fetch('/research/tree');
            const data = await response.json();
            if (data.result === 'ok') {
                this.technologies = data.technologies;
            }
        } catch (e) {
            console.error('Failed to load technologies:', e);
        }
    }

    /**
     * Group technologies by tier
     */
    groupByTier() {
        const byTier = {};
        for (const tech of this.technologies) {
            if (!byTier[tech.tier]) {
                byTier[tech.tier] = [];
            }
            byTier[tech.tier].push(tech);
        }
        return byTier;
    }

    /**
     * Render technology tree
     */
    renderTree() {
        const treeEl = this.element.querySelector('.tech-tree');
        const byTier = this.groupByTier();

        let html = '<div class="tech-tiers" style="display: flex; gap: 40px; min-width: fit-content;">';

        const tierNames = ['', 'Tier 1', 'Tier 2', 'Tier 3', 'Tier 4'];
        const tierColors = ['', '#e74c3c', '#27ae60', '#3498db', '#9b59b6'];

        for (const tier of [1, 2, 3, 4]) {
            const techs = byTier[tier] || [];
            html += `
                <div class="tech-tier" data-tier="${tier}" style="display: flex; flex-direction: column; gap: 15px; min-width: 180px;">
                    <div class="tier-header" style="text-align: center; font-weight: bold; color: ${tierColors[tier]}; padding-bottom: 10px; border-bottom: 2px solid ${tierColors[tier]};">
                        ${tierNames[tier]}
                    </div>
            `;

            for (const tech of techs) {
                html += this.renderTechNode(tech, tierColors[tier]);
            }

            html += '</div>';
        }

        html += '</div>';
        treeEl.innerHTML = html;

        // Render connections (SVG lines between dependencies)
        this.renderConnections();
    }

    /**
     * Render single technology node
     */
    renderTechNode(tech, tierColor) {
        const statusClasses = {
            'locked': 'tech-locked',
            'available': 'tech-available',
            'researched': 'tech-researched'
        };
        const statusClass = statusClasses[tech.status] || '';
        const isSelected = this.selectedTech && this.selectedTech.id === tech.id;

        const bgColor = tech.status === 'researched' ? 'rgba(46, 204, 113, 0.2)' :
            tech.status === 'available' ? 'rgba(241, 196, 15, 0.2)' :
                'rgba(100, 100, 100, 0.2)';

        const borderColor = tech.status === 'researched' ? '#27ae60' :
            tech.status === 'available' ? '#f1c40f' :
                '#555';

        const opacity = tech.status === 'locked' ? '0.5' : '1';

        return `
            <div class="tech-node ${statusClass}${isSelected ? ' selected' : ''}"
                 data-id="${tech.id}"
                 style="
                    background: ${bgColor};
                    border: 2px solid ${isSelected ? '#fff' : borderColor};
                    border-radius: 8px;
                    padding: 12px;
                    cursor: pointer;
                    opacity: ${opacity};
                    transition: all 0.2s;
                 ">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <img src="/assets/tiles/technologies/${tech.icon}"
                         style="width: 32px; height: 32px;"
                         onerror="this.style.display='none'">
                    <span style="font-size: 13px; font-weight: 500; color: #fff;">${tech.name}</span>
                </div>
                ${tech.status === 'researched' ? '<div style="font-size: 10px; color: #27ae60; margin-top: 5px;">Researched</div>' : ''}
            </div>
        `;
    }

    /**
     * Render connection lines between technologies
     */
    renderConnections() {
        // For simplicity, we'll show dependencies in details panel
        // Full SVG connections would require more complex layout calculations
    }

    /**
     * Select a technology
     */
    selectTechnology(techId) {
        this.selectedTech = this.technologies.find(t => t.id === techId) || null;
        this.renderTree(); // Re-render to update selection state
        this.renderDetails();
    }

    /**
     * Render details panel
     */
    renderDetails() {
        const detailsEl = this.element.querySelector('.tech-details');

        if (!this.selectedTech) {
            detailsEl.innerHTML = `
                <div style="color: #888; text-align: center; margin-top: 50px;">
                    Select a technology to view details
                </div>
            `;
            return;
        }

        const tech = this.selectedTech;
        const v = this.game.config?.assetVersion || 1;

        let html = `
            <div style="text-align: center; margin-bottom: 15px;">
                <img src="/assets/tiles/technologies/${tech.icon}"
                     style="width: 64px; height: 64px; margin-bottom: 10px;"
                     onerror="this.style.display='none'">
                <h3 style="margin: 0; color: #fff; font-size: 16px;">${tech.name}</h3>
            </div>
            <p style="color: #aaa; font-size: 12px; line-height: 1.5; margin-bottom: 15px;">
                ${tech.description || 'No description available.'}
            </p>
        `;

        // Status badge
        const statusColors = { locked: '#e74c3c', available: '#f1c40f', researched: '#27ae60' };
        const statusLabels = { locked: 'Locked', available: 'Available', researched: 'Researched' };
        html += `
            <div style="margin-bottom: 15px;">
                <span style="
                    display: inline-block;
                    padding: 4px 12px;
                    background: ${statusColors[tech.status]};
                    border-radius: 4px;
                    font-size: 11px;
                    font-weight: bold;
                    color: #fff;
                ">${statusLabels[tech.status]}</span>
            </div>
        `;

        // Requirements
        if (tech.requires && tech.requires.length > 0) {
            html += `<div style="margin-bottom: 15px;">
                <div style="font-weight: bold; color: #888; font-size: 11px; margin-bottom: 5px;">REQUIRES:</div>
            `;
            for (const reqId of tech.requires) {
                const reqTech = this.technologies.find(t => t.id === reqId);
                const reqColor = reqTech?.status === 'researched' ? '#27ae60' : '#e74c3c';
                const reqIcon = reqTech?.status === 'researched' ? '✓' : '✗';
                html += `
                    <div style="font-size: 12px; color: ${reqColor}; margin: 3px 0;">
                        ${reqIcon} ${reqTech?.name || 'Unknown'}
                    </div>
                `;
            }
            html += `</div>`;
        }

        // Cost
        if (tech.cost && tech.cost.length > 0) {
            html += `<div style="margin-bottom: 15px;">
                <div style="font-weight: bold; color: #888; font-size: 11px; margin-bottom: 5px;">COST:</div>
            `;
            for (const cost of tech.cost) {
                const userAmount = this.getUserResourceAmount(cost.resource_id);
                const hasEnough = userAmount >= cost.quantity;
                const color = hasEnough ? '#27ae60' : '#e74c3c';
                html += `
                    <div style="display: flex; align-items: center; margin: 4px 0; font-size: 12px;">
                        <img src="/assets/tiles/resources/${cost.icon}?v=${v}"
                             style="width: 18px; height: 18px; margin-right: 6px;">
                        <span style="flex: 1; color: #ccc;">${cost.name}</span>
                        <span style="color: ${color};">${userAmount} / ${cost.quantity}</span>
                    </div>
                `;
            }
            html += `</div>`;
        }

        // Unlocks
        if ((tech.unlocks.recipes && tech.unlocks.recipes.length > 0) ||
            (tech.unlocks.entity_types && tech.unlocks.entity_types.length > 0)) {
            html += `<div style="margin-bottom: 15px;">
                <div style="font-weight: bold; color: #888; font-size: 11px; margin-bottom: 5px;">UNLOCKS:</div>
            `;

            // Entity types
            for (const etId of tech.unlocks.entity_types) {
                const entityType = this.game.entityTypes?.[etId];
                if (entityType) {
                    html += `
                        <div style="font-size: 12px; color: #3498db; margin: 3px 0;">
                            Building: ${entityType.name}
                        </div>
                    `;
                }
            }

            // Recipes
            for (const recipeId of tech.unlocks.recipes) {
                const recipe = this.game.recipes?.[recipeId];
                if (recipe) {
                    const outputRes = this.game.resources?.[recipe.output_resource_id];
                    html += `
                        <div style="font-size: 12px; color: #9b59b6; margin: 3px 0;">
                            Recipe: ${outputRes?.name || 'Recipe #' + recipeId}
                        </div>
                    `;
                }
            }

            html += `</div>`;
        }

        // Research button
        if (tech.status === 'available') {
            const canAfford = this.canAffordTechnology(tech);
            const btnStyle = canAfford
                ? 'background: #27ae60; cursor: pointer;'
                : 'background: #555; cursor: not-allowed;';
            html += `
                <button class="btn-research" data-id="${tech.id}"
                        style="
                            width: 100%;
                            padding: 12px;
                            border: none;
                            border-radius: 6px;
                            color: #fff;
                            font-weight: bold;
                            font-size: 14px;
                            margin-top: 10px;
                            ${btnStyle}
                        "
                        ${canAfford ? '' : 'disabled'}>
                    Research
                </button>
            `;
        }

        detailsEl.innerHTML = html;
    }

    /**
     * Get user's amount of a resource
     */
    getUserResourceAmount(resourceId) {
        const userRes = this.game.userResources?.find(r => r.resource_id === resourceId);
        return userRes?.quantity || 0;
    }

    /**
     * Check if user can afford a technology
     */
    canAffordTechnology(tech) {
        if (!tech.cost) return true;
        for (const cost of tech.cost) {
            if (this.getUserResourceAmount(cost.resource_id) < cost.quantity) {
                return false;
            }
        }
        return true;
    }

    /**
     * Research a technology
     */
    async researchTechnology(techId) {
        try {
            const response = await fetch('/research/unlock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ technology_id: techId })
            });

            const data = await response.json();
            if (data.result === 'ok') {
                // Reload technologies and user resources
                await this.loadTechnologies();

                // Update user resources in game
                if (this.game.resourcePanel) {
                    await this.game.resourcePanel.refresh();
                }

                // Re-render
                this.selectTechnology(techId);
            } else {
                console.error('Research failed:', data.error);
                alert(data.error || 'Research failed');
            }
        } catch (e) {
            console.error('Research error:', e);
            alert('Failed to research technology');
        }
    }
}

export default TechnologyWindow;
