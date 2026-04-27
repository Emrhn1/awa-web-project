<?php

require_once __DIR__ . '/../Config/database.php';

class Accident {

    public static function getAll(array $filters = []): array {
        $pdo = getDB();
        $sql = 'SELECT * FROM accidents WHERE 1=1';
        $params = [];

        if (!empty($filters['date'])) {
            $sql .= ' AND date = :date';
            $params[':date'] = $filters['date'];
        }
        if (!empty($filters['city'])) {
            $sql .= ' AND city = :city';
            $params[':city'] = $filters['city'];
        }
        if (!empty($filters['severity'])) {
            $sql .= ' AND severity = :severity';
            $params[':severity'] = $filters['severity'];
        }

        $sql .= ' ORDER BY date DESC, time DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getById(int $id): array|false {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT * FROM accidents WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
}
