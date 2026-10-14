<?php

final class AdminCommissionImportService
{
    private $imports;

    public function __construct(?AdminCommissionImportRepository $imports = null)
    {
        $this->imports = $imports ?: new AdminCommissionImportRepository();
    }

    public function dashboard(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $limit = min(50, max(10, (int) ($query['limit'] ?? 20)));
        $filters = ['search' => trim((string) ($query['search'] ?? ''))];

        try {
            $result = $this->imports->paginate($filters, $page, $limit);
            return [
                'ok' => true,
                'stats' => $this->imports->stats(),
                'rows' => $result['items'],
                'total' => $result['total'],
                'page' => $page,
                'last_page' => (int) ceil($result['total'] / max($limit, 1)),
                'filters' => $filters,
            ];
        } catch (PDOException $exception) {
            error_log('Commission import dashboard failed: ' . $exception->getMessage());
            return [
                'ok' => false,
                'stats' => ['total' => 0, 'dealer_total' => 0, 'installer_total' => 0, 'latest_upload' => null],
                'rows' => [],
                'total' => 0,
                'page' => $page,
                'last_page' => 0,
                'filters' => $filters,
                'message' => 'Commission imports are unavailable.',
            ];
        }
    }

    public function upload(array $admin, array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'Upload a CSV file.'];
        }

        $name = strtolower((string) ($file['name'] ?? ''));
        if (substr($name, -4) !== '.csv') {
            return ['ok' => false, 'message' => 'Only CSV files are supported.'];
        }

        $handle = fopen((string) $file['tmp_name'], 'rb');
        if (!$handle) {
            return ['ok' => false, 'message' => 'CSV file could not be opened.'];
        }

        $rows = [];
        $line = 0;
        $skipped = 0;
        while (($columns = fgetcsv($handle)) !== false) {
            $line++;
            if ($line === 1 && $this->looksLikeHeader($columns)) {
                continue;
            }

            if (count($columns) < 4) {
                $skipped++;
                continue;
            }

            $invoice = strtoupper(trim((string) $columns[1]));
            $model = strtoupper(trim((string) $columns[0]));
            $dealerAmount = $this->money($columns[2]);
            $installerAmount = $this->money($columns[3]);

            if ($invoice === '' || $model === '' || $dealerAmount === null || $installerAmount === null) {
                $skipped++;
                continue;
            }

            $rows[$invoice . '|' . $model] = [
                'invoice_number' => $invoice,
                'model_number' => $model,
                'dealer_commission' => $dealerAmount,
                'installer_commission' => $installerAmount,
            ];
        }
        fclose($handle);

        if (!$rows) {
            return ['ok' => false, 'message' => 'No valid CSV rows were found.'];
        }

        try {
            $count = $this->imports->importRows(array_values($rows), (int) $admin['id']);
            return ['ok' => true, 'message' => 'CSV uploaded. Imported/replaced ' . $count . ' rows.' . ($skipped ? ' Skipped ' . $skipped . ' invalid rows.' : '')];
        } catch (PDOException $exception) {
            error_log('Commission CSV import failed: ' . $exception->getMessage());
            return ['ok' => false, 'message' => 'CSV rows could not be saved.'];
        }
    }

    private function looksLikeHeader(array $columns): bool
    {
        $joined = strtolower(implode('|', array_map('trim', $columns)));
        return strpos($joined, 'model') !== false && (strpos($joined, 'invoice') !== false || strpos($joined, 'serial') !== false);
    }

    private function money($value): ?float
    {
        $value = trim(str_replace(',', '', (string) $value));
        if (!preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $value)) {
            return null;
        }

        return round((float) $value, 2);
    }
}
