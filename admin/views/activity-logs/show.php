<header class="page-head">
    <div>
        <h1><?= $activity ? htmlspecialchars($activity['action'], ENT_QUOTES, 'UTF-8') : 'Activity Log' ?></h1>
        <p><?= $activity ? htmlspecialchars($activity['created_at'], ENT_QUOTES, 'UTF-8') : 'Activity record unavailable' ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="/admin/activity-logs">Back</a>
</header>

<?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<?php if ($activity): ?>
    <section class="detail-grid">
        <article class="panel">
            <div class="panel-head">
                <h2>Event</h2>
                <span>#<?= (int) $activity['id'] ?></span>
            </div>
            <dl class="detail-list">
                <div><dt>Action</dt><dd><?= htmlspecialchars($activity['action'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Module</dt><dd><?= htmlspecialchars($activity['module'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div>
                    <dt>Record</dt>
                    <dd>
                        <?php if ($activity['record_id']): ?>
                            <?php if ($activity['record_url']): ?>
                                <a href="<?= htmlspecialchars($activity['record_url'], ENT_QUOTES, 'UTF-8') ?>">Record #<?= (int) $activity['record_id'] ?></a>
                            <?php else: ?>
                                Record #<?= (int) $activity['record_id'] ?>
                            <?php endif; ?>
                        <?php else: ?>
                            No record
                        <?php endif; ?>
                    </dd>
                </div>
                <div><dt>Description</dt><dd><?= nl2br(htmlspecialchars($activity['description'] ?: '', ENT_QUOTES, 'UTF-8')) ?></dd></div>
                <div><dt>Created At</dt><dd><?= htmlspecialchars($activity['created_at'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            </dl>
        </article>

        <article class="panel">
            <div class="panel-head">
                <h2>Actor</h2>
                <span><?= htmlspecialchars($activity['user_type'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <dl class="detail-list">
                <div><dt>Name</dt><dd><?= htmlspecialchars($activity['actor_label'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>User ID</dt><dd>#<?= (int) $activity['user_id'] ?></dd></div>
                <div><dt>Email</dt><dd><?= htmlspecialchars($activity['actor_email'] ?: '', ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Mobile</dt><dd><?= htmlspecialchars($activity['actor_mobile'] ?: '', ENT_QUOTES, 'UTF-8') ?></dd></div>
            </dl>
        </article>
    </section>

    <section class="panel action-panel">
        <div class="panel-head">
            <h2>Request Context</h2>
        </div>
        <dl class="detail-list">
            <div><dt>IP Address</dt><dd><?= htmlspecialchars($activity['ip_address'] ?: 'Unknown', ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>User Agent</dt><dd class="long-text"><?= htmlspecialchars($activity['user_agent'] ?: '', ENT_QUOTES, 'UTF-8') ?></dd></div>
        </dl>
    </section>
<?php endif; ?>
