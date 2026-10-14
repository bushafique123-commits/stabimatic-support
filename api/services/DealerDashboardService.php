<?php

final class DealerDashboardService
{
    private $users;
    private $sales;

    public function __construct(?UserRepository $users = null, ?SalesRepository $sales = null)
    {
        $this->users = $users ?: new UserRepository();
        $this->sales = $sales ?: new SalesRepository();
    }

    public function summary(array $claims): array
    {
        try {
            $dealerId = (int) ($claims['sub'] ?? 0);
            $dealer = $this->users->findById($dealerId);

            if (!$dealer || $dealer['role'] !== ROLE_DEALER || !(int) $dealer['is_active']) {
                return [
                    'ok' => false,
                    'status' => 403,
                    'message' => 'Dealer account is not allowed',
                    'errors' => ['dealer' => 'Dealer account is not allowed'],
                ];
            }

            return [
                'ok' => true,
                'dealer' => [
                    'id' => (int) $dealer['id'],
                    'name' => $dealer['full_name'],
                ],
                'stats' => [
                    'total_sales' => $this->sales->countForDealer($dealerId),
                    'sales_this_month' => $this->sales->countForDealerThisMonth($dealerId),
                    'sales_today' => $this->sales->countForDealerToday($dealerId),
                ],
                'recent_sales' => $this->sales->recentForDealer($dealerId, 5),
            ];
        } catch (PDOException $exception) {
            Logger::error('Dealer dashboard unavailable', ['detail' => $exception->getMessage()]);
            return [
                'ok' => false,
                'status' => 503,
                'message' => 'Dealer dashboard is unavailable',
                'errors' => ['dashboard' => 'Please try again later'],
            ];
        }
    }
}
