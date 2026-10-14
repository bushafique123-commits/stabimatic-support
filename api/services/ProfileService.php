<?php

final class ProfileService
{
    private $users;

    public function __construct(?UserRepository $users = null)
    {
        $this->users = $users ?: new UserRepository();
    }

    public function current(array $claims): array
    {
        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            return ['ok' => true, 'profile' => $this->profile($user)];
        } catch (PDOException $exception) {
            Logger::error('Profile load unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function update(array $claims, array $input): array
    {
        $data = $this->normalize($input);
        $errors = Validator::validate($data, [
            'full_name' => ['required', 'min:3', 'max:150'],
            'cnic' => ['required', 'cnic', 'max:20'],
            'bank_name' => ['required', 'max:150'],
            'bank_account_title' => ['required', 'max:150'],
            'bank_account_number' => ['required', 'max:50'],
            'iban' => ['required', 'iban', 'max:34'],
        ]);

        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            if ($this->users->existsByField('cnic', $data['cnic'], (int) $user['id'])) {
                return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => ['cnic' => 'CNIC is already registered']];
            }

            $this->users->updateProfile((int) $user['id'], $data);
            Logger::activity($user['role'], (int) $user['id'], strtoupper($user['role']) . '_PROFILE_UPDATED', 'profile', (int) $user['id'], 'User updated profile');

            $updated = $this->users->findById((int) $user['id']);
            return ['ok' => true, 'profile' => $this->profile($updated ?: $user)];
        } catch (PDOException $exception) {
            Logger::error('Profile update unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    public function changePassword(array $claims, array $input): array
    {
        $data = [
            'current_password' => (string) ($input['current_password'] ?? ''),
            'new_password' => (string) ($input['new_password'] ?? ''),
            'confirm_password' => (string) ($input['confirm_password'] ?? ''),
        ];
        $errors = Validator::validate($data, [
            'current_password' => ['required'],
            'new_password' => ['required', 'strong_password'],
            'confirm_password' => ['required', 'same:new_password'],
        ]);

        if ($errors) {
            return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => $errors];
        }

        try {
            $user = $this->activeUser($claims);
            if (!$user) {
                return $this->forbidden();
            }

            if (!password_verify($data['current_password'], $user['password_hash'])) {
                return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => ['current_password' => 'Current password is incorrect']];
            }

            if (password_verify($data['new_password'], $user['password_hash'])) {
                return ['ok' => false, 'status' => 422, 'message' => 'Validation failed', 'errors' => ['new_password' => 'New password must be different from current password']];
            }

            $this->users->updatePassword((int) $user['id'], password_hash($data['new_password'], PASSWORD_DEFAULT));
            Logger::activity($user['role'], (int) $user['id'], strtoupper($user['role']) . '_PASSWORD_CHANGED', 'profile', (int) $user['id'], 'User changed password');

            return ['ok' => true];
        } catch (PDOException $exception) {
            Logger::error('Password change unavailable', ['detail' => $exception->getMessage()]);
            return $this->unavailable();
        }
    }

    private function normalize(array $input): array
    {
        return [
            'full_name' => InputNormalizer::string($input['full_name'] ?? ''),
            'cnic' => InputNormalizer::cnic($input['cnic'] ?? ''),
            'bank_name' => InputNormalizer::string($input['bank_name'] ?? ''),
            'bank_account_title' => InputNormalizer::string($input['bank_account_title'] ?? ''),
            'bank_account_number' => InputNormalizer::string($input['bank_account_number'] ?? ''),
            'iban' => strtoupper(str_replace(' ', '', InputNormalizer::string($input['iban'] ?? ''))),
        ];
    }

    private function activeUser(array $claims): ?array
    {
        $user = $this->users->findById((int) ($claims['sub'] ?? 0));
        if (!$user || !in_array($user['role'], [ROLE_DEALER, ROLE_INSTALLER], true) || !(int) $user['is_active']) {
            return null;
        }

        return $user;
    }

    private function profile(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'role' => $user['role'],
            'full_name' => $user['full_name'],
            'mobile' => $user['mobile'],
            'email' => $user['email'],
            'cnic' => $user['cnic'] ?? '',
            'bank_name' => $user['bank_name'],
            'bank_account_title' => $user['bank_account_title'],
            'bank_account_number' => $user['bank_account_number'],
            'iban' => $user['iban'],
            'mobile_verified_at' => $user['mobile_verified_at'],
            'email_verified_at' => $user['email_verified_at'],
            'is_verified' => (bool) $user['is_verified'],
            'is_active' => (bool) $user['is_active'],
            'created_at' => $user['created_at'],
            'updated_at' => $user['updated_at'],
        ];
    }

    private function forbidden(): array
    {
        return ['ok' => false, 'status' => 403, 'message' => 'Profile is not available for this account', 'errors' => ['profile' => 'Profile is not available']];
    }

    private function unavailable(): array
    {
        return ['ok' => false, 'status' => 503, 'message' => 'Profile service is unavailable', 'errors' => ['profile' => 'Please try again later']];
    }
}
