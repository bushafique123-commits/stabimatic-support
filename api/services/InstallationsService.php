<?php

final class InstallationsService
{
    private $users;
    private $installations;
    private $sales;
    private $settings;
    private $imports;

    public function __construct(?UserRepository $users = null, ?InstallationsRepository $installations = null, ?SalesRepository $sales = null, ?SettingsService $settings = null, ?CommissionImportRepository $imports = null)
    {
        $this->users = $users ?: new UserRepository();
        $this->installations = $installations ?: new InstallationsRepository();
        $this->sales = $sales ?: new SalesRepository();
        $this->settings = $settings ?: new SettingsService();
        $this->imports = $imports ?: new CommissionImportRepository();
    }

    public function list(array $claims, array $query): array
    {
        try {
            $installer = $this->installer($claims);
            if (!$installer) {
                return $this->forbidden();
            }

            $page = Paginator::page($query);
            $limit = Paginator::limit($query, 20, 50);
            $filters = ['search' => InputNormalizer::string($query['search'] ?? '')];
            $result = $this->installations->paginateForInstaller((int) $installer['id'], $filters, $page, $limit);

            return [
                'ok' => true,
                'items' => $result['items'],
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
            ];
        } catch (PDOException $exception) {
            Logger::error('Installations list unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function find(array $claims, int $installationId): array
    {
        try {
            $installer = $this->installer($claims);
            if (!$installer) {
                return $this->forbidden();
            }

            $installation = $this->installations->findForInstaller((int) $installer['id'], $installationId);
            if (!$installation) {
                return ['ok' => false, 'status' => 404, 'message' => 'Installation not found', 'errors' => ['installation' => 'Installation not found']];
            }

            return ['ok' => true, 'installation' => $installation];
        } catch (PDOException $exception) {
            Logger::error('Installation detail unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function create(array $claims, array $input, array $files): array
    {
        return $this->save($claims, $input, $files, null);
    }

    public function update(array $claims, int $installationId, array $input, array $files): array
    {
        return $this->save($claims, $input, $files, $installationId);
    }

    public function delete(array $claims, int $installationId): array
    {
        try {
            $installer = $this->installer($claims);
            if (!$installer) {
                return $this->forbidden();
            }

            $installation = $this->installations->findForInstaller((int) $installer['id'], $installationId);
            if (!$installation) {
                return ['ok' => false, 'status' => 404, 'message' => 'Installation not found', 'errors' => ['installation' => 'Installation not found']];
            }

            $this->installations->softDelete((int) $installer['id'], $installationId);
            Logger::activity(ROLE_INSTALLER, (int) $installer['id'], 'INSTALLATION_DELETED', 'installations', $installationId, 'Installer deleted installation');

            return ['ok' => true];
        } catch (PDOException $exception) {
            Logger::error('Installation delete unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function checkSerial(array $claims, string $serial): array
    {
        $serial = strtoupper(InputNormalizer::string($serial));
        $errors = Validator::validate(['serial_number' => $serial], [
            'serial_number' => ['required', 'max:120'],
        ]);

        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        try {
            $installer = $this->installer($claims);
            if (!$installer) {
                return $this->forbidden();
            }

            $sale = $this->sales->findActiveBySerial($serial);
            $requiresSale = $this->requiresSaleBeforeInstallation();
            if (!$sale) {
                return [
                    'ok' => true,
                    'matched' => false,
                    'requires_sale_before_installation' => $requiresSale,
                    'serial_number' => $serial,
                    'sale' => null,
                    'message' => 'Serial number was not found in Dealer sales',
                ];
            }

            return [
                'ok' => true,
                'matched' => true,
                'requires_sale_before_installation' => $requiresSale,
                'serial_number' => $serial,
                'sale' => $sale,
                'message' => 'Serial number matched Dealer sale',
            ];
        } catch (PDOException $exception) {
            Logger::error('Installation serial check unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function checkCommission(array $claims, array $query): array
    {
        $deviceSerial = strtoupper(InputNormalizer::string($query['serial_number'] ?? $query['device_serial'] ?? $query['invoice_number'] ?? ''));
        $deviceModel = strtoupper(InputNormalizer::string($query['device_model'] ?? $query['model_number'] ?? ''));
        $errors = Validator::validate([
            'serial_number' => $deviceSerial,
            'device_model' => $deviceModel,
        ], [
            'serial_number' => ['required', 'max:120'],
            'device_model' => ['required', 'max:120'],
        ]);

        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        try {
            $installer = $this->installer($claims);
            if (!$installer) {
                return $this->forbidden();
            }

            $csv = $this->imports->findByDeviceSerialModel($deviceSerial, $deviceModel);
            $sale = $this->sales->findActiveBySerialModel($deviceSerial, $deviceModel);
            $requiresSale = $this->requiresSaleBeforeInstallation();

            return [
                'ok' => true,
                'matched' => $csv !== null,
                'sale_matched' => $sale !== null,
                'requires_sale_before_installation' => $requiresSale,
                'invoice_number' => $deviceSerial,
                'device_serial' => $deviceSerial,
                'serial_number' => $deviceSerial,
                'device_model' => $deviceModel,
                'commission' => $csv,
                'sale' => $sale,
                'message' => $csv ? 'Device serial/model matched Commission CSV' : 'Device serial/model was not found in Commission CSV',
            ];
        } catch (PDOException $exception) {
            Logger::error('Installation commission check unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function lookupCommissionByInvoice(array $claims, array $query): array
    {
        $deviceSerial = strtoupper(InputNormalizer::string($query['serial_number'] ?? $query['device_serial'] ?? $query['invoice_number'] ?? ''));
        $errors = Validator::validate(['serial_number' => $deviceSerial], [
            'serial_number' => ['required', 'max:120'],
        ]);

        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        try {
            $installer = $this->installer($claims);
            if (!$installer) {
                return $this->forbidden();
            }

            $row = $this->imports->findByDeviceSerial($deviceSerial);

            return [
                'ok' => true,
                'matched' => $row !== null,
                'invoice_number' => $deviceSerial,
                'device_serial' => $deviceSerial,
                'serial_number' => $deviceSerial,
                'device_model' => $row['model_number'] ?? '',
                'commission' => $row,
                'message' => $row ? 'Device serial matched Commission CSV' : 'Device serial was not found in Commission CSV',
            ];
        } catch (PDOException $exception) {
            Logger::error('Installation commission lookup unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    private function save(array $claims, array $input, array $files, ?int $installationId): array
    {
        $data = InputNormalizer::installation($input);
        $errors = Validator::validate($data, [
            'invoice_number' => ['required', 'max:60'],
            'device_model' => ['required', 'max:120'],
            'customer_email' => ['email', 'max:190'],
        ]);

        if ($installationId === null && empty($files['installation_picture'])) {
            $errors['installation_picture'] = 'Installation picture is required';
        }

        if (!empty($files['installation_picture'])) {
            $validation = FileUpload::validateImage($files['installation_picture']);
            if (!$validation['valid']) {
                $errors['installation_picture'] = $validation['message'];
            }
        }

        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        $newImagePath = null;

        try {
            $installer = $this->installer($claims);
            if (!$installer) {
                return $this->forbidden();
            }

            $data['customer_name'] = $data['customer_name'] !== '' ? $data['customer_name'] : 'CSV Matched Customer';
            $data['customer_mobile'] = $data['customer_mobile'] !== '' ? $data['customer_mobile'] : (string) $installer['mobile'];
            $data['serial_number'] = $data['serial_number'] !== '' ? $data['serial_number'] : $data['invoice_number'] . '-' . $data['device_model'];
            $data['installed_at'] = $data['installed_at'] !== '' ? $data['installed_at'] : date('Y-m-d H:i:s');

            $requiredErrors = Validator::validate($data, [
                'customer_name' => ['required', 'min:3', 'max:150'],
                'customer_mobile' => ['required', 'pak_mobile', 'max:20'],
                'serial_number' => ['required', 'max:120'],
                'installed_at' => ['required', 'datetime'],
            ]);
            if ($requiredErrors) {
                return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $requiredErrors];
            }

            if (!$this->imports->findByDeviceSerialModel($data['serial_number'], $data['device_model'])) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'message' => 'Device serial/model was not found in Commission CSV',
                    'errors' => ['serial_number' => 'Upload this device serial/model in Commission CSV first'],
                ];
            }

            if (!$this->sales->findActiveBySerialModel($data['serial_number'], $data['device_model']) && $this->requiresSaleBeforeInstallation()) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'message' => 'Device serial/model was not found in Dealer sales',
                    'errors' => ['serial_number' => 'Enter a device serial/model from an active Dealer sale'],
                ];
            }

            $existing = null;
            if ($installationId !== null) {
                $existing = $this->installations->findForInstaller((int) $installer['id'], $installationId);
                if (!$existing) {
                    return ['ok' => false, 'status' => 404, 'message' => 'Installation not found', 'errors' => ['installation' => 'Installation not found']];
                }
            }

            $data['installation_picture'] = $existing['installation_picture'] ?? '';
            if (!empty($files['installation_picture'])) {
                $newImagePath = FileUpload::storeImage($files['installation_picture']);
                $data['installation_picture'] = $newImagePath;
            }

            if ($installationId === null) {
                $newId = $this->installations->create((int) $installer['id'], $data);
                Logger::activity(ROLE_INSTALLER, (int) $installer['id'], 'INSTALLATION_CREATED', 'installations', $newId, 'Installer created installation');
                return ['ok' => true, 'installation' => $this->installations->findForInstaller((int) $installer['id'], $newId), 'created' => true];
            }

            $this->installations->update((int) $installer['id'], $installationId, $data);
            if ($newImagePath && $existing) {
                FileUpload::delete($existing['installation_picture']);
            }
            Logger::activity(ROLE_INSTALLER, (int) $installer['id'], 'INSTALLATION_UPDATED', 'installations', $installationId, 'Installer updated installation');
            return ['ok' => true, 'installation' => $this->installations->findForInstaller((int) $installer['id'], $installationId), 'created' => false];
        } catch (PDOException $exception) {
            FileUpload::delete($newImagePath);
            Logger::error('Installation save unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        } catch (RuntimeException $exception) {
            FileUpload::delete($newImagePath);
            Logger::error('Installation image failed', ['detail' => $exception->getMessage()]);
            return ['ok' => false, 'status' => 422, 'message' => 'Image upload failed', 'errors' => ['installation_picture' => $exception->getMessage()]];
        }
    }

    private function installer(array $claims): ?array
    {
        $installer = $this->users->findById((int) ($claims['sub'] ?? 0));
        if (!$installer || $installer['role'] !== ROLE_INSTALLER || !(int) $installer['is_active']) {
            return null;
        }

        return $installer;
    }

    private function requiresSaleBeforeInstallation(): bool
    {
        return (bool) $this->settings->get('require_sale_before_installation', true);
    }

    private function forbidden(): array
    {
        return ['ok' => false, 'status' => 403, 'message' => 'Installer account is not allowed', 'errors' => ['installer' => 'Installer account is not allowed']];
    }

    private function unavailable(): array
    {
        return ['ok' => false, 'status' => 503, 'message' => 'Installations service is unavailable', 'errors' => ['installations' => 'Please try again later']];
    }
}
