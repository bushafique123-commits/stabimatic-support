<?php

final class AdminSaleService
{
    private $sales;

    public function __construct(?AdminSaleRepository $sales = null)
    {
        $this->sales = $sales ?: new AdminSaleRepository();
    }

    public function list(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $limit = min(50, max(10, (int) ($query['limit'] ?? 20)));
        $filters = [
            'search' => trim((string) ($query['search'] ?? '')),
            'dealer_id' => max(0, (int) ($query['dealer_id'] ?? 0)),
            'date_from' => $this->date($query['date_from'] ?? ''),
            'date_to' => $this->date($query['date_to'] ?? ''),
            'match' => in_array(($query['match'] ?? ''), ['matched', 'unmatched'], true) ? $query['match'] : '',
        ];

        try {
            $result = $this->sales->paginate($filters, $page, $limit);
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
            error_log('Admin sale list failed: ' . $exception->getMessage());
            return $this->unavailable($page, $limit, $filters);
        }
    }

    public function detail(int $saleId): array
    {
        try {
            $sale = $this->sales->find($saleId);
            if (!$sale) {
                return ['ok' => false, 'status' => 404, 'message' => 'Sale not found'];
            }

            return [
                'ok' => true,
                'sale' => $sale,
                'matched_installations' => $this->sales->matchedInstallations($saleId),
                'activity' => $this->sales->relatedActivity($saleId),
            ];
        } catch (PDOException $exception) {
            error_log('Admin sale detail failed: ' . $exception->getMessage());
            return ['ok' => false, 'status' => 503, 'message' => 'Sales management is unavailable.'];
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
            'message' => 'Sales management is unavailable.',
        ];
    }
}
