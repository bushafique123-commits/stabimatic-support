<header class="page-head">
    <div>
        <h1>Profile</h1>
        <p>Manage your Super Admin account details and password.</p>
    </div>
</header>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-warning"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<?php if ($profile): ?>
    <section class="profile-grid">
        <form class="profile-form panel" method="post" action="/admin/profile">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="panel-head">
                <h2>Account Details</h2>
                <span>#<?= (int) $profile['id'] ?></span>
            </div>

            <label for="name">Name</label>
            <input class="form-control" id="name" name="name" value="<?= htmlspecialchars($profile['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="150" required>
            <?php if (!empty($profileErrors['name'])): ?><small class="field-error"><?= htmlspecialchars($profileErrors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

            <label for="email">Email</label>
            <input class="form-control" id="email" type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="190" required>
            <?php if (!empty($profileErrors['email'])): ?><small class="field-error"><?= htmlspecialchars($profileErrors['email'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

            <dl class="detail-list profile-meta">
                <div><dt>Status</dt><dd><?= (int) ($profile['is_active'] ?? 0) ? 'Active' : 'Inactive' ?></dd></div>
                <div><dt>Last Login</dt><dd><?= htmlspecialchars($profile['last_login_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Created At</dt><dd><?= htmlspecialchars($profile['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
            </dl>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save Profile</button>
            </div>
        </form>

        <form class="profile-form panel" method="post" action="/admin/profile/password">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="panel-head">
                <h2>Password</h2>
            </div>

            <label for="current_password">Current Password</label>
            <input class="form-control" id="current_password" type="password" name="current_password" autocomplete="current-password" required>
            <?php if (!empty($passwordErrors['current_password'])): ?><small class="field-error"><?= htmlspecialchars($passwordErrors['current_password'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

            <label for="new_password">New Password</label>
            <input class="form-control" id="new_password" type="password" name="new_password" autocomplete="new-password" required>
            <?php if (!empty($passwordErrors['new_password'])): ?><small class="field-error"><?= htmlspecialchars($passwordErrors['new_password'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

            <label for="confirm_password">Confirm New Password</label>
            <input class="form-control" id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" required>
            <?php if (!empty($passwordErrors['confirm_password'])): ?><small class="field-error"><?= htmlspecialchars($passwordErrors['confirm_password'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Change Password</button>
            </div>
        </form>
    </section>
<?php endif; ?>
