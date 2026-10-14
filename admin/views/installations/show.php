<header class="page-head">
    <div>
        <h1><?= $installation ? htmlspecialchars($installation['invoice_number'] ?: $installation['serial_number'], ENT_QUOTES, 'UTF-8') : 'Installation' ?></h1>
        <p><?= $installation ? htmlspecialchars($installation['installed_at'], ENT_QUOTES, 'UTF-8') : 'Installation record unavailable' ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="/admin/installations">Back</a>
</header>

<?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<?php if ($installation): ?>
    <section class="detail-grid">
        <article class="panel">
            <div class="panel-head">
                <h2>Installation</h2>
                <span>#<?= (int) $installation['id'] ?></span>
            </div>
            <?php if ($installation['installation_picture']): ?>
                <a class="image-preview" href="/api/<?= htmlspecialchars(ltrim($installation['installation_picture'], '/'), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                    <img src="/api/<?= htmlspecialchars(ltrim($installation['installation_picture'], '/'), ENT_QUOTES, 'UTF-8') ?>" alt="Installation picture">
                </a>
            <?php endif; ?>
            <dl class="detail-list">
                <div><dt>Invoice Number</dt><dd><?= htmlspecialchars($installation['invoice_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Model Number</dt><dd><?= htmlspecialchars($installation['device_model'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Serial Number</dt><dd><?= htmlspecialchars($installation['serial_number'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Installed At</dt><dd><?= htmlspecialchars($installation['installed_at'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Customer</dt><dd><?= htmlspecialchars($installation['customer_name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Customer Mobile</dt><dd><?= htmlspecialchars($installation['customer_mobile'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Customer Email</dt><dd><?= htmlspecialchars($installation['customer_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
            </dl>
        </article>

        <article class="panel">
            <div class="panel-head">
                <h2>Installer</h2>
                <a href="/admin/installers/<?= (int) $installation['installer_id'] ?>">Open Installer</a>
            </div>
            <dl class="detail-list">
                <div><dt>Name</dt><dd><?= htmlspecialchars($installation['installer_name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Mobile</dt><dd><?= htmlspecialchars($installation['installer_mobile'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Email</dt><dd><?= htmlspecialchars($installation['installer_email'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>CNIC</dt><dd><?= htmlspecialchars($installation['installer_cnic'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Status</dt><dd><?= (int) $installation['installer_is_active'] ? 'Active' : 'Inactive' ?></dd></div>
                <div><dt>Verification</dt><dd><?= (int) $installation['installer_is_verified'] ? 'Verified' : 'Pending' ?></dd></div>
            </dl>
        </article>
    </section>

    <section class="panel action-panel">
        <div class="panel-head">
            <h2>Matched Dealer Sale</h2>
            <?php if ($matchedSale): ?>
                <a href="/admin/sales/<?= (int) $matchedSale['id'] ?>">Open Sale</a>
            <?php endif; ?>
        </div>
        <?php if (!$matchedSale): ?>
            <p class="empty-text">No active Dealer sale matches this installation device serial/model.</p>
        <?php else: ?>
            <dl class="detail-list">
                <div><dt>Invoice</dt><dd><?= htmlspecialchars($matchedSale['invoice_number'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Sale Date</dt><dd><?= htmlspecialchars($matchedSale['sale_date'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Dealer</dt><dd><a href="/admin/dealers/<?= (int) $matchedSale['dealer_id'] ?>"><?= htmlspecialchars($matchedSale['dealer_name'], ENT_QUOTES, 'UTF-8') ?></a></dd></div>
                <div><dt>Customer</dt><dd><?= htmlspecialchars($matchedSale['customer_name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Customer Mobile</dt><dd><?= htmlspecialchars($matchedSale['customer_mobile'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Device Model</dt><dd><?= htmlspecialchars($matchedSale['device_model'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            </dl>
        <?php endif; ?>
    </section>

    <section class="panel activity-panel">
        <div class="panel-head">
            <h2>Installation Activity</h2>
            <span><?= count($activity) ?> latest</span>
        </div>
        <?php if (empty($activity)): ?>
            <p class="empty-text">No activity has been recorded for this installation.</p>
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
