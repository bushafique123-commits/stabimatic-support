<?php

final class CommissionsController
{
    private $commissions;

    public function __construct(?AdminCommissionService $commissions = null)
    {
        $this->commissions = $commissions ?: new AdminCommissionService();
    }

    public function index(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $result = $this->commissions->dashboard($_GET);

        View::render('commissions/index', [
            'title' => 'Commissions',
            'admin' => $admin,
            'stats' => $result['stats'],
            'rules' => $result['rules'],
            'dealerRates' => $result['dealer_rates'],
            'installers' => $result['installers'],
            'earnings' => $result['earnings'],
            'payouts' => $result['payouts'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['last_page'],
            'filters' => $result['filters'],
            'errors' => $_SESSION['_commission_errors'] ?? $result['errors'],
            'success' => $_SESSION['_flash_success'] ?? '',
            'error' => $_SESSION['_flash_error'] ?? ($result['ok'] ? '' : ($result['message'] ?? 'Commissions are unavailable.')),
        ]);

        unset($_SESSION['_commission_errors'], $_SESSION['_flash_success'], $_SESSION['_flash_error']);
    }

    public function updateRules(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $this->commissions->saveRules($admin, $_POST);
        $this->flash($result);
        header('Location: /admin/commissions');
        exit;
    }

    public function updateDealerRates(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $this->commissions->saveDealerRates($admin, $_POST);
        $this->flash($result);
        header('Location: /admin/commissions');
        exit;
    }

    public function generate(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $this->commissions->generate($admin);
        $this->flash($result, ' Created/updated: ' . (int) ($result['count'] ?? 0) . '.');
        header('Location: /admin/commissions');
        exit;
    }

    public function generateDealer(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $this->commissions->generateDealer($admin, $_POST);
        $this->flash($result);
        header('Location: /admin/commissions');
        exit;
    }

    public function generateInstaller(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $this->commissions->generateInstaller($admin, $_POST);
        $this->flash($result);
        header('Location: /admin/commissions');
        exit;
    }

    public function approvePending(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $this->commissions->approvePending($admin);
        $this->flash($result, ' Updated: ' . (int) ($result['count'] ?? 0) . '.');
        header('Location: /admin/commissions');
        exit;
    }

    public function createPayouts(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $this->commissions->createPayouts($admin);
        $this->flash($result, ' Created: ' . (int) ($result['count'] ?? 0) . '.');
        header('Location: /admin/commissions');
        exit;
    }

    public function updatePayout(int $payoutId): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $this->commissions->updatePayout($admin, $payoutId, $_POST);
        $this->flash($result);
        header('Location: /admin/commissions');
        exit;
    }

    public function paySource(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $this->commissions->paySource($admin, $_POST);
        $this->flash($result);

        $redirect = (string) ($_POST['_redirect'] ?? '/admin/commissions');
        if (strpos($redirect, '/admin/') !== 0) {
            $redirect = '/admin/commissions';
        }
        header('Location: ' . $redirect);
        exit;
    }

    private function flash(array $result, string $suffix = ''): void
    {
        if ($result['ok'] ?? false) {
            $_SESSION['_flash_success'] = ($result['message'] ?? 'Done.') . $suffix;
            return;
        }

        $_SESSION['_flash_error'] = $result['message'] ?? 'Request failed.';
        if (!empty($result['errors'])) {
            $_SESSION['_commission_errors'] = $result['errors'];
        }
    }
}
