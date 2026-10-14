<?php

final class AuthTokenRepository extends BaseRepository
{
    public function create(int $userId, string $tokenId, string $role, int $issuedAt, int $expiresAt): void
    {
        $this->execute(
            'INSERT INTO auth_tokens (user_id, token_id, role, issued_at, expires_at)
             VALUES (:user_id, :token_id, :role, :issued_at, :expires_at)',
            [
                ':user_id' => $userId,
                ':token_id' => $tokenId,
                ':role' => $role,
                ':issued_at' => date('Y-m-d H:i:s', $issuedAt),
                ':expires_at' => date('Y-m-d H:i:s', $expiresAt),
            ]
        );
    }

    public function isActive(string $tokenId): bool
    {
        return $this->fetchOne(
            'SELECT id FROM auth_tokens
             WHERE token_id = :token_id AND revoked_at IS NULL AND expires_at > NOW()
             LIMIT 1',
            [':token_id' => $tokenId]
        ) !== null;
    }

    public function revoke(string $tokenId): void
    {
        $this->execute(
            'UPDATE auth_tokens SET revoked_at = NOW() WHERE token_id = :token_id AND revoked_at IS NULL',
            [':token_id' => $tokenId]
        );
    }

    public function revokeUserTokens(int $userId): void
    {
        $this->execute(
            'UPDATE auth_tokens SET revoked_at = NOW() WHERE user_id = :user_id AND revoked_at IS NULL',
            [':user_id' => $userId]
        );
    }
}
