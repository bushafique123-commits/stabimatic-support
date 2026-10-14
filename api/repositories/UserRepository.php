<?php

final class UserRepository extends BaseRepository
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM users WHERE id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    public function findByIdentifier(string $identifier): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM users WHERE mobile = :mobile_identifier OR email = :email_identifier LIMIT 1',
            [
                ':mobile_identifier' => $identifier,
                ':email_identifier' => $identifier,
            ]
        );
    }

    public function existsByField(string $field, string $value, ?int $exceptUserId = null): bool
    {
        $allowedFields = ['mobile', 'email', 'cnic'];
        if (!in_array($field, $allowedFields, true)) {
            throw new InvalidArgumentException('Invalid unique field');
        }

        $sql = "SELECT id FROM users WHERE {$field} = :value";
        $params = [':value' => $value];
        if ($exceptUserId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $exceptUserId;
        }
        $sql .= ' LIMIT 1';

        return $this->fetchOne($sql, $params) !== null;
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO users
             (role, full_name, mobile, email, cnic, bank_name, bank_account_title, bank_account_number, iban, password_hash)
             VALUES
             (:role, :full_name, :mobile, :email, :cnic, :bank_name, :bank_account_title, :bank_account_number, :iban, :password_hash)',
            [
                ':role' => $data['role'],
                ':full_name' => $data['full_name'],
                ':mobile' => $data['mobile'],
                ':email' => $data['email'],
                ':cnic' => $data['cnic'] ?? null,
                ':bank_name' => $data['bank_name'],
                ':bank_account_title' => $data['bank_account_title'],
                ':bank_account_number' => $data['bank_account_number'],
                ':iban' => $data['iban'],
                ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            ]
        );

        return $this->lastInsertId();
    }

    public function markMobileVerified(int $userId): void
    {
        $this->execute(
            'UPDATE users SET mobile_verified_at = NOW() WHERE id = :id',
            [':id' => $userId]
        );
    }

    public function findPendingMobileVerification(int $userId, string $mobile): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM users
             WHERE id = :id
               AND mobile = :mobile
               AND role IN ('dealer', 'installer')
               AND is_active = 1
             LIMIT 1",
            [':id' => $userId, ':mobile' => $mobile]
        );
    }

    public function markEmailVerified(int $userId): void
    {
        $this->execute(
            'UPDATE users
             SET email_verified_at = NOW(),
                 is_verified = CASE WHEN mobile_verified_at IS NOT NULL THEN 1 ELSE is_verified END
             WHERE id = :id',
            [':id' => $userId]
        );
    }

    public function markAccountVerified(int $userId): void
    {
        $this->execute(
            'UPDATE users
             SET is_verified = 1,
                 updated_at = NOW()
             WHERE id = :id
               AND mobile_verified_at IS NOT NULL
               AND email_verified_at IS NOT NULL',
            [':id' => $userId]
        );
    }

    public function findPendingEmailVerification(int $userId, string $email): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM users
             WHERE id = :id
               AND email = :email
               AND role IN ('dealer', 'installer')
               AND is_active = 1
             LIMIT 1",
            [':id' => $userId, ':email' => $email]
        );
    }

    public function updateProfile(int $userId, array $data): void
    {
        $this->execute(
            'UPDATE users
             SET full_name = :full_name,
                 cnic = :cnic,
                 bank_name = :bank_name,
                 bank_account_title = :bank_account_title,
                 bank_account_number = :bank_account_number,
                 iban = :iban,
                 updated_at = NOW()
             WHERE id = :id',
            [
                ':id' => $userId,
                ':full_name' => $data['full_name'],
                ':cnic' => $data['cnic'] ?: null,
                ':bank_name' => $data['bank_name'],
                ':bank_account_title' => $data['bank_account_title'],
                ':bank_account_number' => $data['bank_account_number'],
                ':iban' => $data['iban'],
            ]
        );
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $this->execute(
            'UPDATE users
             SET password_hash = :password_hash, updated_at = NOW()
             WHERE id = :id',
            [
                ':id' => $userId,
                ':password_hash' => $passwordHash,
            ]
        );
    }

    public function updateMobile(int $userId, string $mobile): void
    {
        $this->execute(
            'UPDATE users
             SET mobile = :mobile,
                 mobile_verified_at = NOW(),
                 is_verified = CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE is_verified END,
                 updated_at = NOW()
             WHERE id = :id',
            [
                ':id' => $userId,
                ':mobile' => $mobile,
            ]
        );
    }

    public function updateEmail(int $userId, string $email): void
    {
        $this->execute(
            'UPDATE users
             SET email = :email,
                 email_verified_at = NOW(),
                 is_verified = CASE WHEN mobile_verified_at IS NOT NULL THEN 1 ELSE is_verified END,
                 updated_at = NOW()
             WHERE id = :id',
            [
                ':id' => $userId,
                ':email' => $email,
            ]
        );
    }
}
