<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $staticPath = dirname(__DIR__, 2) . str_replace('/', DIRECTORY_SEPARATOR, $requestPath);
    if (is_file($staticPath)) {
        return false;
    }
}

require_once __DIR__ . '/../core/Env.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../repositories/AdminUserRepository.php';
require_once __DIR__ . '/../repositories/AdminDashboardRepository.php';
require_once __DIR__ . '/../repositories/AdminDealerRepository.php';
require_once __DIR__ . '/../repositories/AdminInstallerRepository.php';
require_once __DIR__ . '/../repositories/AdminSaleRepository.php';
require_once __DIR__ . '/../repositories/AdminInstallationRepository.php';
require_once __DIR__ . '/../repositories/AdminReportRepository.php';
require_once __DIR__ . '/../repositories/AdminSettingRepository.php';
require_once __DIR__ . '/../repositories/AdminActivityLogRepository.php';
require_once __DIR__ . '/../repositories/AdminNotificationRepository.php';
require_once __DIR__ . '/../repositories/AdminCommissionRepository.php';
require_once __DIR__ . '/../repositories/AdminCommissionImportRepository.php';
require_once __DIR__ . '/../services/AdminAuthService.php';
require_once __DIR__ . '/../services/AdminDashboardService.php';
require_once __DIR__ . '/../services/AdminDealerService.php';
require_once __DIR__ . '/../services/AdminInstallerService.php';
require_once __DIR__ . '/../services/AdminSaleService.php';
require_once __DIR__ . '/../services/AdminInstallationService.php';
require_once __DIR__ . '/../services/AdminReportService.php';
require_once __DIR__ . '/../services/AdminSettingService.php';
require_once __DIR__ . '/../services/AdminActivityLogService.php';
require_once __DIR__ . '/../services/AdminNotificationService.php';
require_once __DIR__ . '/../services/AdminProfileService.php';
require_once __DIR__ . '/../services/AdminCommissionService.php';
require_once __DIR__ . '/../services/AdminCommissionImportService.php';
require_once __DIR__ . '/../middleware/AdminAuthMiddleware.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/DealersController.php';
require_once __DIR__ . '/../controllers/InstallersController.php';
require_once __DIR__ . '/../controllers/SalesController.php';
require_once __DIR__ . '/../controllers/InstallationsController.php';
require_once __DIR__ . '/../controllers/ReportsController.php';
require_once __DIR__ . '/../controllers/SettingsController.php';
require_once __DIR__ . '/../controllers/ActivityLogsController.php';
require_once __DIR__ . '/../controllers/NotificationsController.php';
require_once __DIR__ . '/../controllers/ProfileController.php';
require_once __DIR__ . '/../controllers/CommissionsController.php';
require_once __DIR__ . '/../controllers/CommissionImportsController.php';

Env::load(__DIR__ . '/../.env');
Session::start();

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($path === '/admin/login' && $method === 'GET') {
    (new AuthController())->showLogin();
    exit;
}

if ($path === '/admin/login' && $method === 'POST') {
    (new AuthController())->login();
    exit;
}

if ($path === '/admin/logout' && $method === 'POST') {
    (new AuthController())->logout();
    exit;
}

if ($path === '/admin' || $path === '/admin/') {
    header('Location: /admin/dashboard');
    exit;
}

if ($path === '/admin/dashboard' && $method === 'GET') {
    (new DashboardController())->index();
    exit;
}

if ($path === '/admin/dealers' && $method === 'GET') {
    (new DealersController())->index();
    exit;
}

if (preg_match('#^/admin/dealers/([0-9]+)$#', $path, $matches) && $method === 'GET') {
    (new DealersController())->show((int) $matches[1]);
    exit;
}

if (preg_match('#^/admin/dealers/([0-9]+)/(activate|deactivate|verify|unverify)$#', $path, $matches) && $method === 'POST') {
    $controller = new DealersController();
    $dealerId = (int) $matches[1];
    if ($matches[2] === 'activate' || $matches[2] === 'deactivate') {
        $controller->setActive($dealerId, $matches[2] === 'activate');
        exit;
    }
    $controller->setVerified($dealerId, $matches[2] === 'verify');
    exit;
}

if ($path === '/admin/installers' && $method === 'GET') {
    (new InstallersController())->index();
    exit;
}

if (preg_match('#^/admin/installers/([0-9]+)$#', $path, $matches) && $method === 'GET') {
    (new InstallersController())->show((int) $matches[1]);
    exit;
}

if (preg_match('#^/admin/installers/([0-9]+)/(activate|deactivate|verify|unverify)$#', $path, $matches) && $method === 'POST') {
    $controller = new InstallersController();
    $installerId = (int) $matches[1];
    if ($matches[2] === 'activate' || $matches[2] === 'deactivate') {
        $controller->setActive($installerId, $matches[2] === 'activate');
        exit;
    }
    $controller->setVerified($installerId, $matches[2] === 'verify');
    exit;
}

if ($path === '/admin/sales' && $method === 'GET') {
    (new SalesController())->index();
    exit;
}

if (preg_match('#^/admin/sales/([0-9]+)$#', $path, $matches) && $method === 'GET') {
    (new SalesController())->show((int) $matches[1]);
    exit;
}

if ($path === '/admin/installations' && $method === 'GET') {
    (new InstallationsController())->index();
    exit;
}

if (preg_match('#^/admin/installations/([0-9]+)$#', $path, $matches) && $method === 'GET') {
    (new InstallationsController())->show((int) $matches[1]);
    exit;
}

if ($path === '/admin/reports' && $method === 'GET') {
    (new ReportsController())->index();
    exit;
}

if ($path === '/admin/settings' && $method === 'GET') {
    (new SettingsController())->index();
    exit;
}

if ($path === '/admin/settings' && $method === 'POST') {
    (new SettingsController())->update();
    exit;
}

if ($path === '/admin/activity-logs' && $method === 'GET') {
    (new ActivityLogsController())->index();
    exit;
}

if (preg_match('#^/admin/activity-logs/([0-9]+)$#', $path, $matches) && $method === 'GET') {
    (new ActivityLogsController())->show((int) $matches[1]);
    exit;
}

if ($path === '/admin/notifications' && $method === 'GET') {
    (new NotificationsController())->index();
    exit;
}

if ($path === '/admin/notifications' && $method === 'POST') {
    (new NotificationsController())->store();
    exit;
}

if (preg_match('#^/admin/notifications/([a-f0-9]{32})$#', $path, $matches) && $method === 'GET') {
    (new NotificationsController())->show($matches[1]);
    exit;
}

if (preg_match('#^/admin/notifications/([a-f0-9]{32})/status$#', $path, $matches) && $method === 'POST') {
    (new NotificationsController())->updateStatus($matches[1]);
    exit;
}

if ($path === '/admin/commissions' && $method === 'GET') {
    (new CommissionsController())->index();
    exit;
}

if ($path === '/admin/commission-imports' && $method === 'GET') {
    (new CommissionImportsController())->index();
    exit;
}

if ($path === '/admin/commission-imports/upload' && $method === 'POST') {
    (new CommissionImportsController())->upload();
    exit;
}

if ($path === '/admin/commissions/rules' && $method === 'POST') {
    (new CommissionsController())->updateRules();
    exit;
}

if ($path === '/admin/commissions/dealer-rates' && $method === 'POST') {
    (new CommissionsController())->updateDealerRates();
    exit;
}

if ($path === '/admin/commissions/generate-dealer' && $method === 'POST') {
    (new CommissionsController())->generateDealer();
    exit;
}

if ($path === '/admin/commissions/generate-installer' && $method === 'POST') {
    (new CommissionsController())->generateInstaller();
    exit;
}

if ($path === '/admin/commissions/generate' && $method === 'POST') {
    (new CommissionsController())->generate();
    exit;
}

if ($path === '/admin/commissions/approve-pending' && $method === 'POST') {
    (new CommissionsController())->approvePending();
    exit;
}

if ($path === '/admin/commissions/create-payouts' && $method === 'POST') {
    (new CommissionsController())->createPayouts();
    exit;
}

if ($path === '/admin/commissions/pay-source' && $method === 'POST') {
    (new CommissionsController())->paySource();
    exit;
}

if (preg_match('#^/admin/commissions/payouts/([0-9]+)/status$#', $path, $matches) && $method === 'POST') {
    (new CommissionsController())->updatePayout((int) $matches[1]);
    exit;
}

if ($path === '/admin/profile' && $method === 'GET') {
    (new ProfileController())->index();
    exit;
}

if ($path === '/admin/profile' && $method === 'POST') {
    (new ProfileController())->update();
    exit;
}

if ($path === '/admin/profile/password' && $method === 'POST') {
    (new ProfileController())->changePassword();
    exit;
}

http_response_code(404);
echo 'Admin page not found.';
