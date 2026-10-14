<?php

final class AdminDashboardService
{
    private $dashboard;

    public function __construct(?AdminDashboardRepository $dashboard = null)
    {
        $this->dashboard = $dashboard ?: new AdminDashboardRepository();
    }

    public function summary(): array
    {
        try {
            $stats = $this->dashboard->stats();
            $settings = $this->dashboard->settings();

            return [
                'ok' => true,
                'stats' => $stats,
                'recent_activity' => $this->dashboard->recentActivity(8),
                'status' => $this->status($stats, $settings),
            ];
        } catch (PDOException $exception) {
            error_log('Admin dashboard failed: ' . $exception->getMessage());

            return [
                'ok' => false,
                'stats' => $this->emptyStats(),
                'recent_activity' => [],
                'status' => [
                    ['label' => 'Database', 'value' => 'Unavailable', 'tone' => 'danger'],
                    ['label' => 'Dashboard', 'value' => 'Retry after database setup', 'tone' => 'warning'],
                ],
            ];
        }
    }

    private function status(array $stats, array $settings): array
    {
        $maxImageSize = (int) ($settings['maximum_image_size'] ?? 0);
        $imageSizeMb = $maxImageSize > 0 ? number_format($maxImageSize / 1048576, 1) . ' MB' : 'Not set';
        $requiresSale = (bool) ($settings['require_sale_before_installation'] ?? true);

        return [
            ['label' => 'Database', 'value' => 'Online', 'tone' => 'success'],
            ['label' => 'Serial Policy', 'value' => $requiresSale ? 'Dealer sale required' : 'Warning only', 'tone' => $requiresSale ? 'success' : 'warning'],
            ['label' => 'Unmatched Installs', 'value' => (string) $stats['unmatched_installations'], 'tone' => $stats['unmatched_installations'] > 0 ? 'warning' : 'success'],
            ['label' => 'OTP Expiry', 'value' => (string) ($settings['otp_expiry_minutes'] ?? 10) . ' minutes', 'tone' => 'neutral'],
            ['label' => 'OTP Resend', 'value' => (string) ($settings['otp_resend_seconds'] ?? 60) . ' seconds', 'tone' => 'neutral'],
            ['label' => 'Image Upload Limit', 'value' => $imageSizeMb, 'tone' => 'neutral'],
        ];
    }

    private function emptyStats(): array
    {
        return [
            'dealers' => 0,
            'active_dealers' => 0,
            'pending_dealers' => 0,
            'installers' => 0,
            'active_installers' => 0,
            'pending_installers' => 0,
            'sales' => 0,
            'sales_today' => 0,
            'installations' => 0,
            'installations_today' => 0,
            'matched_installations' => 0,
            'unmatched_installations' => 0,
        ];
    }
}
