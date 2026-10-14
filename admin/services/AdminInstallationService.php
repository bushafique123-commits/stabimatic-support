<?php

final class AdminInstallationService
{
    private $installations;

    public function __construct(?AdminInstallationRepository $installations = null)
    {
        $this->installations = $installations ?: new AdminInstallationRepository();
    }

    public function list(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $limit = min(50, max(10, (int) ($query['limit'] ?? 20)));
        $filters = [
            'search' => trim((string) ($query['search'] ?? '')),
            'installer_id' => max(0, (int) ($query['installer_id'] ?? 0)),
            'date_from' => $this->date($query['date_from'] ?? ''),
            'date_to' => $this->date($query['date_to'] ?? ''),
            'match' => in_array(($query['match'] ?? ''), ['matched', 'unmatched'], true) ? $query['match'] : '',
        ];

        try {
            $result = $this->installations->paginate($filters, $page, $limit);
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
            error_log('Admin installation list failed: ' . $exception->getMessage());
            return $this->unavailable($page, $limit, $filters);
        }
    }

    public function detail(int $installationId): array
    {
        try {
            $installation = $this->installations->find($installationId);
            if (!$installation) {
                return ['ok' => false, 'status' => 404, 'message' => 'Installation not found'];
            }

            return [
                'ok' => true,
                'installation' => $installation,
                'matched_sale' => $this->installations->matchedSale((string) ($installation['serial_number'] ?? ''), (string) ($installation['device_model'] ?? '')),
                'activity' => $this->installations->relatedActivity($installationId),
            ];
        } catch (PDOException $exception) {
            error_log('Admin installation detail failed: ' . $exception->getMessage());
            return ['ok' => false, 'status' => 503, 'message' => 'Installation management is unavailable.'];
        }
    }

    private function date($value): string
    {
        $value = trim((string) $value);
        return preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value) ? $value : '';
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
            'message' => 'Installation management is unavailable.',
        ];
    }
}
