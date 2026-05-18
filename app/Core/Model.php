<?php

abstract class Model
{
    protected mysqli $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    protected function all(string $sql, string $types = '', array $params = []): array
    {
        $stmt = $this->stmt($sql, $types, $params);
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    protected function one(string $sql, string $types = '', array $params = []): ?array
    {
        $stmt = $this->stmt($sql, $types, $params);
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    protected function exists(string $sql, string $types = '', array $params = []): bool
    {
        return $this->one($sql, $types, $params) !== null;
    }

    protected function insert(string $sql, string $types, array $params): int
    {
        $stmt = $this->stmt($sql, $types, $params);
        $id = (int) $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    protected function run(string $sql, string $types = '', array $params = []): void
    {
        $stmt = $this->stmt($sql, $types, $params);
        $stmt->close();
    }

    protected function stmt(string $sql, string $types = '', array $params = []): mysqli_stmt
    {
        $stmt = $this->db->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt;
    }
}
