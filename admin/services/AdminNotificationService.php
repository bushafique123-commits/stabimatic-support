<?php

final class AdminNotificationService
{
    private $notifications;

    public function __construct(?AdminNotificationRepository $notifications = null)
    {
        $this->notifications = $notifications ?: new AdminNotificationRepository();
    }

    public function list(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $limit = min(50, max(10, (int) ($query['limit'] ?? 20)));
        $filters = $this->filters($query);

        try {
            $result = $this->notifications->paginateBatches($filters, $page, $limit);

            return [
                'ok' => true,
                'stats' => $this->notifications->stats(),
                'items' => $result['items'],
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
                'last_page' => (int) ceil($result['total'] / max($limit, 1)),
                'filters' => $filters,
                'form' => $this->defaults(),
                'errors' => [],
            ];
        } catch (PDOException $exception) {
            error_log('Admin notifications list failed: ' . $exception->getMessage());
            return [
                'ok' => false,
                'stats' => ['queued' => 0, 'sent' => 0, 'failed' => 0, 'cancelled' => 0, 'active_devices' => 0, 'custom_preferences' => 0],
                'items' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'last_page' => 0,
                'filters' => $filters,
                'form' => $this->defaults(),
                'errors' => [],
                'message' => 'Notifications are unavailable.',
            ];
        }
    }

    public function create(array $admin, array $input): array
    {
        $data = $this->normalize($input);
        $errors = $this->validate($data);
        if ($errors) {
            return ['ok' => false, 'form' => $data, 'errors' => $errors, 'message' => 'Validation failed.'];
        }

        try {
            $batchId = bin2hex(random_bytes(16));
            $queued = $this->notifications->createBroadcast((int) $admin['id'], $batchId, $data);
            if ($queued < 1) {
                return [
                    'ok' => false,
                    'form' => $data,
                    'errors' => ['audience' => 'No eligible recipients matched this audience, channel, and preference selection.'],
                    'message' => 'No notifications were queued.',
                ];
            }

            $description = 'Super Admin queued ' . $queued . ' ' . $data['channel'] . ' notification(s) for ' . $data['audience'] . ' audience';
            $this->notifications->logActivity((int) $admin['id'], 'ADMIN_NOTIFICATION_BROADCAST_CREATED', $description);

            return ['ok' => true, 'batch_id' => $batchId, 'queued' => $queued, 'message' => 'Notification broadcast queued.'];
        } catch (PDOException $exception) {
            error_log('Admin notification create failed: ' . $exception->getMessage());
            return ['ok' => false, 'form' => $data, 'errors' => [], 'message' => 'Notification broadcast could not be queued.'];
        }
    }

    public function detail(string $batchId): array
    {
        try {
            $batch = $this->notifications->findBatch($batchId);
            if (!$batch) {
                return ['ok' => false, 'status' => 404, 'message' => 'Notification batch not found.'];
            }

            return [
                'ok' => true,
                'batch' => $batch,
                'recipients' => $this->notifications->recipients($batchId),
            ];
        } catch (PDOException $exception) {
            error_log('Admin notification detail failed: ' . $exception->getMessage());
            return ['ok' => false, 'status' => 503, 'message' => 'Notifications are unavailable.'];
        }
    }

    public function updateStatus(array $admin, string $batchId, array $input): array
    {
        $status = (string) ($input['status'] ?? '');
        $errorMessage = trim((string) ($input['error_message'] ?? ''));
        if (!in_array($status, ['sent', 'failed', 'cancelled'], true)) {
            return ['ok' => false, 'message' => 'Invalid notification status.'];
        }
        if ($status === 'failed' && $errorMessage === '') {
            return ['ok' => false, 'message' => 'Failure reason is required.'];
        }
        if (strlen($errorMessage) > 500) {
            return ['ok' => false, 'message' => 'Failure reason is too long.'];
        }

        try {
            $updated = $this->notifications->updateQueuedStatus($batchId, $status, $errorMessage);
            $this->notifications->logActivity((int) $admin['id'], 'ADMIN_NOTIFICATION_STATUS_UPDATED', 'Super Admin marked ' . $updated . ' queued notification(s) as ' . $status);

            return ['ok' => true, 'updated' => $updated, 'message' => 'Notification status updated.'];
        } catch (PDOException $exception) {
            error_log('Admin notification status update failed: ' . $exception->getMessage());
            return ['ok' => false, 'message' => 'Notification status could not be updated.'];
        }
    }

    private function filters(array $query): array
    {
        return [
            'search' => trim((string) ($query['search'] ?? '')),
            'audience' => $this->choice($query['audience'] ?? '', ['all', 'dealer', 'installer']),
            'channel' => $this->choice($query['channel'] ?? '', ['push', 'sms', 'email']),
            'notification_type' => $this->choice($query['notification_type'] ?? '', ['general', 'sales', 'installation', 'account', 'marketing']),
            'status' => $this->choice($query['status'] ?? '', ['queued', 'sent', 'failed', 'cancelled']),
        ];
    }

    private function normalize(array $input): array
    {
        $targetUrl = trim((string) ($input['target_url'] ?? ''));
        return [
            'audience' => $this->choice($input['audience'] ?? 'all', ['all', 'dealer', 'installer'], 'all'),
            'channel' => $this->choice($input['channel'] ?? 'push', ['push', 'sms', 'email'], 'push'),
            'notification_type' => $this->choice($input['notification_type'] ?? 'general', ['general', 'sales', 'installation', 'account', 'marketing'], 'general'),
            'title' => trim((string) ($input['title'] ?? '')),
            'message' => trim((string) ($input['message'] ?? '')),
            'target_url' => $targetUrl,
            'payload_json' => $targetUrl !== '' ? json_encode(['target_url' => $targetUrl]) : null,
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if ($data['title'] === '' || strlen($data['title']) < 3 || strlen($data['title']) > 120) {
            $errors['title'] = 'Title must be between 3 and 120 characters.';
        }
        if ($data['message'] === '' || strlen($data['message']) < 5 || strlen($data['message']) > 1000) {
            $errors['message'] = 'Message must be between 5 and 1000 characters.';
        }
        if ($data['target_url'] !== '' && (strlen($data['target_url']) > 255 || strpos($data['target_url'], '/') !== 0)) {
            $errors['target_url'] = 'Target URL must start with / and be 255 characters or fewer.';
        }

        return $errors;
    }

    private function defaults(): array
    {
        return [
            'audience' => 'all',
            'channel' => 'push',
            'notification_type' => 'general',
            'title' => '',
            'message' => '',
            'target_url' => '',
        ];
    }

    private function choice($value, array $allowed, string $fallback = ''): string
    {
        $value = (string) $value;
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
