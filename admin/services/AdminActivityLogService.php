<?php

final class AdminActivityLogService
{
    private $activityLogs;

    public function __construct(?AdminActivityLogRepository $activityLogs = null)
    {
        $this->activityLogs = $activityLogs ?: new AdminActivityLogRepository();
    }

    public function list(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $limit = min(50, max(10, (int) ($query['limit'] ?? 20)));
        $filters = $this->filters($query);

        try {
            $result = $this->activityLogs->paginate($filters, $page, $limit);

            return [
                'ok' => true,
                'items' => array_map([$this, 'enrich'], $result['items']),
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
                'last_page' => (int) ceil($result['total'] / max($limit, 1)),
                'filters' => $filters,
                'modules' => $this->activityLogs->modules(),
                'actions' => $this->activityLogs->actions(),
            ];
        } catch (PDOException $exception) {
            error_log('Admin activity log list failed: ' . $exception->getMessage());
            return $this->unavailable($page, $limit, $filters);
        }
    }

    public function detail(int $activityLogId): array
    {
        try {
            $activity = $this->activityLogs->find($activityLogId);
            if (!$activity) {
                return ['ok' => false, 'status' => 404, 'message' => 'Activity log not found'];
            }

            return [
                'ok' => true,
                'activity' => $this->enrich($activity),
            ];
        } catch (PDOException $exception) {
            error_log('Admin activity log detail failed: ' . $exception->getMessage());
            return ['ok' => false, 'status' => 503, 'message' => 'Activity logs are unavailable.'];
        }
    }

    private function filters(array $query): array
    {
        return [
            'search' => trim((string) ($query['search'] ?? '')),
            'user_type' => in_array(($query['user_type'] ?? ''), ['admin', 'dealer', 'installer'], true) ? $query['user_type'] : '',
            'module' => trim((string) ($query['module'] ?? '')),
            'action' => trim((string) ($query['action'] ?? '')),
            'date_from' => $this->date($query['date_from'] ?? ''),
            'date_to' => $this->date($query['date_to'] ?? ''),
        ];
    }

    private function date($value): string
    {
        $value = trim((string) $value);
        return preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value) ? $value : '';
    }

    private function enrich(array $activity): array
    {
        $activity['actor_label'] = trim((string) ($activity['actor_name'] ?? '')) !== ''
            ? $activity['actor_name']
            : ucfirst($activity['user_type']) . ' #' . (int) $activity['user_id'];
        $activity['record_url'] = $this->recordUrl($activity['module'] ?? '', (int) ($activity['record_id'] ?? 0));

        return $activity;
    }

    private function recordUrl(string $module, int $recordId): string
    {
        if ($recordId <= 0) {
            return '';
        }

        $routes = [
            'dealers' => '/admin/dealers/',
            'installers' => '/admin/installers/',
            'sales' => '/admin/sales/',
            'installations' => '/admin/installations/',
        ];

        return isset($routes[$module]) ? $routes[$module] . $recordId : '';
    }

    private function unavailable(int $page, int $limit, array $filters): array
    {
        return [
            'ok' => false,
            'items' => [],
            'total' => 0,
            'page' => $page,
            'limit' => $limit,
            'last_page' => 0,
            'filters' => $filters,
            'modules' => [],
            'actions' => [],
            'message' => 'Activity logs are unavailable.',
        ];
    }
}
