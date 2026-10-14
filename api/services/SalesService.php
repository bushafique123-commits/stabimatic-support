<?php

final class SalesService
{
    private $users;
    private $sales;
    private $imports;

    public function __construct(?UserRepository $users = null, ?SalesRepository $sales = null, ?CommissionImportRepository $imports = null)
    {
        $this->users = $users ?: new UserRepository();
        $this->sales = $sales ?: new SalesRepository();
        $this->imports = $imports ?: new CommissionImportRepository();
    }

    public function list(array $claims, array $query): array
    {
        try {
            $dealer = $this->dealer($claims);
            if (!$dealer) {
                return $this->forbidden();
            }

            $page = Paginator::page($query);
            $limit = Paginator::limit($query, 20, 50);
            $filters = ['search' => InputNormalizer::string($query['search'] ?? '')];
            $result = $this->sales->paginateForDealer((int) $dealer['id'], $filters, $page, $limit);

            return [
                'ok' => true,
                'items' => $result['items'],
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
            ];
        } catch (PDOException $exception) {
            Logger::error('Sales list unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function find(array $claims, int $saleId): array
    {
        try {
            $dealer = $this->dealer($claims);
            if (!$dealer) {
                return $this->forbidden();
            }

            $sale = $this->sales->findForDealer((int) $dealer['id'], $saleId);
            if (!$sale) {
                return ['ok' => false, 'status' => 404, 'message' => 'Sale not found', 'errors' => ['sale' => 'Sale not found']];
            }

            return ['ok' => true, 'sale' => $sale];
        } catch (PDOException $exception) {
            Logger::error('Sale detail unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function create(array $claims, array $input): array
    {
        return $this->save($claims, $input, null);
    }

    public function update(array $claims, int $saleId, array $input): array
    {
        return $this->save($claims, $input, $saleId);
    }

    public function delete(array $claims, int $saleId): array
    {
        try {
            $dealer = $this->dealer($claims);
            if (!$dealer) {
                return $this->forbidden();
            }

            $sale = $this->sales->findForDealer((int) $dealer['id'], $saleId);
            if (!$sale) {
                return ['ok' => false, 'status' => 404, 'message' => 'Sale not found', 'errors' => ['sale' => 'Sale not found']];
            }

            $this->sales->softDelete((int) $dealer['id'], $saleId);
            Logger::activity(ROLE_DEALER, (int) $dealer['id'], 'SALE_DELETED', 'sales', $saleId, 'Dealer deleted sale');

            return ['ok' => true];
        } catch (PDOException $exception) {
            Logger::error('Sale delete unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function lookupCommissionByInvoice(array $claims, array $query): array
    {
        $deviceSerial = strtoupper(InputNormalizer::string($query['device_serial'] ?? $query['invoice_number'] ?? ''));
        $errors = Validator::validate(['device_serial' => $deviceSerial], [
            'device_serial' => ['required', 'max:120'],
        ]);

        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        try {
            $dealer = $this->dealer($claims);
            if (!$dealer) {
                return $this->forbidden();
            }

            $row = $this->imports->findByDeviceSerial($deviceSerial);

            return [
                'ok' => true,
                'matched' => $row !== null,
                'invoice_number' => $deviceSerial,
                'device_serial' => $deviceSerial,
                'device_model' => $row['model_number'] ?? '',
                'commission' => $row,
                'message' => $row ? 'Device serial matched Commission CSV' : 'Device serial was not found in Commission CSV',
            ];
        } catch (PDOException $exception) {
            Logger::error('Sale commission lookup unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    private function save(array $claims, array $input, ?int $saleId): array
    {
        $data = InputNormalizer::sale($input);
        $errors = Validator::validate($data, [
            'invoice_number' => ['required', 'max:60'],
            'device_model' => ['required', 'max:120'],
        ]);

        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        try {
            $dealer = $this->dealer($claims);
            if (!$dealer) {
                return $this->forbidden();
            }

            $data['sale_date'] = $data['sale_date'] !== '' ? $data['sale_date'] : date('Y-m-d');
            $data['customer_name'] = $data['customer_name'] !== '' ? $data['customer_name'] : 'CSV Matched Customer';
            $data['customer_mobile'] = $data['customer_mobile'] !== '' ? $data['customer_mobile'] : (string) $dealer['mobile'];
            $data['device_serial'] = $data['device_serial'] !== '' ? $data['device_serial'] : $data['invoice_number'] . '-' . $data['device_model'];

            $requiredErrors = Validator::validate($data, [
                'sale_date' => ['required', 'date'],
                'customer_name' => ['required', 'min:3', 'max:150'],
                'customer_mobile' => ['required', 'pak_mobile', 'max:20'],
                'device_serial' => ['required', 'max:120'],
            ]);
            if ($requiredErrors) {
                return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $requiredErrors];
            }

            if (!$this->imports->findByDeviceSerialModel($data['device_serial'], $data['device_model'])) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'message' => 'CSV commission row was not found',
                    'errors' => ['device_serial' => 'Upload this device serial/model in Commission CSV first'],
                ];
            }

            if ($saleId !== null && !$this->sales->findForDealer((int) $dealer['id'], $saleId)) {
                return ['ok' => false, 'status' => 404, 'message' => 'Sale not found', 'errors' => ['sale' => 'Sale not found']];
            }

            if ($this->sales->activeSerialExists($data['device_serial'], $saleId)) {
                return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => ['device_serial' => 'Device serial number is already registered']];
            }

            if ($saleId === null) {
                $newId = $this->sales->create((int) $dealer['id'], $data);
                Logger::activity(ROLE_DEALER, (int) $dealer['id'], 'SALE_CREATED', 'sales', $newId, 'Dealer created sale');
                return ['ok' => true, 'sale' => $this->sales->findForDealer((int) $dealer['id'], $newId), 'created' => true];
            }

            $this->sales->update((int) $dealer['id'], $saleId, $data);
            Logger::activity(ROLE_DEALER, (int) $dealer['id'], 'SALE_UPDATED', 'sales', $saleId, 'Dealer updated sale');
            return ['ok' => true, 'sale' => $this->sales->findForDealer((int) $dealer['id'], $saleId), 'created' => false];
        } catch (PDOException $exception) {
            Logger::error('Sale save unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    private function dealer(array $claims): ?array
    {
        $dealer = $this->users->findById((int) ($claims['sub'] ?? 0));
        if (!$dealer || $dealer['role'] !== ROLE_DEALER || !(int) $dealer['is_active']) {
            return null;
        }

        return $dealer;
    }

    private function forbidden(): array
    {
        return ['ok' => false, 'status' => 403, 'message' => 'Dealer account is not allowed', 'errors' => ['dealer' => 'Dealer account is not allowed']];
    }

    private function unavailable(): array
    {
        return ['ok' => false, 'status' => 503, 'message' => 'Sales service is unavailable', 'errors' => ['sales' => 'Please try again later']];
    }
}
