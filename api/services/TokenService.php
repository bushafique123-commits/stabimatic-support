<?php

final class TokenService
{
    private $tokens;

    public function __construct(?AuthTokenRepository $tokens = null)
    {
        $this->tokens = $tokens ?: new AuthTokenRepository();
    }

    public function issueForUser(array $user): string
    {
        $tokenId = bin2hex(random_bytes(16));
        $issuedAt = time();
        $expiresAt = $issuedAt + (int) config('jwt.expiry', 2592000);

        $token = JwtHelper::issue([
            'sub' => (int) $user['id'],
            'role' => $user['role'],
            'name' => $user['full_name'],
            'jti' => $tokenId,
        ]);

        $claims = JwtHelper::verify($token);
        $this->tokens->create((int) $user['id'], $claims['jti'], $user['role'], $claims['iat'], $claims['exp']);

        return $token;
    }

    public function isActive(array $claims): bool
    {
        return isset($claims['jti']) && $this->tokens->isActive($claims['jti']);
    }

    public function revoke(array $claims): void
    {
        if (isset($claims['jti'])) {
            $this->tokens->revoke($claims['jti']);
        }
    }
}
