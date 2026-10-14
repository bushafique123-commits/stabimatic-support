<header class="page-head">
    <div>
        <h1>Installers</h1>
        <p>Review Installer accounts, verification, and installation activity.</p>
    </div>
</header>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($flashError): ?><div class="alert alert-danger"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<form class="filter-bar" method="get" action="/admin/installers">
    <input class="form-control" type="search" name="search" placeholder="Search name, mobile, email, CNIC" value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <select class="form-select" name="status">
        <option value="">All Status</option>
        <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>
    <select class="form-select" name="verification">
        <option value="">All Verification</option>
        <option value="verified" <?= ($filters['verification'] ?? '') === 'verified' ? 'selected' : '' ?>>Verified</option>
        <option value="pending" <?= ($filters['verification'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
    </select>
    <button class="btn btn-primary" type="submit">Filter</button>
</form>

<section class="panel table-panel">
    <div class="panel-head">
        <h2>Installer Accounts</h2>
        <span><?= number_format((int) $total) ?> total</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Verification</th>
                    <th>Installations</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($installers)): ?>
                    <tr><td colspan="6" class="text-muted">No installers found.</td></tr>
                <?php endif; ?>
                <?php foreach ($installers as $installer): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($installer['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small>#<?= (int) $installer['id'] ?> - <?= htmlspecialchars($installer['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td>
                            <span><?= htmlspecialchars($installer['mobile'], ENT_QUOTES, 'UTF-8') ?></span>
                            <small><?= htmlspecialchars($installer['email'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td><span class="badge-soft <?= (int) $installer['is_active'] ? 'badge-success' : 'badge-danger' ?>"><?= (int) $installer['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                        <td><span class="badge-soft <?= (int) $installer['is_verified'] ? 'badge-success' : 'badge-warning' ?>"><?= (int) $installer['is_verified'] ? 'Verified' : 'Pending' ?></span></td>
                        <td><?= number_format((int) $installer['installations_count']) ?></td>
                        <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="/admin/installers/<?= (int) $installer['id'] ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($lastPage > 1): ?>
    <nav class="pagination-bar">
        <?php if ($page > 1): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/installers?page=<?= $page - 1 ?>&search=<?= urlencode($filters['search'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>&verification=<?= urlencode($filters['verification'] ?? '') ?>">Previous</a>
        <?php endif; ?>
        <span>Page <?= (int) $page ?> of <?= (int) $lastPage ?></span>
        <?php if ($page < $lastPage): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/installers?page=<?= $page + 1 ?>&search=<?= urlencode($filters['search'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>&verification=<?= urlencode($filters['verification'] ?? '') ?>">Next</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
