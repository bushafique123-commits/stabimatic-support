<?php

final class AdminUserRepository
{
    public function findByEmail(string $email): ?array
    {
        $statement = admin_db()->prepare(
            'SELECT id, name, email, password_hash, is_active, last_login_at, created_at
             FROM admin_users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute([':email' => $email]);
        $admin = $statement->fetch();

        return $admin ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = admin_db()->prepare(
            'SELECT id, name, email, is_active, last_login_at, created_at
             FROM admin_users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute([':id' => $id]);
        $admin = $statement->fetch();

        return $admin ?: null;
    }

    public function findForPasswordChange(int $id): ?array
    {
        $statement = admin_db()->prepare(
            'SELECT id, name, email, password_hash, is_active, last_login_at, created_at
             FROM admin_users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute([':id' => $id]);
        $admin = $statement->fetch();

        return $admin ?: null;
    }

    public function emailExistsForAnotherAdmin(string $email, int $adminId): bool
    {
        $statement = admin_db()->prepare(
            'SELECT COUNT(*)
             FROM admin_users
             WHERE email = :email AND id <> :id'
        );
        $statement->execute([':email' => $email, ':id' => $adminId]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function updateProfile(int $id, string $name, string $email): void
    {
        $statement = admin_db()->prepare(
            'UPDATE admin_users
             SET name = :name, email = :email, updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            ':id' => $id,
            ':name' => $name,
            ':email' => $email,
        ]);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $statement = admin_db()->prepare(
            'UPDATE admin_users
             SET password_hash = :password_hash, updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            ':id' => $id,
            ':password_hash' => $passwordHash,
        ]);
    }

    public function touchLastLogin(int $id): void
    {
        $statement = admin_db()->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = :id');
        $statement->execute([':id' => $id]);
    }

    public function logActivity(int $adminId, string $action, string $description, string $module = 'admin_auth'): void
    {
        $statement = admin_db()->prepare(
            'INSERT INTO activity_logs (user_type, user_id, action, module, description, ip_address, user_agent)
             VALUES ("admin", :user_id, :action, :module, :description, :ip_address, :user_agent)'
        );
        $statement->execute([
            ':user_id' => $adminId,
            ':action' => $action,
            ':module' => $module,
            ':description' => $description,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }
}
