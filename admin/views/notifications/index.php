<header class="page-head">
    <div>
        <h1>Notifications</h1>
        <p>Queue broadcasts for dealers and installers, then review delivery state.</p>
    </div>
</header>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<section class="stat-grid notification-stat-grid">
    <article>
        <span>Queued</span>
        <strong><?= number_format((int) ($stats['queued'] ?? 0)) ?></strong>
    </article>
    <article>
        <span>Sent</span>
        <strong><?= number_format((int) ($stats['sent'] ?? 0)) ?></strong>
    </article>
    <article>
        <span>Failed</span>
        <strong><?= number_format((int) ($stats['failed'] ?? 0)) ?></strong>
    </article>
    <article>
        <span>Active Devices</span>
        <strong><?= number_format((int) ($stats['active_devices'] ?? 0)) ?></strong>
        <small><?= number_format((int) ($stats['custom_preferences'] ?? 0)) ?> custom preference rows</small>
    </article>
</section>

<section class="notification-grid">
    <form class="panel notification-form" method="post" action="/admin/notifications">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="panel-head">
            <h2>New Broadcast</h2>
        </div>

        <label>Audience</label>
        <select class="form-select" name="audience">
            <option value="all" <?= ($form['audience'] ?? '') === 'all' ? 'selected' : '' ?>>All verified users</option>
            <option value="dealer" <?= ($form['audience'] ?? '') === 'dealer' ? 'selected' : '' ?>>Dealers only</option>
            <option value="installer" <?= ($form['audience'] ?? '') === 'installer' ? 'selected' : '' ?>>Installers only</option>
        </select>
        <?php if (!empty($errors['audience'])): ?><small class="field-error"><?= htmlspecialchars($errors['audience'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

        <label>Channel</label>
        <select class="form-select" name="channel">
            <option value="push" <?= ($form['channel'] ?? '') === 'push' ? 'selected' : '' ?>>Push</option>
            <option value="sms" <?= ($form['channel'] ?? '') === 'sms' ? 'selected' : '' ?>>SMS</option>
            <option value="email" <?= ($form['channel'] ?? '') === 'email' ? 'selected' : '' ?>>Email</option>
        </select>

        <label>Category</label>
        <select class="form-select" name="notification_type">
            <option value="general" <?= ($form['notification_type'] ?? '') === 'general' ? 'selected' : '' ?>>General</option>
            <option value="sales" <?= ($form['notification_type'] ?? '') === 'sales' ? 'selected' : '' ?>>Sales</option>
            <option value="installation" <?= ($form['notification_type'] ?? '') === 'installation' ? 'selected' : '' ?>>Installation</option>
            <option value="account" <?= ($form['notification_type'] ?? '') === 'account' ? 'selected' : '' ?>>Account</option>
            <option value="marketing" <?= ($form['notification_type'] ?? '') === 'marketing' ? 'selected' : '' ?>>Offers</option>
        </select>

        <label>Title</label>
        <input class="form-control" name="title" maxlength="120" value="<?= htmlspecialchars($form['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        <?php if (!empty($errors['title'])): ?><small class="field-error"><?= htmlspecialchars($errors['title'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

        <label>Message</label>
        <textarea class="form-control" name="message" rows="6" maxlength="1000" required><?= htmlspecialchars($form['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        <?php if (!empty($errors['message'])): ?><small class="field-error"><?= htmlspecialchars($errors['message'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

        <label>Target URL</label>
        <input class="form-control" name="target_url" maxlength="255" placeholder="/dealer/sales" value="<?= htmlspecialchars($form['target_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($errors['target_url'])): ?><small class="field-error"><?= htmlspecialchars($errors['target_url'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Queue Broadcast</button>
        </div>
    </form>

    <div>
        <form class="filter-bar notification-filter" method="get" action="/admin/notifications">
            <input class="form-control" type="search" name="search" placeholder="Search title, message, or batch" value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <select class="form-select" name="status">
                <option value="">All Statuses</option>
                <?php foreach (['queued' => 'Queued', 'sent' => 'Sent', 'failed' => 'Failed', 'cancelled' => 'Cancelled'] as $value => $label): ?>
                    <option value="<?= $value ?>" <?= ($filters['status'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="channel">
                <option value="">All Channels</option>
                <?php foreach (['push' => 'Push', 'sms' => 'SMS', 'email' => 'Email'] as $value => $label): ?>
                    <option value="<?= $value ?>" <?= ($filters['channel'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit">Filter</button>
        </form>

        <section class="panel table-panel">
            <div class="panel-head">
                <h2>Broadcast Queue</h2>
                <span><?= number_format((int) $total) ?> batches</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Broadcast</th>
                            <th>Audience</th>
                            <th>Status</th>
                            <th>Queued</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($notifications)): ?>
                            <tr><td colspan="5" class="text-muted">No notification broadcasts found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($notifications as $notification): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars($notification['channel'] . ' / ' . $notification['notification_type'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td>
                                    <span><?= htmlspecialchars($notification['audience'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <small><?= number_format((int) $notification['recipient_count']) ?> recipients</small>
                                </td>
                                <td>
                                    <span class="badge-soft badge-warning"><?= number_format((int) $notification['queued_count']) ?> queued</span>
                                    <small><?= number_format((int) $notification['sent_count']) ?> sent, <?= number_format((int) $notification['failed_count']) ?> failed</small>
                                </td>
                                <td>
                                    <span><?= htmlspecialchars($notification['queued_at'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <small><?= htmlspecialchars($notification['created_by'] ?: 'System', ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="/admin/notifications/<?= htmlspecialchars($notification['batch_id'], ENT_QUOTES, 'UTF-8') ?>">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>

<?php if ($lastPage > 1): ?>
    <nav class="pagination-bar">
        <?php
        $query = 'search=' . urlencode($filters['search'] ?? '')
            . '&status=' . urlencode($filters['status'] ?? '')
            . '&channel=' . urlencode($filters['channel'] ?? '')
            . '&audience=' . urlencode($filters['audience'] ?? '')
            . '&notification_type=' . urlencode($filters['notification_type'] ?? '');
        ?>
        <?php if ($page > 1): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/notifications?page=<?= $page - 1 ?>&<?= $query ?>">Previous</a>
        <?php endif; ?>
        <span>Page <?= (int) $page ?> of <?= (int) $lastPage ?></span>
        <?php if ($page < $lastPage): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/notifications?page=<?= $page + 1 ?>&<?= $query ?>">Next</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
