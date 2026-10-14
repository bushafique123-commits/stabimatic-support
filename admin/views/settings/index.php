<header class="page-head">
    <div>
        <h1>Settings</h1>
        <p>Manage public app settings, OTP limits, uploads, and serial validation.</p>
    </div>
</header>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<form class="settings-form" method="post" action="/admin/settings">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

    <section class="settings-grid">
        <article class="panel">
            <div class="panel-head">
                <h2>Company</h2>
            </div>
            <label>Company Name</label>
            <input class="form-control" name="company_name" maxlength="150" value="<?= htmlspecialchars($settings['company_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty($errors['company_name'])): ?><small class="field-error"><?= htmlspecialchars($errors['company_name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

            <label>Company Logo Path</label>
            <input class="form-control" name="company_logo" maxlength="255" value="<?= htmlspecialchars($settings['company_logo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['company_logo'])): ?><small class="field-error"><?= htmlspecialchars($errors['company_logo'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

            <label>Support Phone</label>
            <input class="form-control" name="support_phone" maxlength="30" value="<?= htmlspecialchars($settings['support_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['support_phone'])): ?><small class="field-error"><?= htmlspecialchars($errors['support_phone'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

            <label>Support Email</label>
            <input class="form-control" type="email" name="support_email" maxlength="190" value="<?= htmlspecialchars($settings['support_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['support_email'])): ?><small class="field-error"><?= htmlspecialchars($errors['support_email'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </article>

        <article class="panel">
            <div class="panel-head">
                <h2>Rules</h2>
            </div>
            <label>OTP Expiry Minutes</label>
            <input class="form-control" type="number" name="otp_expiry_minutes" min="1" max="60" value="<?= (int) ($settings['otp_expiry_minutes'] ?? 10) ?>" required>
            <?php if (!empty($errors['otp_expiry_minutes'])): ?><small class="field-error"><?= htmlspecialchars($errors['otp_expiry_minutes'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

            <label>OTP Resend Seconds</label>
            <input class="form-control" type="number" name="otp_resend_seconds" min="15" max="600" value="<?= (int) ($settings['otp_resend_seconds'] ?? 60) ?>" required>
            <?php if (!empty($errors['otp_resend_seconds'])): ?><small class="field-error"><?= htmlspecialchars($errors['otp_resend_seconds'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

            <label>Maximum OTP Attempts</label>
            <input class="form-control" type="number" name="maximum_otp_attempts" min="1" max="10" value="<?= (int) ($settings['maximum_otp_attempts'] ?? 5) ?>" required>
            <?php if (!empty($errors['maximum_otp_attempts'])): ?><small class="field-error"><?= htmlspecialchars($errors['maximum_otp_attempts'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

            <label>Maximum Image Size MB</label>
            <input class="form-control" type="number" name="maximum_image_size_mb" min="1" max="20" value="<?= (int) ($settings['maximum_image_size_mb'] ?? 5) ?>" required>
            <?php if (!empty($errors['maximum_image_size_mb'])): ?><small class="field-error"><?= htmlspecialchars($errors['maximum_image_size_mb'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

            <label class="switch-row">
                <input type="checkbox" name="require_sale_before_installation" value="1" <?= !empty($settings['require_sale_before_installation']) ? 'checked' : '' ?>>
                <span>Require Dealer sale before installation</span>
            </label>
        </article>
    </section>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Save Settings</button>
    </div>
</form>
