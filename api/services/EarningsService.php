<?php

final class EarningsService
{
    private $users;
    private $earnings;

    public function __construct(?UserRepository $users = null, ?EarningsRepository $earnings = null)
    {
        $this->users = $users ?: new UserRepository();
        $this->earnings = $earnings ?: new EarningsRepository();
    }

    public function summary(array $claims): array
    {
        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            return ['ok' => true, 'summary' => $this->earnings->summaryForUser((int) $user['id'])];
        } catch (PDOException $exception) {
            Logger::error('Earnings summary unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function earnings(array $claims, array $query): array
    {
        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            $page = Paginator::page($query);
            $limit = Paginator::limit($query, 20, 50);
            $filters = [
                'status' => in_array(($query['status'] ?? ''), ['pending', 'approved', 'paid', 'cancelled'], true) ? $query['status'] : '',
            ];
            $result = $this->earnings->paginateEarningsForUser((int) $user['id'], $filters, $page, $limit);

            return [
                'ok' => true,
                'items' => array_map([$this, 'earningResponse'], $result['items']),
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
            ];
        } catch (PDOException $exception) {
            Logger::error('Earnings list unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function payouts(array $claims, array $query): array
    {
        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            $page = Paginator::page($query);
            $limit = Paginator::limit($query, 20, 50);
            $filters = [
                'status' => in_array(($query['status'] ?? ''), ['pending', 'paid', 'cancelled'], true) ? $query['status'] : '',
            ];
            $result = $this->earnings->paginatePayoutsForUser((int) $user['id'], $filters, $page, $limit);

            return [
                'ok' => true,
                'items' => array_map([$this, 'payoutResponse'], $result['items']),
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
            ];
        } catch (PDOException $exception) {
            Logger::error('Payout list unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    private function activeUser(array $claims): ?array
    {
        $user = $this->users->findById((int) ($claims['sub'] ?? 0));
        if (!$user || !in_array($user['role'], [ROLE_DEALER, ROLE_INSTALLER], true) || !(int) $user['is_active']) {
            return null;
        }

        return $user;
    }

    private function earningResponse(array $earning): array
    {
        return [
            'id' => (int) $earning['id'],
            'source_type' => $earning['source_type'],
            'source_id' => (int) $earning['source_id'],
            'source_label' => $earning['source_label'] ?: ucfirst($earning['source_type']) . ' #' . (int) $earning['source_id'],
            'amount' => (float) $earning['amount'],
            'currency' => $earning['currency'],
            'status' => $earning['status'],
            'payout_id' => $earning['payout_id'] ? (int) $earning['payout_id'] : null,
            'earned_at' => $earning['earned_at'],
            'approved_at' => $earning['approved_at'],
            'paid_at' => $earning['paid_at'],
            'created_at' => $earning['created_at'],
        ];
    }

    private function payoutResponse(array $payout): array
    {
        return [
            'id' => (int) $payout['id'],
            'amount' => (float) $payout['amount'],
            'currency' => 'PKR',
            'status' => $payout['status'],
            'payment_reference' => $payout['payment_reference'] ?? '',
            'notes' => $payout['notes'] ?? '',
            'earning_count' => (int) $payout['earning_count'],
            'paid_at' => $payout['paid_at'],
            'created_at' => $payout['created_at'],
            'updated_at' => $payout['updated_at'],
        ];
    }

    private function forbidden(): array
    {
        return ['ok' => false, 'status' => 403, 'message' => 'Earnings are not available for this account', 'errors' => ['earnings' => 'Earnings are not available']];
    }

    private function unavailable(): array
    {
        return ['ok' => false, 'status' => 503, 'message' => 'Earnings service is unavailable', 'errors' => ['earnings' => 'Please try again later']];
    }
}
