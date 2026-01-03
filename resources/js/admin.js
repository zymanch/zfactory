// Admin panel main page
class AdminPanel {
    constructor() {
        this.regionsUrl = '/admin/regions';
        this.usersUrl = '/admin/users';
        this.init();
    }

    init() {
        this.loadRegions();
        this.loadUsers();
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
}

// Initialize when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    new AdminPanel();
});
