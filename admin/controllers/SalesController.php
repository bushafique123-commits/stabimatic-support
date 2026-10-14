<?php

final class SalesController
{
    private $sales;

    public function __construct(?AdminSaleService $sales = null)
    {
        $this->sales = $sales ?: new AdminSaleService();
    }

    public function index(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $result = $this->sales->list($_GET);

        View::render('sales/index', [
            'title' => 'Sales',
            'admin' => $admin,
            'sales' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['last_page'],
            'filters' => $result['filters'],
            'success' => $_SESSION['_flash_success'] ?? '',
            'error' => $_SESSION['_flash_error'] ?? ($result['ok'] ? '' : ($result['message'] ?? 'Sales management is unavailable.')),
        ]);

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);
    }

    public function show(int $saleId): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $result = $this->sales->detail($saleId);

        if (!($result['ok'] ?? false)) {
            http_response_code((int) ($result['status'] ?? 404));
            View::render('sales/show', [
                'title' => 'Sale Not Found',
                'admin' => $admin,
                'sale' => null,
                'matchedInstallations' => [],
                'activity' => [],
                'error' => $result['message'] ?? 'Sale not found.',
            ]);
            return;
        }

        View::render('sales/show', [
            'title' => $result['sale']['invoice_number'],
            'admin' => $admin,
            'sale' => $result['sale'],
            'matchedInstallations' => $result['matched_installations'],
            'activity' => $result['activity'],
            'error' => '',
        ]);
    }
}
