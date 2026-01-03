// Admin panel main page
let adminPanel;

class AdminPanel {
    constructor() {
        this.regionsUrl = '/admin/regions';
        this.usersUrl = '/admin/users';
        this.technologiesUrl = '/admin/technologies';
        this.technologies = [];
        this.init();
    }

    init() {
        this.loadRegions();
        this.loadUsers();
        this.loadTechnologies();
        this.initFilters();
    }

    async loadRegions(filters = {}) {
        const params = new URLSearchParams(filters);
        const response = await fetch(`${this.regionsUrl}?${params}`);
        const data = await response.json();

        if (data.result === 'ok') {
            this.renderRegions(data.regions);
        }
    }

    renderRegions(regions) {
        const tbody = document.getElementById('regions-table-body');
        if (!regions.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No regions found</td></tr>';
            return;
        }

        tbody.innerHTML = regions.map(region => `
            <tr>
                <td>${region.region_id}</td>
                <td>${region.name}</td>
                <td>${region.difficulty}</td>
                <td>${region.width}×${region.height}</td>
                <td>
                    <a href="/admin/edit-map?region_id=${region.region_id}"
                       class="btn btn-sm btn-primary">Edit Map</a>
                </td>
            </tr>
        `).join('');
    }

    async loadUsers(filters = {}) {
        const params = new URLSearchParams(filters);
        const response = await fetch(`${this.usersUrl}?${params}`);
        const data = await response.json();

        if (data.result === 'ok') {
            this.renderUsers(data.users);
        }
    }

    renderUsers(users) {
        const tbody = document.getElementById('users-table-body');
        if (!users.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No users found</td></tr>';
            return;
        }

        tbody.innerHTML = users.map(user => `
            <tr>
                <td>${user.user_id}</td>
                <td>${user.username}</td>
                <td>${user.email || '-'}</td>
                <td>${user.is_admin ? '✓' : ''}</td>
                <td>${user.current_region_id || '-'}</td>
            </tr>
        `).join('');
    }

    initFilters() {
        let regionTimeout, userTimeout;

        document.getElementById('region-name-filter').addEventListener('input', (e) => {
            clearTimeout(regionTimeout);
            regionTimeout = setTimeout(() => {
                this.loadRegions({ name: e.target.value });
            }, 300);
        });

        document.getElementById('user-name-filter').addEventListener('input', (e) => {
            clearTimeout(userTimeout);
            userTimeout = setTimeout(() => {
                const isAdmin = document.getElementById('user-admin-filter').value;
                this.loadUsers({
                    username: e.target.value,
                    is_admin: isAdmin
                });
            }, 300);
        });

        document.getElementById('user-admin-filter').addEventListener('change', (e) => {
            const username = document.getElementById('user-name-filter').value;
            this.loadUsers({
                username: username,
                is_admin: e.target.value
            });
        });
    }

    // ============ Technologies ============
    async loadTechnologies() {
        const response = await fetch(this.technologiesUrl);
        const data = await response.json();

        if (data.result === 'ok') {
            this.technologies = data.technologies;
            this.renderTechnologies(data.technologies);
        }
    }

    renderTechnologies(technologies) {
        const tbody = document.getElementById('technologies-table-body');
        if (!technologies.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No technologies found</td></tr>';
            return;
        }

        const tierColors = { 1: '#e74c3c', 2: '#27ae60', 3: '#3498db', 4: '#9b59b6' };

        tbody.innerHTML = technologies.map(tech => {
            const costs = tech.costs.map(c => `${c.name}:${c.quantity}`).join(', ') || '-';
            const requires = tech.requires.join(', ') || '-';
            const unlocks = [
                ...tech.unlocks_recipes.map(id => `R${id}`),
                ...tech.unlocks_entities.map(id => `E${id}`)
            ].join(', ') || '-';

            return `
                <tr>
                    <td>${tech.technology_id}</td>
                    <td>${tech.name}</td>
                    <td><span class="badge" style="background:${tierColors[tech.tier]}">Tier ${tech.tier}</span></td>
                    <td><small>${costs}</small></td>
                    <td><small>${requires}</small></td>
                    <td><small>${unlocks}</small></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="adminPanel.editTechnology(${tech.technology_id})">Edit</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="adminPanel.deleteTechnology(${tech.technology_id})">Del</button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    openTechModal(tech = null) {
        document.getElementById('tech-id').value = tech ? tech.technology_id : '';
        document.getElementById('tech-name').value = tech ? tech.name : '';
        document.getElementById('tech-tier').value = tech ? tech.tier : 1;
        document.getElementById('tech-icon').value = tech ? tech.icon : '';
        document.getElementById('tech-description').value = tech ? tech.description : '';
        document.getElementById('tech-costs').value = tech ? tech.costs.map(c => `${c.resource_id}:${c.quantity}`).join(', ') : '';
        document.getElementById('tech-requires').value = tech ? tech.requires.join(', ') : '';
        document.getElementById('tech-recipes').value = tech ? tech.unlocks_recipes.join(', ') : '';
        document.getElementById('tech-entities').value = tech ? tech.unlocks_entities.join(', ') : '';

        document.getElementById('techModalTitle').textContent = tech ? 'Edit Technology' : 'New Technology';

        const modal = new bootstrap.Modal(document.getElementById('techModal'));
        modal.show();
    }

    editTechnology(id) {
        const tech = this.technologies.find(t => t.technology_id === id);
        if (tech) {
            this.openTechModal(tech);
        }
    }

    async saveTechnology() {
        const parseIds = (str) => str.split(',').map(s => parseInt(s.trim())).filter(n => !isNaN(n) && n > 0);
        const parseCosts = (str) => str.split(',').map(s => {
            const [rid, qty] = s.split(':').map(x => parseInt(x.trim()));
            return (rid > 0 && qty > 0) ? { resource_id: rid, quantity: qty } : null;
        }).filter(c => c);

        const data = {
            technology_id: parseInt(document.getElementById('tech-id').value) || 0,
            name: document.getElementById('tech-name').value,
            tier: parseInt(document.getElementById('tech-tier').value),
            icon: document.getElementById('tech-icon').value,
            description: document.getElementById('tech-description').value,
            costs: parseCosts(document.getElementById('tech-costs').value),
            requires: parseIds(document.getElementById('tech-requires').value),
            unlocks_recipes: parseIds(document.getElementById('tech-recipes').value),
            unlocks_entities: parseIds(document.getElementById('tech-entities').value),
        };

        const response = await fetch('/admin/save-technology', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.result === 'ok') {
            bootstrap.Modal.getInstance(document.getElementById('techModal')).hide();
            this.loadTechnologies();
        } else {
            alert('Error: ' + (result.error || 'Unknown error'));
        }
    }

    async deleteTechnology(id) {
        if (!confirm('Delete this technology?')) return;

        const response = await fetch('/admin/delete-technology', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ technology_id: id })
        });

        const result = await response.json();
        if (result.result === 'ok') {
            this.loadTechnologies();
        } else {
            alert('Error: ' + (result.error || 'Unknown error'));
        }
    }
}

// Initialize when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    adminPanel = new AdminPanel();
    window.adminPanel = adminPanel;
});
