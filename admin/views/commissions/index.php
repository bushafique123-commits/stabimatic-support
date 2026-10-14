<header class="page-head">
    <div>
        <h1>Commissions</h1>
        <p>Manage fixed rates, generated earnings, approvals, and payout status.</p>
    </div>
</header>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<section class="stat-grid commission-stat-grid">
    <article><span>Pending</span><strong><?= number_format((float) ($stats['pending_amount'] ?? 0), 2) ?></strong><small><?= number_format((int) ($stats['pending_count'] ?? 0)) ?> earnings</small></article>
    <article><span>Approved</span><strong><?= number_format((float) ($stats['approved_amount'] ?? 0), 2) ?></strong><small><?= number_format((int) ($stats['approved_count'] ?? 0)) ?> earnings</small></article>
    <article><span>Paid</span><strong><?= number_format((float) ($stats['paid_amount'] ?? 0), 2) ?></strong><small><?= number_format((int) ($stats['paid_count'] ?? 0)) ?> earnings</small></article>
    <article><span>Pending Payouts</span><strong><?= number_format((float) ($stats['pending_payout_amount'] ?? 0), 2) ?></strong><small><?= number_format((int) ($stats['pending_payout_count'] ?? 0)) ?> payouts</small></article>
</section>

<section class="commission-grid">
    <form class="panel commission-form dealer-generate-form" method="post" action="/admin/commissions/generate-dealer">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="panel-head">
            <h2>Generate Dealer Earnings</h2>
            <span>Select dealer and amount</span>
        </div>

        <label>Dealer</label>
        <select class="form-select js-select2" name="dealer_id" data-placeholder="Search dealer by name or mobile" required>
            <option value=""></option>
            <?php foreach (($dealerRates ?? []) as $dealer): ?>
                <option value="<?= (int) $dealer['id'] ?>"><?= htmlspecialchars($dealer['full_name'] . ' / ' . $dealer['mobile'] . ' / ' . $dealer['email'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['dealer_id'])): ?><small class="field-error"><?= htmlspecialchars($errors['dealer_id'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

        <label>Amount</label>
        <input class="form-control" type="number" step="0.01" min="0.01" max="999999.99" name="dealer_sale_amount" placeholder="PKR 0.00" required>
        <?php if (!empty($errors['dealer_sale_amount'])): ?><small class="field-error"><?= htmlspecialchars($errors['dealer_sale_amount'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Generate Earnings</button>
        </div>
    </form>

    <form class="panel commission-form installer-generate-form" method="post" action="/admin/commissions/generate-installer">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="panel-head">
            <h2>Generate Installer Earnings</h2>
            <span>Select installer and amount</span>
        </div>

        <label>Installer</label>
        <select class="form-select js-select2" name="installer_id" data-placeholder="Search installer by name or mobile" required>
            <option value=""></option>
            <?php foreach (($installers ?? []) as $installer): ?>
                <option value="<?= (int) $installer['id'] ?>"><?= htmlspecialchars($installer['full_name'] . ' / ' . $installer['mobile'] . ' / ' . $installer['email'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['installer_id'])): ?><small class="field-error"><?= htmlspecialchars($errors['installer_id'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

        <label>Amount</label>
        <input class="form-control" type="number" step="0.01" min="0.01" max="999999.99" name="installer_installation_amount" placeholder="PKR 0.00" required>
        <?php if (!empty($errors['installer_installation_amount'])): ?><small class="field-error"><?= htmlspecialchars($errors['installer_installation_amount'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Generate Earnings</button>
        </div>
    </form>

    <article class="panel">
        <div class="panel-head">
            <h2>Operations</h2>
        </div>
        <div class="action-row commission-actions">
            <form method="post" action="/admin/commissions/approve-pending">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn btn-primary" type="submit">Approve Pending</button>
            </form>
            <form method="post" action="/admin/commissions/create-payouts">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn btn-outline-primary" type="submit">Create Payouts</button>
            </form>
        </div>
        <dl class="metric-list">
            <div><dt>Dealer Records</dt><dd><?= number_format(count($dealerRates ?? [])) ?></dd></div>
            <div><dt>Installer Records</dt><dd><?= number_format(count($installers ?? [])) ?></dd></div>
            <div><dt>Cancelled Earnings</dt><dd><?= number_format((float) ($stats['cancelled_amount'] ?? 0), 2) ?></dd></div>
        </dl>
    </article>
</section>

<form class="filter-bar commission-filter" method="get" action="/admin/commissions">
    <input class="form-control" type="search" name="search" placeholder="Search user, mobile, invoice, serial" value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <select class="form-select" name="role">
        <option value="">All Roles</option>
        <option value="dealer" <?= ($filters['role'] ?? '') === 'dealer' ? 'selected' : '' ?>>Dealer</option>
        <option value="installer" <?= ($filters['role'] ?? '') === 'installer' ? 'selected' : '' ?>>Installer</option>
    </select>
    <select class="form-select" name="status">
        <option value="">All Statuses</option>
        <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'paid' => 'Paid', 'cancelled' => 'Cancelled'] as $value => $label): ?>
            <option value="<?= $value ?>" <?= ($filters['status'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>
    <input class="form-control" type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <input class="form-control" type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <button class="btn btn-primary" type="submit">Filter</button>
</form>

<section class="panel table-panel">
    <div class="panel-head">
        <h2>Earnings</h2>
        <span><?= number_format((int) $total) ?> total</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Source</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Earned</th>
                    <th>Payout</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($earnings)): ?>
                    <tr><td colspan="6" class="text-muted">No commission earnings found.</td></tr>
                <?php endif; ?>
                <?php foreach ($earnings as $earning): ?>
                    <?php
                    $badgeClass = $earning['status'] === 'paid'
                        ? 'badge-success'
                        : ($earning['status'] === 'cancelled' ? 'badge-danger' : 'badge-warning');
                    $sourceUrl = $earning['source_type'] === 'sale'
                        ? '/admin/sales/' . (int) $earning['source_id']
                        : ($earning['source_type'] === 'installation' ? '/admin/installations/' . (int) $earning['source_id'] : '');
                    ?>
                    <tr>
                        <td>
                            <a href="<?= $earning['role'] === 'dealer' ? '/admin/dealers/' . (int) $earning['user_id'] : '/admin/installers/' . (int) $earning['user_id'] ?>"><?= htmlspecialchars($earning['full_name'], ENT_QUOTES, 'UTF-8') ?></a>
                            <small><?= htmlspecialchars($earning['role'] . ' / ' . $earning['mobile'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td>
                            <?php if ($sourceUrl !== ''): ?>
                                <a href="<?= $sourceUrl ?>"><?= htmlspecialchars($earning['source_label'] ?: '#' . (int) $earning['source_id'], ENT_QUOTES, 'UTF-8') ?></a>
                            <?php else: ?>
                                <span><?= htmlspecialchars($earning['source_label'] ?: 'Manual earning', ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <small><?= htmlspecialchars($earning['source_type'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($earning['currency'], ENT_QUOTES, 'UTF-8') ?> <?= number_format((float) $earning['amount'], 2) ?></strong>
                        </td>
                        <td><span class="badge-soft <?= $badgeClass ?>"><?= htmlspecialchars($earning['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars($earning['earned_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $earning['payout_id'] ? '#' . (int) $earning['payout_id'] : 'Not assigned' ?></td>
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
            . '&role=' . urlencode($filters['role'] ?? '')
            . '&status=' . urlencode($filters['status'] ?? '')
            . '&date_from=' . urlencode($filters['date_from'] ?? '')
            . '&date_to=' . urlencode($filters['date_to'] ?? '');
        ?>
        <?php if ($page > 1): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/commissions?page=<?= $page - 1 ?>&<?= $query ?>">Previous</a>
        <?php endif; ?>
        <span>Page <?= (int) $page ?> of <?= (int) $lastPage ?></span>
        <?php if ($page < $lastPage): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/commissions?page=<?= $page + 1 ?>&<?= $query ?>">Next</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>

<section class="panel table-panel activity-panel">
    <div class="panel-head">
        <h2>Recent Payouts</h2>
        <span><?= count($payouts) ?> latest</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Reference</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payouts)): ?>
                    <tr><td colspan="5" class="text-muted">No payouts found.</td></tr>
                <?php endif; ?>
                <?php foreach ($payouts as $payout): ?>
                    <tr>
                        <td>
                            <a href="<?= $payout['role'] === 'dealer' ? '/admin/dealers/' . (int) $payout['user_id'] : '/admin/installers/' . (int) $payout['user_id'] ?>"><?= htmlspecialchars($payout['full_name'], ENT_QUOTES, 'UTF-8') ?></a>
                            <small><?= htmlspecialchars($payout['role'] . ' / ' . $payout['mobile'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td>
                            <strong>PKR <?= number_format((float) $payout['amount'], 2) ?></strong>
                            <small><?= number_format((int) $payout['earning_count']) ?> earnings</small>
                        </td>
                        <td>
                            <span class="badge-soft <?= $payout['status'] === 'paid' ? 'badge-success' : ($payout['status'] === 'cancelled' ? 'badge-danger' : 'badge-warning') ?>"><?= htmlspecialchars($payout['status'], ENT_QUOTES, 'UTF-8') ?></span>
                            <small><?= htmlspecialchars($payout['paid_at'] ?: $payout['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td>
                            <span><?= htmlspecialchars($payout['payment_reference'] ?: 'No reference', ENT_QUOTES, 'UTF-8') ?></span>
                            <small><?= htmlspecialchars($payout['notes'] ?: '', ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td>
                            <?php if ($payout['status'] === 'pending'): ?>
                                <form class="payout-status-form" method="post" action="/admin/commissions/payouts/<?= (int) $payout['id'] ?>/status">
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <select class="form-select form-select-sm" name="status">
                                        <option value="paid">Paid</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <input class="form-control form-control-sm" name="payment_reference" maxlength="120" placeholder="Reference">
                                    <input class="form-control form-control-sm" name="notes" maxlength="1000" placeholder="Notes">
                                    <button class="btn btn-outline-primary btn-sm" type="submit">Update</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
