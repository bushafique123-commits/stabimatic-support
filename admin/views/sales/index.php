<header class="page-head">
    <div>
        <h1>Sales</h1>
        <p>Audit Dealer sales and device serial/model installation matching.</p>
    </div>
</header>

<?php if ($success ?? ''): ?><div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<form class="filter-bar sales-filter" method="get" action="/admin/sales">
    <input class="form-control" type="search" name="search" placeholder="Search invoice, model, customer, mobile, dealer" value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <input class="form-control" type="number" min="1" name="dealer_id" placeholder="Dealer ID" value="<?= (int) ($filters['dealer_id'] ?? 0) ?: '' ?>">
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
        <h2>Sale Records</h2>
        <span><?= number_format((int) $total) ?> total</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Sale Date</th>
                    <th>Dealer</th>
                    <th>Customer</th>
                    <th>Device</th>
                    <th>Match</th>
                    <th>Payout</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales)): ?>
                    <tr><td colspan="8" class="text-muted">No sales found.</td></tr>
                <?php endif; ?>
                <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($sale['invoice_number'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small>#<?= (int) $sale['id'] ?></small>
                        </td>
                        <td><?= htmlspecialchars($sale['sale_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="/admin/dealers/<?= (int) $sale['dealer_id'] ?>"><?= htmlspecialchars($sale['dealer_name'], ENT_QUOTES, 'UTF-8') ?></a>
                            <small><?= htmlspecialchars($sale['dealer_mobile'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($sale['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= htmlspecialchars($sale['customer_mobile'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td>
                            <span><?= htmlspecialchars($sale['device_model'], ENT_QUOTES, 'UTF-8') ?></span>
                            <small><?= htmlspecialchars($sale['device_serial'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td>
                            <?php if ((int) $sale['installations_count'] > 0): ?>
                                <span class="badge-soft badge-success"><?= number_format((int) $sale['installations_count']) ?> matched</span>
                                <small><?= htmlspecialchars($sale['latest_installation_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                            <?php else: ?>
                                <span class="badge-soft badge-warning">Unmatched</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($sale['dealer_payout_status'] ?? '') === 'paid'): ?>
                                <span class="badge-soft badge-success">Paid</span>
                                <small>PKR <?= number_format((float) ($sale['dealer_paid_amount'] ?? 0), 2) ?></small>
                            <?php else: ?>
                                <details class="inline-pay">
                                    <summary class="btn btn-outline-success btn-sm">Paid</summary>
                                    <form method="post" action="/admin/commissions/pay-source">
                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="source_type" value="sale">
                                        <input type="hidden" name="source_id" value="<?= (int) $sale['id'] ?>">
                                        <input type="hidden" name="_redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/admin/sales', ENT_QUOTES, 'UTF-8') ?>">
                                        <input class="form-control form-control-sm" type="number" step="0.01" min="0.01" max="999999.99" name="amount" value="<?= htmlspecialchars(number_format((float) ($sale['dealer_commission'] ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <button class="btn btn-primary btn-sm" type="submit">Submit</button>
                                    </form>
                                </details>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="/admin/sales/<?= (int) $sale['id'] ?>">Open</a></td>
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
            . '&dealer_id=' . urlencode((string) ($filters['dealer_id'] ?? ''))
            . '&date_from=' . urlencode($filters['date_from'] ?? '')
            . '&date_to=' . urlencode($filters['date_to'] ?? '')
            . '&match=' . urlencode($filters['match'] ?? '');
        ?>
        <?php if ($page > 1): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/sales?page=<?= $page - 1 ?>&<?= $query ?>">Previous</a>
        <?php endif; ?>
        <span>Page <?= (int) $page ?> of <?= (int) $lastPage ?></span>
        <?php if ($page < $lastPage): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/sales?page=<?= $page + 1 ?>&<?= $query ?>">Next</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
