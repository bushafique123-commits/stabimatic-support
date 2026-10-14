<?php

final class AdminProfileService
{
    private $admins;

    public function __construct(?AdminUserRepository $admins = null)
    {
        $this->admins = $admins ?: new AdminUserRepository();
    }

    public function detail(int $adminId): array
    {
        try {
            $admin = $this->admins->findById($adminId);
            if (!$admin) {
                return ['ok' => false, 'profile' => null, 'message' => 'Admin profile not found.'];
            }

            return ['ok' => true, 'profile' => $admin, 'errors' => []];
        } catch (PDOException $exception) {
            error_log('Admin profile load failed: ' . $exception->getMessage());
            return ['ok' => false, 'profile' => null, 'errors' => [], 'message' => 'Admin profile is unavailable.'];
        }
    }

    public function updateProfile(array $admin, array $input): array
    {
        $adminId = (int) $admin['id'];
        $profile = [
            'id' => $adminId,
            'name' => trim((string) ($input['name'] ?? '')),
            'email' => strtolower(trim((string) ($input['email'] ?? ''))),
            'is_active' => 1,
            'last_login_at' => $admin['last_login_at'] ?? null,
            'created_at' => $admin['created_at'] ?? null,
        ];
        $errors = $this->validateProfile($profile);

        if ($errors) {
            return ['ok' => false, 'profile' => $profile, 'errors' => $errors, 'message' => 'Validation failed.'];
        }

        try {
            if ($this->admins->emailExistsForAnotherAdmin($profile['email'], $adminId)) {
                return [
                    'ok' => false,
                    'profile' => $profile,
                    'errors' => ['email' => 'This email is already used by another admin.'],
                    'message' => 'Validation failed.',
                ];
            }

            $this->admins->updateProfile($adminId, $profile['name'], $profile['email']);
            $freshProfile = $this->admins->findById($adminId) ?: $profile;
            $_SESSION['admin_user'] = [
                'id' => (int) $freshProfile['id'],
                'name' => $freshProfile['name'],
                'email' => $freshProfile['email'],
            ];
            $this->admins->logActivity($adminId, 'ADMIN_PROFILE_UPDATED', 'Super Admin updated profile details', 'admin_profile');

            return ['ok' => true, 'profile' => $freshProfile, 'errors' => [], 'message' => 'Profile updated.'];
        } catch (PDOException $exception) {
            error_log('Admin profile update failed: ' . $exception->getMessage());
            return ['ok' => false, 'profile' => $profile, 'errors' => [], 'message' => 'Profile could not be saved.'];
        }
    }

    public function changePassword(int $adminId, array $input): array
    {
        $currentPassword = (string) ($input['current_password'] ?? '');
        $newPassword = (string) ($input['new_password'] ?? '');
        $confirmPassword = (string) ($input['confirm_password'] ?? '');
        $errors = $this->validatePassword($currentPassword, $newPassword, $confirmPassword);

        if ($errors) {
            return ['ok' => false, 'errors' => $errors, 'message' => 'Validation failed.'];
        }

        try {
            $admin = $this->admins->findForPasswordChange($adminId);
            if (!$admin || !(int) $admin['is_active']) {
                return ['ok' => false, 'errors' => [], 'message' => 'Admin profile not found.'];
            }

            if (!password_verify($currentPassword, $admin['password_hash'])) {
                return [
                    'ok' => false,
                    'errors' => ['current_password' => 'Current password is incorrect.'],
                    'message' => 'Validation failed.',
                ];
            }

            if (password_verify($newPassword, $admin['password_hash'])) {
                return [
                    'ok' => false,
                    'errors' => ['new_password' => 'New password must be different from the current password.'],
                    'message' => 'Validation failed.',
                ];
            }

            $this->admins->updatePassword($adminId, password_hash($newPassword, PASSWORD_DEFAULT));
            $this->admins->logActivity($adminId, 'ADMIN_PASSWORD_CHANGED', 'Super Admin changed account password', 'admin_profile');

            return ['ok' => true, 'errors' => [], 'message' => 'Password changed.'];
        } catch (PDOException $exception) {
            error_log('Admin password change failed: ' . $exception->getMessage());
            return ['ok' => false, 'errors' => [], 'message' => 'Password could not be changed.'];
        }
    }

    private function validateProfile(array $profile): array
    {
        $errors = [];

        if ($profile['name'] === '' || strlen($profile['name']) > 150) {
            $errors['name'] = 'Name is required and must be 150 characters or fewer.';
        }
        if (!filter_var($profile['email'], FILTER_VALIDATE_EMAIL) || strlen($profile['email']) > 190) {
            $errors['email'] = 'Enter a valid email address.';
        }

        return $errors;
    }

    private function validatePassword(string $currentPassword, string $newPassword, string $confirmPassword): array
    {
        $errors = [];

        if ($currentPassword === '') {
            $errors['current_password'] = 'Current password is required.';
        }
        if (strlen($newPassword) < 12) {
            $errors['new_password'] = 'New password must be at least 12 characters.';
        }
        if (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $errors['new_password'] = 'New password must include uppercase, lowercase, and number characters.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Password confirmation does not match.';
        }

        return $errors;
    }
}
