<?php

final class AdminReportService
{
    private $reports;

    public function __construct(?AdminReportRepository $reports = null)
    {
        $this->reports = $reports ?: new AdminReportRepository();
    }

    public function dashboard(array $query): array
    {
        $filters = $this->filters($query);

        try {
            $summary = $this->reports->summary($filters);

            return [
                'ok' => true,
                'filters' => $filters,
                'summary' => $summary,
                'match_rate' => $this->matchRate($summary),
                'daily_sales' => $this->reports->dailySales($filters),
                'daily_installations' => $this->reports->dailyInstallations($filters),
                'top_dealers' => $this->reports->topDealers($filters),
                'top_installers' => $this->reports->topInstallers($filters),
                'unmatched_installations' => $this->reports->unmatchedInstallations($filters),
                'activity_breakdown' => $this->reports->activityBreakdown($filters),
            ];
        } catch (PDOException $exception) {
            error_log('Admin reports failed: ' . $exception->getMessage());

            return [
                'ok' => false,
                'filters' => $filters,
                'summary' => $this->emptySummary(),
                'match_rate' => 0,
                'daily_sales' => [],
                'daily_installations' => [],
                'top_dealers' => [],
                'top_installers' => [],
                'unmatched_installations' => [],
                'activity_breakdown' => [],
                'message' => 'Reports are unavailable.',
            ];
        }
    }

    private function filters(array $query): array
    {
        $dateFrom = $this->date($query['date_from'] ?? '');
        $dateTo = $this->date($query['date_to'] ?? '');

        if ($dateFrom === '' && $dateTo === '') {
            $dateFrom = date('Y-m-01');
            $dateTo = date('Y-m-d');
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    private function date($value): string
    {
        $value = trim((string) $value);
        return preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value) ? $value : '';
    }

    private function matchRate(array $summary): int
    {
        $total = max(1, (int) $summary['installations']);
        return (int) round(((int) $summary['matched_installations'] / $total) * 100);
    }

    private function emptySummary(): array
    {
        return [
            'sales' => 0,
            'installations' => 0,
            'matched_installations' => 0,
            'unmatched_installations' => 0,
            'dealer_activity' => 0,
            'installer_activity' => 0,
            'admin_activity' => 0,
        ];
    }
}
