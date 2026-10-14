<?php

final class RateLimitRepository extends BaseRepository
{
    public function find(string $key, string $action): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM rate_limits WHERE rate_key = :rate_key AND action = :action LIMIT 1',
            [':rate_key' => $key, ':action' => $action]
        );
    }

    public function hit(string $key, string $action, int $windowSeconds, int $maxAttempts, int $blockSeconds): array
    {
        $record = $this->find($key, $action);
        $now = time();

        if (!$record || strtotime($record['window_started_at']) + $windowSeconds <= $now) {
            $nowDateTime = date('Y-m-d H:i:s', $now);
            $this->execute(
                'INSERT INTO rate_limits (rate_key, action, attempt_count, window_started_at, blocked_until)
                 VALUES (:rate_key, :action, 1, :window_started_at, NULL)
                 ON DUPLICATE KEY UPDATE attempt_count = 1, window_started_at = VALUES(window_started_at), blocked_until = NULL',
                [
                    ':rate_key' => $key,
                    ':action' => $action,
                    ':window_started_at' => $nowDateTime,
                ]
            );
            return ['allowed' => true, 'remaining' => max(0, $maxAttempts - 1), 'blocked_until' => null];
        }

        if ($record['blocked_until'] && strtotime($record['blocked_until']) > $now) {
            return ['allowed' => false, 'remaining' => 0, 'blocked_until' => $record['blocked_until']];
        }

        $attempts = (int) $record['attempt_count'] + 1;
        $blockedUntil = $attempts > $maxAttempts ? date('Y-m-d H:i:s', $now + $blockSeconds) : null;

        $this->execute(
            'UPDATE rate_limits
             SET attempt_count = :attempt_count, blocked_until = :blocked_until
             WHERE rate_key = :rate_key AND action = :action',
            [
                ':attempt_count' => $attempts,
                ':blocked_until' => $blockedUntil,
                ':rate_key' => $key,
                ':action' => $action,
            ]
        );

        return [
            'allowed' => $blockedUntil === null,
            'remaining' => max(0, $maxAttempts - $attempts),
            'blocked_until' => $blockedUntil,
        ];
    }

    public function clear(string $key, string $action): void
    {
        $this->execute(
            'DELETE FROM rate_limits WHERE rate_key = :rate_key AND action = :action',
            [':rate_key' => $key, ':action' => $action]
        );
    }
}
