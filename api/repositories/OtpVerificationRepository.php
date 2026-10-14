<?php

final class OtpVerificationRepository extends BaseRepository
{
    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO otp_verifications (user_id, mobile, email, otp, type, purpose, expires_at, created_at)
             VALUES (:user_id, :mobile, :email, :otp, :type, :purpose, :expires_at, :created_at)',
            [
                ':user_id' => $data['user_id'] ?? null,
                ':mobile' => $data['mobile'] ?? null,
                ':email' => $data['email'] ?? null,
                ':otp' => $data['otp'],
                ':type' => $data['type'],
                ':purpose' => $data['purpose'],
                ':expires_at' => $data['expires_at'],
                ':created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
            ]
        );

        return $this->lastInsertId();
    }

    public function latestPending(array $criteria): ?array
    {
        $where = ['type = :type', 'purpose = :purpose', 'verified_at IS NULL'];
        $params = [
            ':type' => $criteria['type'],
            ':purpose' => $criteria['purpose'],
        ];

        if (isset($criteria['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params[':user_id'] = $criteria['user_id'];
        }
        if (isset($criteria['mobile'])) {
            $where[] = 'mobile = :mobile';
            $params[':mobile'] = $criteria['mobile'];
        }
        if (isset($criteria['email'])) {
            $where[] = 'email = :email';
            $params[':email'] = $criteria['email'];
        }

        return $this->fetchOne(
            'SELECT * FROM otp_verifications WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT 1',
            $params
        );
    }

    public function latest(array $criteria): ?array
    {
        $where = ['type = :type', 'purpose = :purpose'];
        $params = [
            ':type' => $criteria['type'],
            ':purpose' => $criteria['purpose'],
        ];

        if (isset($criteria['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params[':user_id'] = $criteria['user_id'];
        }
        if (isset($criteria['mobile'])) {
            $where[] = 'mobile = :mobile';
            $params[':mobile'] = $criteria['mobile'];
        }
        if (isset($criteria['email'])) {
            $where[] = 'email = :email';
            $params[':email'] = $criteria['email'];
        }

        return $this->fetchOne(
            'SELECT * FROM otp_verifications WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT 1',
            $params
        );
    }

    public function findVerifiedReset(int $id, int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT *
             FROM otp_verifications
             WHERE id = :id
               AND user_id = :user_id
               AND purpose = :purpose
               AND verified_at IS NOT NULL
               AND expires_at > NOW()
             LIMIT 1',
            [
                ':id' => $id,
                ':user_id' => $userId,
                ':purpose' => OTP_PURPOSE_FORGOT_PASSWORD,
            ]
        );
    }

    public function incrementAttempts(int $id): void
    {
        $this->execute(
            'UPDATE otp_verifications SET attempt_count = attempt_count + 1 WHERE id = :id',
            [':id' => $id]
        );
    }

    public function markVerified(int $id): void
    {
        $this->execute(
            'UPDATE otp_verifications SET verified_at = NOW() WHERE id = :id',
            [':id' => $id]
        );
    }

    public function markConsumed(int $id): void
    {
        $this->execute(
            'UPDATE otp_verifications SET attempt_count = :attempt_count WHERE id = :id',
            [
                ':id' => $id,
                ':attempt_count' => (int) config('otp.max_attempts'),
            ]
        );
    }

    public function deleteExpired(): int
    {
        $statement = $this->connection()->prepare('DELETE FROM otp_verifications WHERE expires_at < NOW() AND verified_at IS NULL');
        $statement->execute();
        return $statement->rowCount();
    }
}
