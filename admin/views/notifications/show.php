<header class="page-head">
    <div>
        <h1><?= $batch ? htmlspecialchars($batch['title'], ENT_QUOTES, 'UTF-8') : 'Notification Batch' ?></h1>
        <p><?= $batch ? htmlspecialchars($batch['batch_id'], ENT_QUOTES, 'UTF-8') : 'Broadcast details unavailable.' ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="/admin/notifications">Back</a>
</header>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<?php if ($batch): ?>
    <section class="detail-grid">
        <article class="panel">
            <div class="panel-head">
                <h2>Broadcast</h2>
                <span><?= htmlspecialchars($batch['channel'] . ' / ' . $batch['notification_type'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <dl class="detail-list">
                <div><dt>Audience</dt><dd><?= htmlspecialchars($batch['audience'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Message</dt><dd class="long-text"><?= nl2br(htmlspecialchars($batch['message'], ENT_QUOTES, 'UTF-8')) ?></dd></div>
                <div><dt>Payload</dt><dd><?= htmlspecialchars($batch['payload'] ?: 'None', ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Queued At</dt><dd><?= htmlspecialchars($batch['queued_at'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Created By</dt><dd><?= htmlspecialchars($batch['created_by'] ?: 'System', ENT_QUOTES, 'UTF-8') ?></dd></div>
            </dl>
        </article>

        <article class="panel">
            <div class="panel-head">
                <h2>Delivery</h2>
                <span><?= number_format((int) $batch['recipient_count']) ?> recipients</span>
            </div>
            <dl class="metric-list">
                <div><dt>Queued</dt><dd><?= number_format((int) $batch['queued_count']) ?></dd></div>
                <div><dt>Sent</dt><dd><?= number_format((int) $batch['sent_count']) ?></dd></div>
                <div><dt>Failed</dt><dd><?= number_format((int) $batch['failed_count']) ?></dd></div>
                <div><dt>Cancelled</dt><dd><?= number_format((int) $batch['cancelled_count']) ?></dd></div>
            </dl>

            <?php if ((int) $batch['queued_count'] > 0): ?>
                <form class="status-form" method="post" action="/admin/notifications/<?= htmlspecialchars($batch['batch_id'], ENT_QUOTES, 'UTF-8') ?>/status">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                    <label>Status Action</label>
                    <select class="form-select" name="status">
                        <option value="sent">Mark queued as sent</option>
                        <option value="failed">Mark queued as failed</option>
                        <option value="cancelled">Cancel queued</option>
                    </select>
                    <label>Failure Reason</label>
                    <input class="form-control" name="error_message" maxlength="500" placeholder="Required only when marking failed">
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Update Status</button>
                    </div>
                </form>
            <?php endif; ?>
        </article>
    </section>

    <section class="panel table-panel compact-grid">
        <div class="panel-head">
            <h2>Recipients</h2>
            <span>Showing up to 200 rows</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recipients as $recipient): ?>
                        <?php
                        $badgeClass = $recipient['status'] === 'sent'
                            ? 'badge-success'
                            : ($recipient['status'] === 'failed' ? 'badge-danger' : 'badge-warning');
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($recipient['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars($recipient['role'], ENT_QUOTES, 'UTF-8') ?> #<?= (int) $recipient['user_id'] ?></small>
                            </td>
                            <td>
                                <span><?= htmlspecialchars($recipient['mobile'], ENT_QUOTES, 'UTF-8') ?></span>
                                <small><?= htmlspecialchars($recipient['email'], ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td>
                                <span class="badge-soft <?= $badgeClass ?>"><?= htmlspecialchars($recipient['status'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (!empty($recipient['error_message'])): ?><small><?= htmlspecialchars($recipient['error_message'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                            </td>
                            <td>
                                <span><?= htmlspecialchars($recipient['updated_at'], ENT_QUOTES, 'UTF-8') ?></span>
                                <small><?= htmlspecialchars($recipient['sent_at'] ?: '', ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recipients)): ?>
                        <tr><td colspan="4" class="text-muted">No recipients found for this batch.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
