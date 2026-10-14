<?php

final class InstallationsController
{
    private $installations;

    public function __construct(?InstallationsService $installations = null)
    {
        $this->installations = $installations ?: new InstallationsService();
    }

    public function index(Request $request): void
    {
        $claims = $this->installerClaims($request);
        $result = $this->installations->list($claims, $request->query());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Installations unavailable', $result['errors'] ?? [], $result['status'] ?? 503);
        }

        Response::paginated('Installations loaded', $result['items'], (int) $result['total'], (int) $result['page'], (int) $result['limit']);
    }

    public function show(Request $request, string $id): void
    {
        $claims = $this->installerClaims($request);
        $result = $this->installations->find($claims, (int) $id);

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Installation not found', $result['errors'] ?? [], $result['status'] ?? 404);
        }

        Response::success('Installation loaded', $result['installation']);
    }

    public function checkSerial(Request $request, string $serial): void
    {
        $claims = $this->installerClaims($request);
        $result = $this->installations->checkSerial($claims, urldecode($serial));

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Serial number could not be checked', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success($result['message'], [
            'matched' => (bool) $result['matched'],
            'requires_sale_before_installation' => (bool) $result['requires_sale_before_installation'],
            'serial_number' => $result['serial_number'],
            'sale' => $result['sale'],
        ]);
    }

    public function checkCommission(Request $request): void
    {
        $claims = $this->installerClaims($request);
        $result = $this->installations->checkCommission($claims, $request->query());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Device serial/model could not be checked', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success($result['message'], [
            'matched' => (bool) $result['matched'],
            'sale_matched' => (bool) $result['sale_matched'],
            'requires_sale_before_installation' => (bool) $result['requires_sale_before_installation'],
            'invoice_number' => $result['invoice_number'],
            'device_serial' => $result['device_serial'],
            'serial_number' => $result['serial_number'],
            'device_model' => $result['device_model'],
            'commission' => $result['commission'],
            'sale' => $result['sale'],
        ]);
    }

    public function commissionLookup(Request $request): void
    {
        $claims = $this->installerClaims($request);
        $result = $this->installations->lookupCommissionByInvoice($claims, $request->query());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Commission CSV could not be checked', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success($result['message'], [
            'matched' => (bool) $result['matched'],
            'invoice_number' => $result['invoice_number'],
            'device_serial' => $result['device_serial'],
            'serial_number' => $result['serial_number'],
            'device_model' => $result['device_model'],
            'commission' => $result['commission'],
        ]);
    }

    public function store(Request $request): void
    {
        $claims = $this->installerClaims($request);
        $result = $this->installations->create($claims, $request->input(), $request->files());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Installation could not be saved', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success('Installation added successfully', $result['installation'], 201);
    }

    public function update(Request $request, string $id): void
    {
        $claims = $this->installerClaims($request);
        $result = $this->installations->update($claims, (int) $id, $request->input(), $request->files());

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Installation could not be saved', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success('Installation updated successfully', $result['installation']);
    }

    public function destroy(Request $request, string $id): void
    {
        $claims = $this->installerClaims($request);
        $result = $this->installations->delete($claims, (int) $id);

        if (!($result['ok'] ?? false)) {
            Response::error($result['message'] ?? 'Installation could not be deleted', $result['errors'] ?? [], $result['status'] ?? 422);
        }

        Response::success('Installation deleted successfully');
    }

    private function installerClaims(Request $request): array
    {
        $claims = AuthMiddleware::user($request);
        RoleMiddleware::require($claims, [ROLE_INSTALLER]);
        return $claims;
    }
}
