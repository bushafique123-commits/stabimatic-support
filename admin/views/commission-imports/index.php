<header class="page-head">
    <div>
        <h1>Commission CSV</h1>
        <p>Upload device serial/model commission amounts for dealer and installer payouts.</p>
    </div>
</header>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<section class="stat-grid commission-stat-grid">
    <article><span>Rows</span><strong><?= number_format((int) ($stats['total'] ?? 0)) ?></strong><small>Device serial/model pairs</small></article>
    <article><span>Dealer Total</span><strong><?= number_format((float) ($stats['dealer_total'] ?? 0), 2) ?></strong><small>PKR</small></article>
    <article><span>Installer Total</span><strong><?= number_format((float) ($stats['installer_total'] ?? 0), 2) ?></strong><small>PKR</small></article>
    <article><span>Latest Upload</span><strong><?= htmlspecialchars($stats['latest_upload'] ?: 'None', ENT_QUOTES, 'UTF-8') ?></strong><small>Last replacement</small></article>
</section>

<form class="panel commission-form" method="post" action="/admin/commission-imports/upload" enctype="multipart/form-data">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
    <div class="panel-head">
        <h2>Upload CSV</h2>
        <span>Columns: Model No, Device Serial No, Dealer Comission, Installer Comission</span>
    </div>
    <input class="form-control" type="file" name="commission_csv" accept=".csv,text/csv" required>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Upload CSV</button>
    </div>
</form>

<form class="filter-bar commission-filter" method="get" action="/admin/commission-imports">
    <input class="form-control" type="search" name="search" placeholder="Search device serial or model" value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <button class="btn btn-primary" type="submit">Filter</button>
</form>

<section class="panel table-panel">
    <div class="panel-head">
        <h2>Imported Rows</h2>
        <span><?= number_format((int) $total) ?> total</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Model No</th>
                    <th>Device Serial No</th>
                    <th>Dealer Comission</th>
                    <th>Installer Comission</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5" class="text-muted">No CSV rows uploaded.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['model_number'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars($row['invoice_number'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>PKR <?= number_format((float) $row['dealer_commission'], 2) ?></td>
                        <td>PKR <?= number_format((float) $row['installer_commission'], 2) ?></td>
                        <td><?= htmlspecialchars($row['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($lastPage > 1): ?>
    <nav class="pagination-bar">
        <?php $query = 'search=' . urlencode($filters['search'] ?? ''); ?>
        <?php if ($page > 1): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/commission-imports?page=<?= $page - 1 ?>&<?= $query ?>">Previous</a>
        <?php endif; ?>
        <span>Page <?= (int) $page ?> of <?= (int) $lastPage ?></span>
        <?php if ($page < $lastPage): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/commission-imports?page=<?= $page + 1 ?>&<?= $query ?>">Next</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
