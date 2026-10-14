<section class="login-panel">
    <h1>STABIMATIC</h1>
    <p>Administration Portal</p>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <form method="post" action="/admin/login">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
        <label>Email</label>
        <input class="form-control" type="email" name="email" autocomplete="email" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        <label>Password</label>
        <input class="form-control" type="password" name="password" autocomplete="current-password" required>
        <button class="btn btn-primary w-100" type="submit">Sign In</button>
    </form>
</section>
