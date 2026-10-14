<header class="page-head">
    <div>
        <h1><?= $sale ? htmlspecialchars($sale['invoice_number'], ENT_QUOTES, 'UTF-8') : 'Sale' ?></h1>
        <p><?= $sale ? htmlspecialchars($sale['device_serial'], ENT_QUOTES, 'UTF-8') : 'Sale record unavailable' ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="/admin/sales">Back</a>
</header>

<?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<?php if ($sale): ?>
    <section class="detail-grid">
        <article class="panel">
            <div class="panel-head">
                <h2>Sale</h2>
                <span>#<?= (int) $sale['id'] ?></span>
            </div>
            <dl class="detail-list">
                <div><dt>Invoice</dt><dd><?= htmlspecialchars($sale['invoice_number'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Sale Date</dt><dd><?= htmlspecialchars($sale['sale_date'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Customer</dt><dd><?= htmlspecialchars($sale['customer_name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Customer Mobile</dt><dd><?= htmlspecialchars($sale['customer_mobile'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Device Model</dt><dd><?= htmlspecialchars($sale['device_model'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Device Serial</dt><dd><?= htmlspecialchars($sale['device_serial'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            </dl>
        </article>

        <article class="panel">
            <div class="panel-head">
                <h2>Dealer</h2>
                <a href="/admin/dealers/<?= (int) $sale['dealer_id'] ?>">Open Dealer</a>
            </div>
            <dl class="detail-list">
                <div><dt>Name</dt><dd><?= htmlspecialchars($sale['dealer_name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Mobile</dt><dd><?= htmlspecialchars($sale['dealer_mobile'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Email</dt><dd><?= htmlspecialchars($sale['dealer_email'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>CNIC</dt><dd><?= htmlspecialchars($sale['dealer_cnic'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Status</dt><dd><?= (int) $sale['dealer_is_active'] ? 'Active' : 'Inactive' ?></dd></div>
                <div><dt>Verification</dt><dd><?= (int) $sale['dealer_is_verified'] ? 'Verified' : 'Pending' ?></dd></div>
            </dl>
        </article>
    </section>

    <section class="panel table-panel action-panel">
        <div class="panel-head">
            <h2>Matched Installations</h2>
            <span><?= count($matchedInstallations) ?> found</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Installed At</th>
                        <th>Installer</th>
                        <th>Customer</th>
                        <th>Picture</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($matchedInstallations)): ?>
                        <tr><td colspan="4" class="text-muted">No active installations match this sale device serial/model.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($matchedInstallations as $installation): ?>
                        <tr>
                            <td><?= htmlspecialchars($installation['installed_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <a href="/admin/installers/<?= (int) $installation['installer_id'] ?>"><?= htmlspecialchars($installation['installer_name'], ENT_QUOTES, 'UTF-8') ?></a>
                                <small><?= htmlspecialchars($installation['installer_mobile'], ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($installation['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars($installation['customer_mobile'], ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td>
                                <?php if ($installation['installation_picture']): ?>
                                    <a href="/api/<?= htmlspecialchars(ltrim($installation['installation_picture'], '/'), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Open</a>
                                <?php else: ?>
                                    <span class="text-muted">None</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel activity-panel">
        <div class="panel-head">
            <h2>Sale Activity</h2>
            <span><?= count($activity) ?> latest</span>
        </div>
        <?php if (empty($activity)): ?>
            <p class="empty-text">No activity has been recorded for this sale.</p>
        <?php else: ?>
            <div class="activity-list">
                <?php foreach ($activity as $item): ?>
                    <article>
                        <div>
                            <strong><?= htmlspecialchars($item['action'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span><?= htmlspecialchars($item['description'] ?: $item['module'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <small><?= htmlspecialchars($item['user_type'], ENT_QUOTES, 'UTF-8') ?> #<?= (int) $item['user_id'] ?> - <?= htmlspecialchars($item['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
