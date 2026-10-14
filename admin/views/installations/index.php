<header class="page-head">
    <div>
        <h1>Installations</h1>
        <p>Inspect Installer submissions, device serial/model matches, and installation images.</p>
    </div>
</header>

<?php if ($success ?? ''): ?><div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<form class="filter-bar sales-filter" method="get" action="/admin/installations">
    <input class="form-control" type="search" name="search" placeholder="Search customer, invoice, model, installer" value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <input class="form-control" type="number" min="1" name="installer_id" placeholder="Installer ID" value="<?= (int) ($filters['installer_id'] ?? 0) ?: '' ?>">
    <input class="form-control" type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <input class="form-control" type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <select class="form-select" name="match">
        <option value="">All Match</option>
        <option value="matched" <?= ($filters['match'] ?? '') === 'matched' ? 'selected' : '' ?>>Matched</option>
        <option value="unmatched" <?= ($filters['match'] ?? '') === 'unmatched' ? 'selected' : '' ?>>Unmatched</option>
    </select>
    <button class="btn btn-primary" type="submit">Filter</button>
</form>

<section class="panel table-panel">
    <div class="panel-head">
        <h2>Installation Records</h2>
        <span><?= number_format((int) $total) ?> total</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Invoice / Model</th>
                    <th>Installed At</th>
                    <th>Installer</th>
                    <th>Customer</th>
                    <th>Match</th>
                    <th>Image</th>
                    <th>Payout</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($installations)): ?>
                    <tr><td colspan="8" class="text-muted">No installations found.</td></tr>
                <?php endif; ?>
                <?php foreach ($installations as $installation): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($installation['installation_invoice_number'] ?: $installation['serial_number'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= htmlspecialchars($installation['installation_device_model'] ?: '', ENT_QUOTES, 'UTF-8') ?></small>
                            <small>#<?= (int) $installation['id'] ?></small>
                        </td>
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
                            <?php if ($installation['sale_id']): ?>
                                <a class="badge-soft badge-success" href="/admin/sales/<?= (int) $installation['sale_id'] ?>"><?= htmlspecialchars($installation['invoice_number'], ENT_QUOTES, 'UTF-8') ?></a>
                                <small><?= htmlspecialchars($installation['dealer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                            <?php else: ?>
                                <span class="badge-soft badge-warning">Unmatched</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($installation['installation_picture']): ?>
                                <a href="/api/<?= htmlspecialchars(ltrim($installation['installation_picture'], '/'), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Open</a>
                            <?php else: ?>
                                <span class="text-muted">None</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($installation['installer_payout_status'] ?? '') === 'paid'): ?>
                                <span class="badge-soft badge-success">Paid</span>
                                <small>PKR <?= number_format((float) ($installation['installer_paid_amount'] ?? 0), 2) ?></small>
                            <?php else: ?>
                                <details class="inline-pay">
                                    <summary class="btn btn-outline-success btn-sm">Paid</summary>
                                    <form method="post" action="/admin/commissions/pay-source">
                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="source_type" value="installation">
                                        <input type="hidden" name="source_id" value="<?= (int) $installation['id'] ?>">
                                        <input type="hidden" name="_redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/admin/installations', ENT_QUOTES, 'UTF-8') ?>">
                                        <input class="form-control form-control-sm" type="number" step="0.01" min="0.01" max="999999.99" name="amount" value="<?= htmlspecialchars(number_format((float) ($installation['installer_commission'] ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <button class="btn btn-primary btn-sm" type="submit">Submit</button>
                                    </form>
                                </details>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="/admin/installations/<?= (int) $installation['id'] ?>">Open</a></td>
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
            . '&installer_id=' . urlencode((string) ($filters['installer_id'] ?? ''))
            . '&date_from=' . urlencode($filters['date_from'] ?? '')
            . '&date_to=' . urlencode($filters['date_to'] ?? '')
            . '&match=' . urlencode($filters['match'] ?? '');
        ?>
        <?php if ($page > 1): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/installations?page=<?= $page - 1 ?>&<?= $query ?>">Previous</a>
        <?php endif; ?>
        <span>Page <?= (int) $page ?> of <?= (int) $lastPage ?></span>
        <?php if ($page < $lastPage): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/installations?page=<?= $page + 1 ?>&<?= $query ?>">Next</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
