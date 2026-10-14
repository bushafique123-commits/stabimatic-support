<?php

final class AdminInstallerService
{
    private $installers;

    public function __construct(?AdminInstallerRepository $installers = null)
    {
        $this->installers = $installers ?: new AdminInstallerRepository();
    }

    public function list(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $limit = min(50, max(10, (int) ($query['limit'] ?? 20)));
        $filters = [
            'search' => trim((string) ($query['search'] ?? '')),
            'status' => trim((string) ($query['status'] ?? '')),
            'verification' => trim((string) ($query['verification'] ?? '')),
        ];

        try {
            $result = $this->installers->paginate($filters, $page, $limit);
            return [
                'ok' => true,
                'items' => $result['items'],
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
                'last_page' => (int) ceil($result['total'] / max($limit, 1)),
                'filters' => $filters,
            ];
        } catch (PDOException $exception) {
            error_log('Admin installer list failed: ' . $exception->getMessage());
            return $this->unavailable($page, $limit, $filters);
        }
    }

    public function detail(int $installerId): array
    {
        try {
            $installer = $this->installers->find($installerId);
            if (!$installer) {
                return ['ok' => false, 'status' => 404, 'message' => 'Installer not found'];
            }

            return [
                'ok' => true,
                'installer' => $installer,
                'stats' => $this->installers->stats($installerId),
                'recent_installations' => $this->installers->recentInstallations($installerId),
            ];
        } catch (PDOException $exception) {
            error_log('Admin installer detail failed: ' . $exception->getMessage());
            return ['ok' => false, 'status' => 503, 'message' => 'Installer management is unavailable.'];
        }
    }

    public function setActive(array $admin, int $installerId, bool $active): array
    {
        return $this->mutate($admin, $installerId, $active ? 'activate' : 'deactivate', function () use ($installerId, $active): void {
            $this->installers->setActive($installerId, $active);
        });
    }

    public function setVerified(array $admin, int $installerId, bool $verified): array
    {
        return $this->mutate($admin, $installerId, $verified ? 'verify' : 'unverify', function () use ($installerId, $verified): void {
            $this->installers->setVerified($installerId, $verified);
        });
    }

    private function mutate(array $admin, int $installerId, string $verb, callable $operation): array
    {
        try {
            if (!$this->installers->find($installerId)) {
                return ['ok' => false, 'message' => 'Installer not found.'];
            }

            $operation();
            $action = 'INSTALLER_' . strtoupper($verb);
            $this->installers->logActivity((int) $admin['id'], $installerId, $action, 'Super Admin updated Installer account');

            return ['ok' => true, 'message' => 'Installer account updated.'];
        } catch (PDOException $exception) {
            error_log('Admin installer mutation failed: ' . $exception->getMessage());
            return ['ok' => false, 'message' => 'Installer account could not be updated.'];
        }
    }

    private function unavailable(int $page, int $limit, array $filters): array
    {
        return [
            'ok' => false,
            'items' => [],
            'total' => 0,
            'page' => $page,
            'limit' => $limit,
            'last_page' => 0,
            'filters' => $filters,
            'message' => 'Installer management is unavailable.',
        ];
    }
}
