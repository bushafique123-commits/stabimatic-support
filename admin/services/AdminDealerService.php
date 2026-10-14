<?php

final class AdminDealerService
{
    private $dealers;

    public function __construct(?AdminDealerRepository $dealers = null)
    {
        $this->dealers = $dealers ?: new AdminDealerRepository();
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
            $result = $this->dealers->paginate($filters, $page, $limit);
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
            error_log('Admin dealer list failed: ' . $exception->getMessage());
            return $this->unavailable($page, $limit, $filters);
        }
    }

    public function detail(int $dealerId): array
    {
        try {
            $dealer = $this->dealers->find($dealerId);
            if (!$dealer) {
                return ['ok' => false, 'status' => 404, 'message' => 'Dealer not found'];
            }

            return [
                'ok' => true,
                'dealer' => $dealer,
                'stats' => $this->dealers->stats($dealerId),
                'recent_sales' => $this->dealers->recentSales($dealerId),
            ];
        } catch (PDOException $exception) {
            error_log('Admin dealer detail failed: ' . $exception->getMessage());
            return ['ok' => false, 'status' => 503, 'message' => 'Dealer management is unavailable.'];
        }
    }

    public function setActive(array $admin, int $dealerId, bool $active): array
    {
        return $this->mutate($admin, $dealerId, $active ? 'activate' : 'deactivate', function () use ($dealerId, $active): void {
            $this->dealers->setActive($dealerId, $active);
        });
    }

    public function setVerified(array $admin, int $dealerId, bool $verified): array
    {
        return $this->mutate($admin, $dealerId, $verified ? 'verify' : 'unverify', function () use ($dealerId, $verified): void {
            $this->dealers->setVerified($dealerId, $verified);
        });
    }

    private function mutate(array $admin, int $dealerId, string $verb, callable $operation): array
    {
        try {
            if (!$this->dealers->find($dealerId)) {
                return ['ok' => false, 'message' => 'Dealer not found.'];
            }

            $operation();
            $action = 'DEALER_' . strtoupper($verb);
            $this->dealers->logActivity((int) $admin['id'], $dealerId, $action, 'Super Admin updated Dealer account');

            return ['ok' => true, 'message' => 'Dealer account updated.'];
        } catch (PDOException $exception) {
            error_log('Admin dealer mutation failed: ' . $exception->getMessage());
            return ['ok' => false, 'message' => 'Dealer account could not be updated.'];
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
            'message' => 'Dealer management is unavailable.',
        ];
    }
}
