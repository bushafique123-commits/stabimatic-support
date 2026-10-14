<?php

final class SalesRepository extends BaseRepository
{
    public function paginateForDealer(int $dealerId, array $filters, int $page, int $limit): array
    {
        $where = ['dealer_id = :dealer_id', 'deleted_at IS NULL'];
        $params = [':dealer_id' => $dealerId];

        if (($filters['search'] ?? '') !== '') {
            $where[] = '(invoice_number LIKE :search OR customer_name LIKE :search OR customer_mobile LIKE :search OR device_model LIKE :search OR device_serial LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) $this->fetchOne(
            "SELECT COUNT(*) AS total FROM sales WHERE {$whereSql}",
            $params
        )['total'];

        $offset = Paginator::offset($page, $limit);
        $items = $this->fetchAll(
            "SELECT id, invoice_number, sale_date, customer_name, customer_mobile, device_model, device_serial, created_at, updated_at
             FROM sales
             WHERE {$whereSql}
             ORDER BY sale_date DESC, id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        return ['total' => $total, 'items' => $items];
    }

    public function findForDealer(int $dealerId, int $saleId): ?array
    {
        return $this->fetchOne(
            'SELECT id, invoice_number, sale_date, customer_name, customer_mobile, device_model, device_serial, created_at, updated_at
             FROM sales
             WHERE id = :id AND dealer_id = :dealer_id AND deleted_at IS NULL
             LIMIT 1',
            [':id' => $saleId, ':dealer_id' => $dealerId]
        );
    }

    public function activeSerialExists(string $serial, ?int $exceptSaleId = null): bool
    {
        $sql = 'SELECT id FROM sales WHERE device_serial = :device_serial AND deleted_at IS NULL';
        $params = [':device_serial' => $serial];

        if ($exceptSaleId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $exceptSaleId;
        }

        $sql .= ' LIMIT 1';
        return $this->fetchOne($sql, $params) !== null;
    }

    public function findActiveBySerial(string $serial): ?array
    {
        return $this->fetchOne(
            'SELECT s.id,
                    s.invoice_number,
                    s.sale_date,
                    s.customer_name,
                    s.customer_mobile,
                    s.device_model,
                    s.device_serial,
                    s.created_at,
                    u.full_name AS dealer_name,
                    u.mobile AS dealer_mobile
             FROM sales s
             INNER JOIN users u ON u.id = s.dealer_id
             WHERE s.device_serial = :device_serial
               AND s.deleted_at IS NULL
             LIMIT 1',
            [':device_serial' => $serial]
        );
    }

    public function findActiveByInvoiceModel(string $invoiceNumber, string $deviceModel): ?array
    {
        return $this->fetchOne(
            'SELECT s.id,
                    s.invoice_number,
                    s.sale_date,
                    s.customer_name,
                    s.customer_mobile,
                    s.device_model,
                    s.device_serial,
                    s.created_at,
                    u.full_name AS dealer_name,
                    u.mobile AS dealer_mobile
             FROM sales s
             INNER JOIN users u ON u.id = s.dealer_id
             WHERE s.invoice_number = :invoice_number
               AND s.device_model = :device_model
               AND s.deleted_at IS NULL
             LIMIT 1',
            [':invoice_number' => $invoiceNumber, ':device_model' => $deviceModel]
        );
    }

    public function findActiveBySerialModel(string $serial, string $deviceModel): ?array
    {
        return $this->fetchOne(
            'SELECT s.id,
                    s.invoice_number,
                    s.sale_date,
                    s.customer_name,
                    s.customer_mobile,
                    s.device_model,
                    s.device_serial,
                    s.created_at,
                    u.full_name AS dealer_name,
                    u.mobile AS dealer_mobile
             FROM sales s
             INNER JOIN users u ON u.id = s.dealer_id
             WHERE s.device_serial = :device_serial
               AND s.device_model = :device_model
               AND s.deleted_at IS NULL
             LIMIT 1',
            [':device_serial' => $serial, ':device_model' => $deviceModel]
        );
    }

    public function create(int $dealerId, array $data): int
    {
        $this->execute(
            'INSERT INTO sales (dealer_id, invoice_number, sale_date, customer_name, customer_mobile, device_model, device_serial)
             VALUES (:dealer_id, :invoice_number, :sale_date, :customer_name, :customer_mobile, :device_model, :device_serial)',
            [
                ':dealer_id' => $dealerId,
                ':invoice_number' => $data['invoice_number'],
                ':sale_date' => $data['sale_date'],
                ':customer_name' => $data['customer_name'],
                ':customer_mobile' => $data['customer_mobile'],
                ':device_model' => $data['device_model'],
                ':device_serial' => $data['device_serial'],
            ]
        );

        return $this->lastInsertId();
    }

    public function update(int $dealerId, int $saleId, array $data): bool
    {
        return $this->execute(
            'UPDATE sales
             SET invoice_number = :invoice_number,
                 sale_date = :sale_date,
                 customer_name = :customer_name,
                 customer_mobile = :customer_mobile,
                 device_model = :device_model,
                 device_serial = :device_serial
             WHERE id = :id AND dealer_id = :dealer_id AND deleted_at IS NULL',
            [
                ':id' => $saleId,
                ':dealer_id' => $dealerId,
                ':invoice_number' => $data['invoice_number'],
                ':sale_date' => $data['sale_date'],
                ':customer_name' => $data['customer_name'],
                ':customer_mobile' => $data['customer_mobile'],
                ':device_model' => $data['device_model'],
                ':device_serial' => $data['device_serial'],
            ]
        );
    }

    public function softDelete(int $dealerId, int $saleId): bool
    {
        return $this->execute(
            'UPDATE sales SET deleted_at = NOW() WHERE id = :id AND dealer_id = :dealer_id AND deleted_at IS NULL',
            [':id' => $saleId, ':dealer_id' => $dealerId]
        );
    }

    public function countForDealer(int $dealerId): int
    {
        return (int) $this->fetchOne(
            'SELECT COUNT(*) AS total FROM sales WHERE dealer_id = :dealer_id AND deleted_at IS NULL',
            [':dealer_id' => $dealerId]
        )['total'];
    }

    public function countForDealerThisMonth(int $dealerId): int
    {
        return (int) $this->fetchOne(
            'SELECT COUNT(*) AS total
             FROM sales
             WHERE dealer_id = :dealer_id
               AND deleted_at IS NULL
               AND sale_date >= DATE_FORMAT(CURDATE(), "%Y-%m-01")
               AND sale_date < DATE_ADD(LAST_DAY(CURDATE()), INTERVAL 1 DAY)',
            [':dealer_id' => $dealerId]
        )['total'];
    }

    public function countForDealerToday(int $dealerId): int
    {
        return (int) $this->fetchOne(
            'SELECT COUNT(*) AS total
             FROM sales
             WHERE dealer_id = :dealer_id
               AND deleted_at IS NULL
               AND sale_date = CURDATE()',
            [':dealer_id' => $dealerId]
        )['total'];
    }

    public function recentForDealer(int $dealerId, int $limit = 5): array
    {
        $limit = max(1, min($limit, 10));

        return $this->fetchAll(
            "SELECT id, invoice_number, sale_date, customer_name, customer_mobile, device_model, device_serial, created_at
             FROM sales
             WHERE dealer_id = :dealer_id
               AND deleted_at IS NULL
             ORDER BY sale_date DESC, id DESC
             LIMIT {$limit}",
            [':dealer_id' => $dealerId]
        );
    }
}
