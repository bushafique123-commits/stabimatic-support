<?php

final class InstallerDashboardService
{
    private $users;
    private $installations;

    public function __construct(?UserRepository $users = null, ?InstallationsRepository $installations = null)
    {
        $this->users = $users ?: new UserRepository();
        $this->installations = $installations ?: new InstallationsRepository();
    }

    public function summary(array $claims): array
    {
        try {
            $installerId = (int) ($claims['sub'] ?? 0);
            $installer = $this->users->findById($installerId);

            if (!$installer || $installer['role'] !== ROLE_INSTALLER || !(int) $installer['is_active']) {
                return [
                    'ok' => false,
                    'status' => 403,
                    'message' => 'Installer account is not allowed',
                    'errors' => ['installer' => 'Installer account is not allowed'],
                ];
            }

            return [
                'ok' => true,
                'installer' => [
                    'id' => (int) $installer['id'],
                    'name' => $installer['full_name'],
                ],
                'stats' => [
                    'total_installations' => $this->installations->countForInstaller($installerId),
                    'installations_today' => $this->installations->countForInstallerToday($installerId),
                    'installations_this_month' => $this->installations->countForInstallerThisMonth($installerId),
                ],
                'recent_installations' => $this->installations->recentForInstaller($installerId, 5),
            ];
        } catch (PDOException $exception) {
            Logger::error('Installer dashboard unavailable', ['detail' => $exception->getMessage()]);
            return [
                'ok' => false,
                'status' => 503,
                'message' => 'Installer dashboard is unavailable',
                'errors' => ['dashboard' => 'Please try again later'],
            ];
        }
    }
}
