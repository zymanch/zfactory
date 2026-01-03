<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Regions</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input type="text" id="region-name-filter" class="form-control form-control-sm" placeholder="Filter by name">
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Difficulty</th>
                                <th>Size</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="regions-table-body">
                            <tr><td colspan="5" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Users</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" id="user-name-filter" class="form-control form-control-sm" placeholder="Filter by username">
                    </div>
                    <div class="col-md-6">
                        <select id="user-admin-filter" class="form-select form-select-sm">
                            <option value="">All users</option>
                            <option value="1">Admins only</option>
                            <option value="0">Non-admins only</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Admin</th>
                                <th>Region</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                            <tr><td colspan="5" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Technologies -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Technologies</h5>
                <button class="btn btn-sm btn-success" onclick="adminPanel.openTechModal()">+ Add Technology</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Tier</th>
                                <th>Cost</th>
                                <th>Requires</th>
                                <th>Unlocks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="technologies-table-body">
                            <tr><td colspan="7" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Technology Modal -->
<div class="modal fade" id="techModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header">
                <h5 class="modal-title" id="techModalTitle">Technology</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="tech-id">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" id="tech-name">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Tier</label>
                            <select class="form-select" id="tech-tier">
                                <option value="1">Tier 1 (Red)</option>
                                <option value="2">Tier 2 (Green)</option>
                                <option value="3">Tier 3 (Blue)</option>
                                <option value="4">Tier 4 (Purple)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Icon</label>
                            <input type="text" class="form-control" id="tech-icon" placeholder="icon.svg">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" id="tech-description" rows="2"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Cost (resource_id:quantity, comma separated)</label>
                            <input type="text" class="form-control" id="tech-costs" placeholder="200:10, 201:20">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Requires (technology IDs, comma separated)</label>
                            <input type="text" class="form-control" id="tech-requires" placeholder="1, 2">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Unlocks Recipes (IDs, comma separated)</label>
                            <input type="text" class="form-control" id="tech-recipes" placeholder="3, 4, 5">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Unlocks Entity Types (IDs, comma separated)</label>
                            <input type="text" class="form-control" id="tech-entities" placeholder="100, 101">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="adminPanel.saveTechnology()">Save</button>
            </div>
        </div>
    </div>
</div>

<script src="/js/admin.js?v=<?= Yii::$app->params['asset_version'] ?? 1 ?>"></script>
