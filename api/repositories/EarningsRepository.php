<?php

final class EarningsRepository extends BaseRepository
{
    public function summaryForUser(int $userId): array
    {
        $earnings = $this->fetchAll(
            "SELECT status, COUNT(*) AS total, COALESCE(SUM(amount), 0) AS amount
             FROM commission_earnings
             WHERE user_id = :user_id
             GROUP BY status",
            [':user_id' => $userId]
        );

        $summary = [
            'pending_count' => 0,
            'pending_amount' => 0.0,
            'approved_count' => 0,
            'approved_amount' => 0.0,
            'paid_count' => 0,
            'paid_amount' => 0.0,
            'cancelled_count' => 0,
            'cancelled_amount' => 0.0,
            'lifetime_amount' => 0.0,
        ];

        foreach ($earnings as $row) {
            $status = $row['status'];
            $summary[$status . '_count'] = (int) $row['total'];
            $summary[$status . '_amount'] = (float) $row['amount'];
            if ($status !== 'cancelled') {
                $summary['lifetime_amount'] += (float) $row['amount'];
            }
        }

        $payouts = $this->fetchAll(
            "SELECT status, COUNT(*) AS total, COALESCE(SUM(amount), 0) AS amount
             FROM commission_payouts
             WHERE user_id = :user_id
             GROUP BY status",
            [':user_id' => $userId]
        );

        $summary['pending_payout_count'] = 0;
        $summary['pending_payout_amount'] = 0.0;
        $summary['paid_payout_count'] = 0;
        $summary['paid_payout_amount'] = 0.0;
        foreach ($payouts as $row) {
            if ($row['status'] === 'pending' || $row['status'] === 'paid') {
                $summary[$row['status'] . '_payout_count'] = (int) $row['total'];
                $summary[$row['status'] . '_payout_amount'] = (float) $row['amount'];
            }
        }

        return $summary;
    }

    public function paginateEarningsForUser(int $userId, array $filters, int $page, int $limit): array
    {
        $where = ['ce.user_id = :user_id'];
        $params = [':user_id' => $userId];

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'ce.status = :status';
            $params[':status'] = $filters['status'];
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) $this->fetchOne(
            "SELECT COUNT(*) AS total
             FROM commission_earnings ce
             WHERE {$whereSql}",
            $params
        )['total'];

        $offset = Paginator::offset($page, $limit);
        $items = $this->fetchAll(
            "SELECT ce.id,
                    ce.source_type,
                    ce.source_id,
                    ce.amount,
                    ce.currency,
                    ce.status,
                    ce.payout_id,
                    ce.earned_at,
                    ce.approved_at,
                    ce.paid_at,
                    ce.created_at,
                    COALESCE(s.invoice_number, i.serial_number) AS source_label
             FROM commission_earnings ce
             LEFT JOIN sales s ON ce.source_type = 'sale' AND s.id = ce.source_id
             LEFT JOIN installations i ON ce.source_type = 'installation' AND i.id = ce.source_id
             WHERE {$whereSql}
             ORDER BY ce.earned_at DESC, ce.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        return ['total' => $total, 'items' => $items];
    }

    public function paginatePayoutsForUser(int $userId, array $filters, int $page, int $limit): array
    {
        $where = ['cp.user_id = :user_id'];
        $params = [':user_id' => $userId];

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'cp.status = :status';
            $params[':status'] = $filters['status'];
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) $this->fetchOne(
            "SELECT COUNT(*) AS total
             FROM commission_payouts cp
             WHERE {$whereSql}",
            $params
        )['total'];

        $offset = Paginator::offset($page, $limit);
        $items = $this->fetchAll(
            "SELECT cp.id,
                    cp.amount,
                    cp.status,
                    cp.payment_reference,
                    cp.notes,
                    cp.paid_at,
                    cp.created_at,
                    cp.updated_at,
                    COUNT(ce.id) AS earning_count
             FROM commission_payouts cp
             LEFT JOIN commission_earnings ce ON ce.payout_id = cp.id
             WHERE {$whereSql}
             GROUP BY cp.id
             ORDER BY cp.created_at DESC, cp.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        return ['total' => $total, 'items' => $items];
    }
}
