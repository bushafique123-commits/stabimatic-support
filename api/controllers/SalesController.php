<?php

final class SalesController
{
    private $sales;

    public function __construct(?SalesService $sales = null)
    {
        $this->sales = $sales ?: new SalesService();
    }

    public function index(Request $request): void
    {
        $claims = $this->dealerClaims($request);
        $result = $this->sales->list($claims, $request->query());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Sales unavailable', $result['errors'] ?? [], $result['status'] ?? 503);
        }

        Response::paginated('Sales loaded', $result['items'], (int) $result['total'], (int) $result['page'], (int) $result['limit']);
    }

    public function show(Request $request, string $id): void
    {
        $claims = $this->dealerClaims($request);
        $result = $this->sales->find($claims, (int) $id);

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Sale not found', $result['errors'] ?? [], $result['status'] ?? 404);
        }

        Response::success('Sale loaded', $result['sale']);
    }

    public function commissionLookup(Request $request): void
    {
        $claims = $this->dealerClaims($request);
        $result = $this->sales->lookupCommissionByInvoice($claims, $request->query());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Commission CSV could not be checked', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success($result['message'], [
            'matched' => (bool) $result['matched'],
            'invoice_number' => $result['invoice_number'],
            'device_serial' => $result['device_serial'],
            'device_model' => $result['device_model'],
            'commission' => $result['commission'],
        ]);
    }

    public function store(Request $request): void
    {
        $claims = $this->dealerClaims($request);
        $result = $this->sales->create($claims, $request->input());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Sale could not be saved', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success('Sale added successfully', $result['sale'], 201);
    }

    public function update(Request $request, string $id): void
    {
        $claims = $this->dealerClaims($request);
        $result = $this->sales->update($claims, (int) $id, $request->input());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Sale could not be saved', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success('Sale updated successfully', $result['sale']);
    }

    public function destroy(Request $request, string $id): void
    {
        $claims = $this->dealerClaims($request);
        $result = $this->sales->delete($claims, (int) $id);

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Sale could not be deleted', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success('Sale deleted successfully');
    }

    private function dealerClaims(Request $request): array
    {
        $claims = AuthMiddleware::user($request);
        RoleMiddleware::require($claims, [ROLE_DEALER]);
        return $claims;
    }
}
