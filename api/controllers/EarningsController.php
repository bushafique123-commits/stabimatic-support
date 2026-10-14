<?php

final class EarningsController
{
    private $earnings;

    public function __construct(?EarningsService $earnings = null)
    {
        $this->earnings = $earnings ?: new EarningsService();
    }

    public function summary(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->earnings->summary($claims);

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Earnings summary could not be loaded', $result['errors'] ?? [], $result['status'] ?? 503);
        }

        Response::success('Earnings summary loaded', $result['summary']);
    }

    public function earnings(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->earnings->earnings($claims, $request->query());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Earnings could not be loaded', $result['errors'] ?? [], $result['status'] ?? 503);
        }

        Response::paginated('Earnings loaded', $result['items'], (int) $result['total'], (int) $result['page'], (int) $result['limit']);
    }

    public function payouts(Request $request): void
    {
        $claims = AuthMiddleware::user($request);
        $result = $this->earnings->payouts($claims, $request->query());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Payouts could not be loaded', $result['errors'] ?? [], $result['status'] ?? 503);
        }

        Response::paginated('Payouts loaded', $result['items'], (int) $result['total'], (int) $result['page'], (int) $result['limit']);
    }
}
