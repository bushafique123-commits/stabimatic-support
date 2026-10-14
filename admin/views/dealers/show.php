<header class="page-head">
    <div>
        <h1><?= $dealer ? htmlspecialchars($dealer['full_name'], ENT_QUOTES, 'UTF-8') : 'Dealer' ?></h1>
        <p><?= $dealer ? htmlspecialchars($dealer['email'], ENT_QUOTES, 'UTF-8') : 'Dealer record unavailable' ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="/admin/dealers">Back</a>
</header>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($flashError): ?><div class="alert alert-danger"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<?php if ($dealer): ?>
    <section class="detail-grid">
        <article class="panel">
            <div class="panel-head">
                <h2>Account</h2>
                <span>#<?= (int) $dealer['id'] ?></span>
            </div>
            <dl class="detail-list">
                <div><dt>Mobile</dt><dd><?= htmlspecialchars($dealer['mobile'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>CNIC</dt><dd><?= htmlspecialchars($dealer['cnic'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Status</dt><dd><?= (int) $dealer['is_active'] ? 'Active' : 'Inactive' ?></dd></div>
                <div><dt>Verification</dt><dd><?= (int) $dealer['is_verified'] ? 'Verified' : 'Pending' ?></dd></div>
                <div><dt>Mobile Verified</dt><dd><?= htmlspecialchars($dealer['mobile_verified_at'] ?? 'No', ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Email Verified</dt><dd><?= htmlspecialchars($dealer['email_verified_at'] ?? 'No', ENT_QUOTES, 'UTF-8') ?></dd></div>
            </dl>
        </article>

        <article class="panel">
            <div class="panel-head">
                <h2>Bank</h2>
            </div>
            <dl class="detail-list">
                <div><dt>Bank</dt><dd><?= htmlspecialchars($dealer['bank_name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Account Title</dt><dd><?= htmlspecialchars($dealer['bank_account_title'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Account Number</dt><dd><?= htmlspecialchars($dealer['bank_account_number'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>IBAN</dt><dd><?= htmlspecialchars($dealer['iban'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            </dl>
        </article>
    </section>

    <section class="stat-grid compact-grid">
        <article><span>Total Sales</span><strong><?= number_format((int) $stats['sales']) ?></strong></article>
        <article><span>Sales Today</span><strong><?= number_format((int) $stats['sales_today']) ?></strong></article>
        <article><span>Matched Installations</span><strong><?= number_format((int) $stats['installations_matched']) ?></strong></article>
    </section>

    <section class="panel action-panel">
        <div class="panel-head">
            <h2>Controls</h2>
        </div>
        <div class="action-row">
            <form method="post" action="/admin/dealers/<?= (int) $dealer['id'] ?>/<?= (int) $dealer['is_active'] ? 'deactivate' : 'activate' ?>">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn <?= (int) $dealer['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit">
                    <?= (int) $dealer['is_active'] ? 'Deactivate' : 'Activate' ?>
                </button>
            </form>
            <form method="post" action="/admin/dealers/<?= (int) $dealer['id'] ?>/<?= (int) $dealer['is_verified'] ? 'unverify' : 'verify' ?>">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn <?= (int) $dealer['is_verified'] ? 'btn-outline-warning' : 'btn-outline-primary' ?>" type="submit">
                    <?= (int) $dealer['is_verified'] ? 'Mark Pending' : 'Mark Verified' ?>
                </button>
            </form>
        </div>
    </section>

    <section class="panel table-panel">
        <div class="panel-head">
            <h2>Recent Sales</h2>
            <span><?= count($recentSales) ?> latest</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Device</th>
                        <th>Serial</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentSales)): ?>
                        <tr><td colspan="5" class="text-muted">No sales found for this dealer.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentSales as $sale): ?>
                        <tr>
                            <td><?= htmlspecialchars($sale['invoice_number'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($sale['sale_date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <strong><?= htmlspecialchars($sale['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars($sale['customer_mobile'], ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td><?= htmlspecialchars($sale['device_model'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($sale['device_serial'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
