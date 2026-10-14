<?php

final class CommissionImportsController
{
    private $imports;

    public function __construct(?AdminCommissionImportService $imports = null)
    {
        $this->imports = $imports ?: new AdminCommissionImportService();
    }

    public function index(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        $result = $this->imports->dashboard($_GET);

        View::render('commission-imports/index', [
            'title' => 'Commission CSV',
            'admin' => $admin,
            'stats' => $result['stats'],
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $result['page'],
            'lastPage' => $result['last_page'],
            'filters' => $result['filters'],
            'success' => $_SESSION['_flash_success'] ?? '',
            'error' => $_SESSION['_flash_error'] ?? ($result['ok'] ? '' : ($result['message'] ?? 'Commission imports are unavailable.')),
        ]);

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);
    }

    public function upload(): void
    {
        $admin = AdminAuthMiddleware::requireAuth();
        Csrf::verify($_POST['_csrf_token'] ?? null);

        $result = $this->imports->upload($admin, $_FILES['commission_csv'] ?? []);
        if ($result['ok'] ?? false) {
            $_SESSION['_flash_success'] = $result['message'] ?? 'CSV uploaded.';
        } else {
            $_SESSION['_flash_error'] = $result['message'] ?? 'CSV upload failed.';
        }

        header('Location: /admin/commission-imports');
        exit;
    }
}
