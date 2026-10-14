<header class="page-head">
    <div>
        <h1>Reports</h1>
        <p>Review sales, installations, serial matching, and activity trends.</p>
    </div>
</header>

<?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<form class="filter-bar report-filter" method="get" action="/admin/reports">
    <input class="form-control" type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <input class="form-control" type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <button class="btn btn-primary" type="submit">Apply</button>
</form>

<section class="stat-grid">
    <article><span>Sales</span><strong><?= number_format((int) $summary['sales']) ?></strong><small>Selected period</small></article>
    <article><span>Installations</span><strong><?= number_format((int) $summary['installations']) ?></strong><small>Selected period</small></article>
    <article><span>Matched</span><strong><?= number_format((int) $summary['matched_installations']) ?></strong><small><?= (int) $report['match_rate'] ?>% match rate</small></article>
    <article><span>Unmatched</span><strong><?= number_format((int) $summary['unmatched_installations']) ?></strong><small>Needs review</small></article>
</section>

<section class="dashboard-grid">
    <article class="panel">
        <div class="panel-head">
            <h2>Serial Match Rate</h2>
            <span><?= (int) $report['match_rate'] ?>%</span>
        </div>
        <div class="match-meter"><div style="width: <?= (int) $report['match_rate'] ?>%"></div></div>
        <dl class="metric-list">
            <div><dt>Matched Installations</dt><dd><?= number_format((int) $summary['matched_installations']) ?></dd></div>
            <div><dt>Unmatched Installations</dt><dd><?= number_format((int) $summary['unmatched_installations']) ?></dd></div>
            <div><dt>Total Installations</dt><dd><?= number_format((int) $summary['installations']) ?></dd></div>
        </dl>
    </article>

    <article class="panel">
        <div class="panel-head">
            <h2>Activity By Role</h2>
        </div>
        <dl class="metric-list">
            <div><dt>Dealer Activity</dt><dd><?= number_format((int) $summary['dealer_activity']) ?></dd></div>
            <div><dt>Installer Activity</dt><dd><?= number_format((int) $summary['installer_activity']) ?></dd></div>
            <div><dt>Admin Activity</dt><dd><?= number_format((int) $summary['admin_activity']) ?></dd></div>
        </dl>
    </article>
</section>

<section class="report-grid">
    <article class="panel table-panel">
        <div class="panel-head">
            <h2>Daily Sales</h2>
            <span><?= count($report['daily_sales']) ?> rows</span>
        </div>
        <table class="table align-middle">
            <thead><tr><th>Date</th><th>Total</th></tr></thead>
            <tbody>
                <?php if (empty($report['daily_sales'])): ?><tr><td colspan="2" class="text-muted">No sales found.</td></tr><?php endif; ?>
                <?php foreach ($report['daily_sales'] as $row): ?>
                    <tr><td><?= htmlspecialchars($row['report_date'], ENT_QUOTES, 'UTF-8') ?></td><td><?= number_format((int) $row['total']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </article>

    <article class="panel table-panel">
        <div class="panel-head">
            <h2>Daily Installations</h2>
            <span><?= count($report['daily_installations']) ?> rows</span>
        </div>
        <table class="table align-middle">
            <thead><tr><th>Date</th><th>Total</th></tr></thead>
            <tbody>
                <?php if (empty($report['daily_installations'])): ?><tr><td colspan="2" class="text-muted">No installations found.</td></tr><?php endif; ?>
                <?php foreach ($report['daily_installations'] as $row): ?>
                    <tr><td><?= htmlspecialchars($row['report_date'], ENT_QUOTES, 'UTF-8') ?></td><td><?= number_format((int) $row['total']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </article>
</section>

<section class="report-grid">
    <article class="panel table-panel">
        <div class="panel-head">
            <h2>Top Dealers</h2>
            <span><?= count($report['top_dealers']) ?> rows</span>
        </div>
        <table class="table align-middle">
            <thead><tr><th>Dealer</th><th>Sales</th></tr></thead>
            <tbody>
                <?php if (empty($report['top_dealers'])): ?><tr><td colspan="2" class="text-muted">No Dealer sales found.</td></tr><?php endif; ?>
                <?php foreach ($report['top_dealers'] as $dealer): ?>
                    <tr>
                        <td><a href="/admin/dealers/<?= (int) $dealer['id'] ?>"><?= htmlspecialchars($dealer['full_name'], ENT_QUOTES, 'UTF-8') ?></a><small><?= htmlspecialchars($dealer['mobile'], ENT_QUOTES, 'UTF-8') ?></small></td>
                        <td><?= number_format((int) $dealer['sales_count']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </article>

    <article class="panel table-panel">
        <div class="panel-head">
            <h2>Top Installers</h2>
            <span><?= count($report['top_installers']) ?> rows</span>
        </div>
        <table class="table align-middle">
            <thead><tr><th>Installer</th><th>Installations</th></tr></thead>
            <tbody>
                <?php if (empty($report['top_installers'])): ?><tr><td colspan="2" class="text-muted">No Installer installations found.</td></tr><?php endif; ?>
                <?php foreach ($report['top_installers'] as $installer): ?>
                    <tr>
                        <td><a href="/admin/installers/<?= (int) $installer['id'] ?>"><?= htmlspecialchars($installer['full_name'], ENT_QUOTES, 'UTF-8') ?></a><small><?= htmlspecialchars($installer['mobile'], ENT_QUOTES, 'UTF-8') ?></small></td>
                        <td><?= number_format((int) $installer['installations_count']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </article>
</section>

<section class="panel table-panel activity-panel">
    <div class="panel-head">
        <h2>Unmatched Installations</h2>
        <span><?= count($report['unmatched_installations']) ?> latest</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Invoice / Model</th><th>Installed At</th><th>Installer</th><th>Customer</th></tr></thead>
            <tbody>
                <?php if (empty($report['unmatched_installations'])): ?><tr><td colspan="4" class="text-muted">No unmatched installations found.</td></tr><?php endif; ?>
                <?php foreach ($report['unmatched_installations'] as $installation): ?>
                    <tr>
                        <td>
                            <a href="/admin/installations/<?= (int) $installation['id'] ?>"><?= htmlspecialchars($installation['invoice_number'] ?: $installation['serial_number'], ENT_QUOTES, 'UTF-8') ?></a>
                            <small><?= htmlspecialchars($installation['device_model'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td><?= htmlspecialchars($installation['installed_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($installation['installer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><strong><?= htmlspecialchars($installation['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($installation['customer_mobile'], ENT_QUOTES, 'UTF-8') ?></small></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel table-panel activity-panel">
    <div class="panel-head">
        <h2>Activity Breakdown</h2>
        <span><?= count($report['activity_breakdown']) ?> rows</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Role</th><th>Module</th><th>Action</th><th>Total</th></tr></thead>
            <tbody>
                <?php if (empty($report['activity_breakdown'])): ?><tr><td colspan="4" class="text-muted">No activity found.</td></tr><?php endif; ?>
                <?php foreach ($report['activity_breakdown'] as $activity): ?>
                    <tr>
                        <td><?= htmlspecialchars($activity['user_type'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($activity['module'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($activity['action'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((int) $activity['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
