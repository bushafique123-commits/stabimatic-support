<header class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p>STABIMATIC administration overview</p>
    </div>
</header>

<section class="stat-grid">
    <article>
        <span>Total Dealers</span>
        <strong><?= number_format((int) $stats['dealers']) ?></strong>
        <small><?= number_format((int) $stats['active_dealers']) ?> active, <?= number_format((int) $stats['pending_dealers']) ?> pending</small>
    </article>
    <article>
        <span>Total Installers</span>
        <strong><?= number_format((int) $stats['installers']) ?></strong>
        <small><?= number_format((int) $stats['active_installers']) ?> active, <?= number_format((int) $stats['pending_installers']) ?> pending</small>
    </article>
    <article>
        <span>Total Sales</span>
        <strong><?= number_format((int) $stats['sales']) ?></strong>
        <small><?= number_format((int) $stats['sales_today']) ?> today</small>
    </article>
    <article>
        <span>Total Installations</span>
        <strong><?= number_format((int) $stats['installations']) ?></strong>
        <small><?= number_format((int) $stats['installations_today']) ?> today</small>
    </article>
</section>

<section class="dashboard-grid">
    <article class="panel">
        <div class="panel-head">
            <h2>Serial Matching</h2>
            <span><?= number_format((int) $stats['matched_installations']) ?> matched</span>
        </div>
        <div class="match-meter">
            <?php
            $totalInstallations = max((int) $stats['installations'], 1);
            $matchedPercent = min(100, round(((int) $stats['matched_installations'] / $totalInstallations) * 100));
            ?>
            <div style="width: <?= (int) $matchedPercent ?>%"></div>
        </div>
        <dl class="metric-list">
            <div><dt>Matched Installations</dt><dd><?= number_format((int) $stats['matched_installations']) ?></dd></div>
            <div><dt>Unmatched Installations</dt><dd><?= number_format((int) $stats['unmatched_installations']) ?></dd></div>
            <div><dt>Match Rate</dt><dd><?= (int) $matchedPercent ?>%</dd></div>
        </dl>
    </article>

    <article class="panel">
        <div class="panel-head">
            <h2>System Status</h2>
        </div>
        <section class="status-list">
            <?php foreach ($statusItems as $item): ?>
                <div class="status-item status-<?= htmlspecialchars($item['tone'], ENT_QUOTES, 'UTF-8') ?>">
                    <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <strong><?= htmlspecialchars($item['value'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            <?php endforeach; ?>
        </section>
    </article>
</section>

<section class="panel activity-panel">
    <div class="panel-head">
        <h2>Recent Activity</h2>
        <span><?= count($recentActivity) ?> latest</span>
    </div>
    <?php if (empty($recentActivity)): ?>
        <p class="empty-text">No activity has been recorded yet.</p>
    <?php else: ?>
        <div class="activity-list">
            <?php foreach ($recentActivity as $activity): ?>
                <article>
                    <div>
                        <strong><?= htmlspecialchars($activity['action'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars($activity['description'] ?: $activity['module'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <small><?= htmlspecialchars($activity['user_type'], ENT_QUOTES, 'UTF-8') ?> #<?= (int) $activity['user_id'] ?> - <?= htmlspecialchars($activity['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
