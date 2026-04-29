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

    public static function create(array $data): int {
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'INSERT INTO accidents (date, time, latitude, longitude, city, district, severity, vehicle_count, injury_count, death_count, description)
             VALUES (:date, :time, :latitude, :longitude, :city, :district, :severity, :vehicle_count, :injury_count, :death_count, :description)'
        );
        $stmt->execute([
            ':date'          => $data['date'],
            ':time'          => $data['time'],
            ':latitude'      => $data['latitude'],
            ':longitude'     => $data['longitude'],
            ':city'          => $data['city'],
            ':district'      => $data['district'] ?? null,
            ':severity'      => $data['severity'],
            ':vehicle_count' => $data['vehicle_count'] ?? null,
            ':injury_count'  => $data['injury_count'] ?? null,
            ':death_count'   => $data['death_count'] ?? null,
            ':description'   => $data['description'] ?? null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void {
        $pdo = getDB();

        $allowed = ['date', 'time', 'latitude', 'longitude', 'city', 'district', 'severity', 'vehicle_count', 'injury_count', 'death_count', 'description'];
        $sets = [];
        $params = [':id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($sets)) return;

        $stmt = $pdo->prepare('UPDATE accidents SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute($params);
    }

    public static function delete(int $id): void {
        $pdo = getDB();
        $stmt = $pdo->prepare('DELETE FROM accidents WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
