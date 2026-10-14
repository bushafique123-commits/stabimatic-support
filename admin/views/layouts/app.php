<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'STABIMATIC Admin', ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="/admin/assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="brand">STABIMATIC</div>
            <nav>
                <a href="/admin/dashboard">Dashboard</a>
                <a href="/admin/dealers">Dealers</a>
                <a href="/admin/installers">Installers</a>
                <a href="/admin/sales">Sales</a>
                <a href="/admin/installations">Installations</a>
                <a href="/admin/reports">Reports</a>
                <a href="/admin/notifications">Notifications</a>
                <a href="/admin/commissions">Commissions</a>
                <a href="/admin/commission-imports">Commission CSV</a>
                <a href="/admin/settings">Settings</a>
                <a href="/admin/activity-logs">Activity Logs</a>
                <a href="/admin/profile">Profile</a>
            </nav>
            <form method="post" action="/admin/logout" class="logout-form">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit">Sign Out</button>
            </form>
        </aside>
        <main class="admin-main">
            <div class="admin-topbar">
                <a href="/admin/profile"><?= htmlspecialchars($admin['name'] ?? 'Super Admin', ENT_QUOTES, 'UTF-8') ?></a>
                <small><?= htmlspecialchars($admin['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
            </div>
            <?= $content ?>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
