<?php

final class AdminCommissionService
{
    private $commissions;

    public function __construct(?AdminCommissionRepository $commissions = null)
    {
        $this->commissions = $commissions ?: new AdminCommissionRepository();
    }

    public function dashboard(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $limit = min(50, max(10, (int) ($query['limit'] ?? 20)));
        $filters = $this->filters($query);

        try {
            $result = $this->commissions->paginateEarnings($filters, $page, $limit);

            return [
                'ok' => true,
                'stats' => $this->commissions->stats(),
                'rules' => $this->ruleMap($this->commissions->rules()),
                'dealer_rates' => $this->commissions->dealerRates(),
                'installers' => $this->commissions->installers(),
                'earnings' => $result['items'],
                'payouts' => $this->commissions->recentPayouts(),
                'total' => $result['total'],
                'page' => $page,
                'last_page' => (int) ceil($result['total'] / max($limit, 1)),
                'filters' => $filters,
                'errors' => [],
            ];
        } catch (PDOException $exception) {
            error_log('Admin commission dashboard failed: ' . $exception->getMessage());
            return [
                'ok' => false,
                'stats' => $this->emptyStats(),
                'rules' => $this->defaultRules(),
                'dealer_rates' => [],
                'installers' => [],
                'earnings' => [],
                'payouts' => [],
                'total' => 0,
                'page' => $page,
                'last_page' => 0,
                'filters' => $filters,
                'errors' => [],
                'message' => 'Commissions are unavailable.',
            ];
        }
    }

    public function saveRules(array $admin, array $input): array
    {
        $dealerSale = $this->money($input['dealer_sale_amount'] ?? '');
        $installerInstallation = $this->money($input['installer_installation_amount'] ?? '');
        $errors = [];

        if ($dealerSale === null || $dealerSale < 0 || $dealerSale > 999999.99) {
            $errors['dealer_sale_amount'] = 'Dealer sale commission must be between 0 and 999999.99.';
        }
        if ($installerInstallation === null || $installerInstallation < 0 || $installerInstallation > 999999.99) {
            $errors['installer_installation_amount'] = 'Installer installation commission must be between 0 and 999999.99.';
        }

        if ($errors) {
            return ['ok' => false, 'errors' => $errors, 'message' => 'Validation failed.'];
        }

        try {
            $this->commissions->saveRule('dealer', 'sale', (float) $dealerSale);
            $this->commissions->saveRule('installer', 'installation', (float) $installerInstallation);
            $this->commissions->logActivity((int) $admin['id'], 'COMMISSION_RULES_UPDATED', 'Super Admin updated commission rules');

            return ['ok' => true, 'message' => 'Commission rules updated.'];
        } catch (PDOException $exception) {
            error_log('Commission rule update failed: ' . $exception->getMessage());
            return ['ok' => false, 'errors' => [], 'message' => 'Commission rules could not be saved.'];
        }
    }

    public function saveDealerRates(array $admin, array $input): array
    {
        $amounts = is_array($input['dealer_sale_amounts'] ?? null) ? $input['dealer_sale_amounts'] : [];
        $validated = [];
        $errors = [];

        foreach ($amounts as $dealerId => $value) {
            $dealerId = (int) $dealerId;
            if ($dealerId <= 0) {
                continue;
            }

            $value = trim((string) $value);
            if ($value === '') {
                $validated[$dealerId] = null;
                continue;
            }

            $amount = $this->money($value);
            if ($amount === null || $amount < 0 || $amount > 999999.99) {
                $errors['dealer_sale_amounts_' . $dealerId] = 'Enter a valid amount between 0 and 999999.99.';
                continue;
            }

            $validated[$dealerId] = (float) $amount;
        }

        if ($errors) {
            return ['ok' => false, 'errors' => $errors, 'message' => 'Dealer commission rates have invalid values.'];
        }

        try {
            $updated = $this->commissions->saveDealerRates($validated);
            $this->commissions->logActivity((int) $admin['id'], 'DEALER_COMMISSION_RATES_UPDATED', 'Super Admin updated dealer-specific sale commission rates');

            return ['ok' => true, 'message' => 'Dealer commission rates updated.', 'count' => $updated];
        } catch (PDOException $exception) {
            error_log('Dealer commission rate update failed: ' . $exception->getMessage());
            return ['ok' => false, 'errors' => [], 'message' => 'Dealer commission rates could not be saved.'];
        }
    }

    public function generate(array $admin): array
    {
        try {
            $created = $this->commissions->generateEarnings();
            $total = (int) $created['dealer'] + (int) $created['installer'];
            $this->commissions->logActivity((int) $admin['id'], 'COMMISSION_EARNINGS_GENERATED', 'Super Admin generated commission earnings');

            return ['ok' => true, 'message' => 'Commission earnings generated.', 'count' => $total];
        } catch (PDOException $exception) {
            error_log('Commission generation failed: ' . $exception->getMessage());
            return ['ok' => false, 'message' => 'Commission earnings could not be generated.'];
        }
    }

    public function generateDealer(array $admin, array $input): array
    {
        $dealerId = (int) ($input['dealer_id'] ?? 0);
        $amount = $this->money($input['dealer_sale_amount'] ?? '');
        $errors = [];

        if ($dealerId <= 0) {
            $errors['dealer_id'] = 'Select a dealer.';
        }
        if ($amount === null || $amount <= 0 || $amount > 999999.99) {
            $errors['dealer_sale_amount'] = 'Enter a commission amount greater than 0.';
        }

        if ($errors) {
            return ['ok' => false, 'errors' => $errors, 'message' => 'Select dealer and enter amount.'];
        }

        try {
            if (!$this->commissions->dealerExists($dealerId)) {
                return ['ok' => false, 'errors' => ['dealer_id' => 'Selected dealer was not found.'], 'message' => 'Dealer was not found.'];
            }

            $result = $this->commissions->generateDealerEarnings($dealerId, (float) $amount);
            $this->commissions->logActivity((int) $admin['id'], 'DEALER_COMMISSION_EARNINGS_GENERATED', 'Super Admin generated dealer earnings');

            return ['ok' => true, 'message' => 'Dealer earning generated.', 'count' => (int) $result['affected']];
        } catch (PDOException $exception) {
            error_log('Dealer commission generation failed: ' . $exception->getMessage());
            return ['ok' => false, 'errors' => [], 'message' => 'Dealer earnings could not be generated.'];
        }
    }

    public function generateInstaller(array $admin, array $input): array
    {
        $installerId = (int) ($input['installer_id'] ?? 0);
        $amount = $this->money($input['installer_installation_amount'] ?? '');
        $errors = [];

        if ($installerId <= 0) {
            $errors['installer_id'] = 'Select an installer.';
        }
        if ($amount === null || $amount <= 0 || $amount > 999999.99) {
            $errors['installer_installation_amount'] = 'Enter a commission amount greater than 0.';
        }

        if ($errors) {
            return ['ok' => false, 'errors' => $errors, 'message' => 'Select installer and enter amount.'];
        }

        try {
            if (!$this->commissions->installerExists($installerId)) {
                return ['ok' => false, 'errors' => ['installer_id' => 'Selected installer was not found.'], 'message' => 'Installer was not found.'];
            }

            $result = $this->commissions->generateInstallerEarnings($installerId, (float) $amount);
            $this->commissions->logActivity((int) $admin['id'], 'INSTALLER_COMMISSION_EARNINGS_GENERATED', 'Super Admin generated installer earnings');

            return ['ok' => true, 'message' => 'Installer earning generated.', 'count' => (int) $result['affected']];
        } catch (PDOException $exception) {
            error_log('Installer commission generation failed: ' . $exception->getMessage());
            return ['ok' => false, 'errors' => [], 'message' => 'Installer earnings could not be generated.'];
        }
    }

    public function approvePending(array $admin): array
    {
        try {
            $updated = $this->commissions->approvePending();
            $this->commissions->logActivity((int) $admin['id'], 'COMMISSION_EARNINGS_APPROVED', 'Super Admin approved pending commission earnings');

            return ['ok' => true, 'message' => 'Pending commission earnings approved.', 'count' => $updated];
        } catch (PDOException $exception) {
            error_log('Commission approval failed: ' . $exception->getMessage());
            return ['ok' => false, 'message' => 'Pending commission earnings could not be approved.'];
        }
    }

    public function createPayouts(array $admin): array
    {
        try {
            $created = $this->commissions->createPayoutsForApproved((int) $admin['id']);
            $this->commissions->logActivity((int) $admin['id'], 'COMMISSION_PAYOUTS_CREATED', 'Super Admin created commission payout records');

            return ['ok' => true, 'message' => 'Commission payouts created.', 'count' => $created];
        } catch (PDOException $exception) {
            error_log('Commission payout creation failed: ' . $exception->getMessage());
            return ['ok' => false, 'message' => 'Commission payouts could not be created.'];
        }
    }

    public function updatePayout(array $admin, int $payoutId, array $input): array
    {
        $status = (string) ($input['status'] ?? '');
        $reference = trim((string) ($input['payment_reference'] ?? ''));
        $notes = trim((string) ($input['notes'] ?? ''));
        if (!in_array($status, ['paid', 'cancelled'], true)) {
            return ['ok' => false, 'message' => 'Invalid payout status.'];
        }
        if (strlen($reference) > 120 || strlen($notes) > 1000) {
            return ['ok' => false, 'message' => 'Payout reference or notes are too long.'];
        }

        try {
            $updated = $this->commissions->setPayoutStatus($payoutId, $status, $reference, $notes);
            if (!$updated) {
                return ['ok' => false, 'message' => 'Payout was not updated.'];
            }

            $this->commissions->logActivity((int) $admin['id'], 'COMMISSION_PAYOUT_UPDATED', 'Super Admin marked payout #' . $payoutId . ' as ' . $status);
            return ['ok' => true, 'message' => 'Commission payout updated.'];
        } catch (PDOException $exception) {
            error_log('Commission payout update failed: ' . $exception->getMessage());
            return ['ok' => false, 'message' => 'Commission payout could not be updated.'];
        }
    }

    public function paySource(array $admin, array $input): array
    {
        $sourceType = (string) ($input['source_type'] ?? '');
        $sourceId = (int) ($input['source_id'] ?? 0);
        $amount = $this->money($input['amount'] ?? '');

        if (!in_array($sourceType, ['sale', 'installation'], true) || $sourceId <= 0) {
            return ['ok' => false, 'message' => 'Invalid payout source.'];
        }
        if ($amount === null || $amount <= 0 || $amount > 999999.99) {
            return ['ok' => false, 'message' => 'Enter a payout amount greater than 0.'];
        }

        try {
            $updated = $sourceType === 'sale'
                ? $this->commissions->markSalePaid($sourceId, (float) $amount)
                : $this->commissions->markInstallationPaid($sourceId, (float) $amount);

            if (!$updated) {
                return ['ok' => false, 'message' => 'Payout source was not found.'];
            }

            $this->commissions->logActivity((int) $admin['id'], 'COMMISSION_SOURCE_PAID', 'Super Admin marked ' . $sourceType . ' #' . $sourceId . ' commission paid');
            return ['ok' => true, 'message' => ucfirst($sourceType) . ' commission marked paid.'];
        } catch (PDOException $exception) {
            error_log('Commission source paid failed: ' . $exception->getMessage());
            return ['ok' => false, 'message' => 'Commission payout could not be saved.'];
        }
    }

    private function filters(array $query): array
    {
        return [
            'search' => trim((string) ($query['search'] ?? '')),
            'role' => in_array(($query['role'] ?? ''), ['dealer', 'installer'], true) ? $query['role'] : '',
            'status' => in_array(($query['status'] ?? ''), ['pending', 'approved', 'paid', 'cancelled'], true) ? $query['status'] : '',
            'date_from' => $this->date($query['date_from'] ?? ''),
            'date_to' => $this->date($query['date_to'] ?? ''),
        ];
    }

    private function ruleMap(array $rules): array
    {
        $mapped = $this->defaultRules();
        foreach ($rules as $rule) {
            $mapped[$rule['role'] . '_' . $rule['event_type']] = $rule;
        }

        return $mapped;
    }

    private function defaultRules(): array
    {
        return [
            'dealer_sale' => ['amount' => 0.00, 'currency' => 'PKR'],
            'installer_installation' => ['amount' => 0.00, 'currency' => 'PKR'],
        ];
    }

    private function emptyStats(): array
    {
        return [
            'pending_count' => 0,
            'pending_amount' => 0,
            'approved_count' => 0,
            'approved_amount' => 0,
            'paid_count' => 0,
            'paid_amount' => 0,
            'cancelled_count' => 0,
            'cancelled_amount' => 0,
            'pending_payout_count' => 0,
            'pending_payout_amount' => 0,
        ];
    }

    private function money($value): ?float
    {
        $value = trim((string) $value);
        if (!preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    private function date($value): string
    {
        $value = trim((string) $value);
        return preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value) ? $value : '';
    }
}
