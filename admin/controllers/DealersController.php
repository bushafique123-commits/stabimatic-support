<?php

final class DealersController
{
    private $dealers;

    public function __construct(?AdminDealerService $dealers = null)
    {
        $this->dealers = $dealers ?: new AdminDealerService();
    }

    public function index(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $result = $this->dealers->list($_GET);

        View::render('dealers/index', [
            'title' => 'Dealers',
            'admin' => $admin,
            'dealers' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['last_page'],
            'filters' => $result['filters'],
            'error' => $result['ok'] ? '' : ($result['message'] ?? 'Dealer management is unavailable.'),
            'success' => $_SESSION['_flash_success'] ?? '',
            'flashError' => $_SESSION['_flash_error'] ?? '',
        ]);

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);
    }

    public function show(int $dealerId): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $result = $this->dealers->detail($dealerId);

        if (!($result['ok'] ?? false)) {
            http_response_code((int) ($result['status'] ?? 404));
            View::render('dealers/show', [
                'title' => 'Dealer Not Found',
                'admin' => $admin,
                'dealer' => null,
                'stats' => [],
                'recentSales' => [],
                'error' => $result['message'] ?? 'Dealer not found.',
                'success' => '',
                'flashError' => '',
            ]);
            return;
        }

        View::render('dealers/show', [
            'title' => $result['dealer']['full_name'],
            'admin' => $admin,
            'dealer' => $result['dealer'],
            'stats' => $result['stats'],
            'recentSales' => $result['recent_sales'],
            'error' => '',
            'success' => $_SESSION['_flash_success'] ?? '',
            'flashError' => $_SESSION['_flash_error'] ?? '',
        ]);

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);
    }

    public function setActive(int $dealerId, bool $active): void
    {
        $this->handleMutation($dealerId, function (array $admin) use ($dealerId, $active): array {
            return $this->dealers->setActive($admin, $dealerId, $active);
        });
    }

    public function setVerified(int $dealerId, bool $verified): void
    {
        $this->handleMutation($dealerId, function (array $admin) use ($dealerId, $verified): array {
            return $this->dealers->setVerified($admin, $dealerId, $verified);
        });
    }

    private function handleMutation(int $dealerId, callable $operation): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $operation($admin);
        if ($result['ok'] ?? false) {
            $_SESSION['_flash_success'] = $result['message'] ?? 'Dealer account updated.';
        } else {
            $_SESSION['_flash_error'] = $result['message'] ?? 'Dealer account could not be updated.';
        }

        header('Location: /admin/dealers/' . $dealerId);
        exit;
    }
}
