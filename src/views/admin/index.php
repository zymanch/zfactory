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

<script src="/js/admin.js?v=<?= Yii::$app->params['asset_version'] ?? 1 ?>"></script>
