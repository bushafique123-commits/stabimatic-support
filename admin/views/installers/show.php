<header class="page-head">
    <div>
        <h1><?= $installer ? htmlspecialchars($installer['full_name'], ENT_QUOTES, 'UTF-8') : 'Installer' ?></h1>
        <p><?= $installer ? htmlspecialchars($installer['email'], ENT_QUOTES, 'UTF-8') : 'Installer record unavailable' ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="/admin/installers">Back</a>
</header>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($flashError): ?><div class="alert alert-danger"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<?php if ($installer): ?>
    <section class="detail-grid">
        <article class="panel">
            <div class="panel-head">
                <h2>Account</h2>
                <span>#<?= (int) $installer['id'] ?></span>
            </div>
            <dl class="detail-list">
                <div><dt>Mobile</dt><dd><?= htmlspecialchars($installer['mobile'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>CNIC</dt><dd><?= htmlspecialchars($installer['cnic'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Status</dt><dd><?= (int) $installer['is_active'] ? 'Active' : 'Inactive' ?></dd></div>
                <div><dt>Verification</dt><dd><?= (int) $installer['is_verified'] ? 'Verified' : 'Pending' ?></dd></div>
                <div><dt>Mobile Verified</dt><dd><?= htmlspecialchars($installer['mobile_verified_at'] ?? 'No', ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Email Verified</dt><dd><?= htmlspecialchars($installer['email_verified_at'] ?? 'No', ENT_QUOTES, 'UTF-8') ?></dd></div>
            </dl>
        </article>

        <article class="panel">
            <div class="panel-head">
                <h2>Bank</h2>
            </div>
            <dl class="detail-list">
                <div><dt>Bank</dt><dd><?= htmlspecialchars($installer['bank_name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Account Title</dt><dd><?= htmlspecialchars($installer['bank_account_title'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Account Number</dt><dd><?= htmlspecialchars($installer['bank_account_number'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>IBAN</dt><dd><?= htmlspecialchars($installer['iban'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            </dl>
        </article>
    </section>

    <section class="stat-grid compact-grid">
        <article><span>Total Installations</span><strong><?= number_format((int) $stats['installations']) ?></strong></article>
        <article><span>Installations Today</span><strong><?= number_format((int) $stats['installations_today']) ?></strong></article>
        <article><span>Matched</span><strong><?= number_format((int) $stats['matched_installations']) ?></strong></article>
        <article><span>Unmatched</span><strong><?= number_format((int) $stats['unmatched_installations']) ?></strong></article>
    </section>

    <section class="panel action-panel">
        <div class="panel-head">
            <h2>Controls</h2>
        </div>
        <div class="action-row">
            <form method="post" action="/admin/installers/<?= (int) $installer['id'] ?>/<?= (int) $installer['is_active'] ? 'deactivate' : 'activate' ?>">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn <?= (int) $installer['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit">
                    <?= (int) $installer['is_active'] ? 'Deactivate' : 'Activate' ?>
                </button>
            </form>
            <form method="post" action="/admin/installers/<?= (int) $installer['id'] ?>/<?= (int) $installer['is_verified'] ? 'unverify' : 'verify' ?>">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn <?= (int) $installer['is_verified'] ? 'btn-outline-warning' : 'btn-outline-primary' ?>" type="submit">
                    <?= (int) $installer['is_verified'] ? 'Mark Pending' : 'Mark Verified' ?>
                </button>
            </form>
        </div>
    </section>

    <section class="panel table-panel">
        <div class="panel-head">
            <h2>Recent Installations</h2>
            <span><?= count($recentInstallations) ?> latest</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Serial</th>
                        <th>Installed At</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Match</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentInstallations)): ?>
                        <tr><td colspan="5" class="text-muted">No installations found for this installer.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentInstallations as $installation): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($installation['invoice_number'] ?: $installation['serial_number'], ENT_QUOTES, 'UTF-8') ?>
                                <small><?= htmlspecialchars($installation['device_model'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td><?= htmlspecialchars($installation['installed_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <strong><?= htmlspecialchars($installation['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars($installation['customer_mobile'], ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td><?= htmlspecialchars($installation['customer_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($installation['matched_invoice']): ?>
                                    <span class="badge-soft badge-success"><?= htmlspecialchars($installation['matched_invoice'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span class="badge-soft badge-warning">Unmatched</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
