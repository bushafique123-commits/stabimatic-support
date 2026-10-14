<?php

abstract class BaseRepository
{
    protected $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $statement = $this->connection()->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();
        return $row ?: null;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->connection()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    protected function execute(string $sql, array $params = []): bool
    {
        $statement = $this->connection()->prepare($sql);
        return $statement->execute($params);
    }

    protected function lastInsertId(): int
    {
        return (int) $this->connection()->lastInsertId();
    }

    protected function connection(): PDO
    {
        if (!$this->pdo instanceof PDO) {
            $this->pdo = db();
        }
        return $this->pdo;
    }
}
