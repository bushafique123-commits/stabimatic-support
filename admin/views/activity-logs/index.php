<header class="page-head">
    <div>
        <h1>Activity Logs</h1>
        <p>Search account, sales, installation, settings, and admin audit events.</p>
    </div>
</header>

<?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<form class="filter-bar activity-log-filter" method="get" action="/admin/activity-logs">
    <input class="form-control" type="search" name="search" placeholder="Search action, module, actor ID, record ID, IP, details" value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <select class="form-select" name="user_type">
        <option value="">All Roles</option>
        <option value="admin" <?= ($filters['user_type'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
        <option value="dealer" <?= ($filters['user_type'] ?? '') === 'dealer' ? 'selected' : '' ?>>Dealer</option>
        <option value="installer" <?= ($filters['user_type'] ?? '') === 'installer' ? 'selected' : '' ?>>Installer</option>
    </select>
    <select class="form-select" name="module">
        <option value="">All Modules</option>
        <?php foreach ($modules as $module): ?>
            <option value="<?= htmlspecialchars($module, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['module'] ?? '') === $module ? 'selected' : '' ?>><?= htmlspecialchars($module, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
    </select>
    <select class="form-select" name="action">
        <option value="">All Actions</option>
        <?php foreach ($actions as $action): ?>
            <option value="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['action'] ?? '') === $action ? 'selected' : '' ?>><?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
    </select>
    <input class="form-control" type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <input class="form-control" type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <button class="btn btn-primary" type="submit">Filter</button>
</form>

<section class="panel table-panel">
    <div class="panel-head">
        <h2>Audit Events</h2>
        <span><?= number_format((int) $total) ?> total</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Actor</th>
                    <th>Event</th>
                    <th>Record</th>
                    <th>Network</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activityLogs)): ?>
                    <tr><td colspan="6" class="text-muted">No activity logs found.</td></tr>
                <?php endif; ?>
                <?php foreach ($activityLogs as $activity): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($activity['created_at'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small>#<?= (int) $activity['id'] ?></small>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($activity['actor_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= htmlspecialchars($activity['user_type'], ENT_QUOTES, 'UTF-8') ?> #<?= (int) $activity['user_id'] ?></small>
                        </td>
                        <td>
                            <span><?= htmlspecialchars($activity['action'], ENT_QUOTES, 'UTF-8') ?></span>
                            <small><?= htmlspecialchars($activity['description'] ?: $activity['module'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td>
                            <span><?= htmlspecialchars($activity['module'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($activity['record_id']): ?>
                                <?php if ($activity['record_url']): ?>
                                    <small><a href="<?= htmlspecialchars($activity['record_url'], ENT_QUOTES, 'UTF-8') ?>">Record #<?= (int) $activity['record_id'] ?></a></small>
                                <?php else: ?>
                                    <small>Record #<?= (int) $activity['record_id'] ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <small>No record</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span><?= htmlspecialchars($activity['ip_address'] ?: 'Unknown', ENT_QUOTES, 'UTF-8') ?></span>
                            <small><?= htmlspecialchars($activity['user_agent'] ?: '', ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="/admin/activity-logs/<?= (int) $activity['id'] ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($lastPage > 1): ?>
    <nav class="pagination-bar">
        <?php
        $query = 'search=' . urlencode($filters['search'] ?? '')
            . '&user_type=' . urlencode($filters['user_type'] ?? '')
            . '&module=' . urlencode($filters['module'] ?? '')
            . '&action=' . urlencode($filters['action'] ?? '')
            . '&date_from=' . urlencode($filters['date_from'] ?? '')
            . '&date_to=' . urlencode($filters['date_to'] ?? '');
        ?>
        <?php if ($page > 1): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/activity-logs?page=<?= $page - 1 ?>&<?= $query ?>">Previous</a>
        <?php endif; ?>
        <span>Page <?= (int) $page ?> of <?= (int) $lastPage ?></span>
        <?php if ($page < $lastPage): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/activity-logs?page=<?= $page + 1 ?>&<?= $query ?>">Next</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
