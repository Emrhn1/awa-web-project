<?php

function list_nominations(): void {
    $where = [];
    $params = [];

    $year = v_int($_GET['year'] ?? null);
    if ($year !== null) {
        $where[] = 'e.year = ?';
        $params[] = $year;
    }

    $category = v_str($_GET['category'] ?? null);
    if ($category !== null) {
        $where[] = 'c.name = ?';
        $params[] = $category;
    }

    $actorId = v_int($_GET['actor_id'] ?? null);
    if ($actorId !== null) {
        $where[] = 'a.id = ?';
        $params[] = $actorId;
    }

    $filmId = v_int($_GET['film_id'] ?? null);
    if ($filmId !== null) {
        $where[] = 'f.id = ?';
        $params[] = $filmId;
    }

    $winner = v_bool01($_GET['winner'] ?? null);
    if ($winner !== null) {
        $where[] = 'n.is_winner = ?';
        $params[] = $winner;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT
            n.id,
            n.is_winner,
            e.year,
            c.name  AS category,
            a.id    AS actor_id,
            a.name  AS actor,
            f.id    AS film_id,
            f.title AS film,
            f.year  AS film_year
        FROM nominations n
        JOIN awards_editions e ON e.id = n.edition_id
        JOIN categories      c ON c.id = n.category_id
        JOIN actors          a ON a.id = n.actor_id
        JOIN films           f ON f.id = n.film_id
        $whereSql
        ORDER BY e.year DESC, c.name ASC, n.is_winner DESC, a.name ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    json_response($stmt->fetchAll());
}

function get_nomination(int $id): void {
    $sql = "
        SELECT
            n.id, n.is_winner,
            e.year,
            c.name  AS category,
            a.id    AS actor_id, a.name AS actor,
            f.id    AS film_id,  f.title AS film, f.year AS film_year
        FROM nominations n
        JOIN awards_editions e ON e.id = n.edition_id
        JOIN categories      c ON c.id = n.category_id
        JOIN actors          a ON a.id = n.actor_id
        JOIN films           f ON f.id = n.film_id
        WHERE n.id = ?
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) { not_found('Nomination not found'); return; }
    json_response($row);
}

function _edition_id_for_year(int $year): int {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM awards_editions WHERE year = ?');
    $stmt->execute([$year]);
    $id = $stmt->fetchColumn();
    if ($id) return (int)$id;

    $stmt = $pdo->prepare('INSERT INTO awards_editions (year) VALUES (?)');
    $stmt->execute([$year]);
    return (int)$pdo->lastInsertId();
}

function _category_id_for_name(string $name): ?int {
    $stmt = db()->prepare('SELECT id FROM categories WHERE name = ?');
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}

function _actor_id_for_name(string $name): int {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM actors WHERE name = ?');
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();
    if ($id) return (int)$id;

    $stmt = $pdo->prepare('INSERT INTO actors (name) VALUES (?)');
    $stmt->execute([$name]);
    return (int)$pdo->lastInsertId();
}

function _film_id_for(string $title, int $year): int {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM films WHERE title = ? AND year = ?');
    $stmt->execute([$title, $year]);
    $id = $stmt->fetchColumn();
    if ($id) return (int)$id;

    $stmt = $pdo->prepare('INSERT INTO films (title, year) VALUES (?, ?)');
    $stmt->execute([$title, $year]);
    return (int)$pdo->lastInsertId();
}

function create_nomination(): void {
    $data = read_json_body();

    $year      = v_int($data['year'] ?? null);
    $category  = v_str($data['category'] ?? null);
    $actor     = v_str($data['actor'] ?? null);
    $film      = v_str($data['film'] ?? null);
    $filmYear  = v_int($data['film_year'] ?? null);
    $isWinner  = v_bool01($data['is_winner'] ?? 0);

    if ($year === null || $category === null || $actor === null || $film === null || $filmYear === null) {
        bad_request('year, category, actor, film, film_year are required');
        return;
    }
    if ($isWinner === null) { bad_request('is_winner must be 0 or 1'); return; }

    $categoryId = _category_id_for_name($category);
    if ($categoryId === null) { bad_request('Unknown category: ' . $category); return; }

    $editionId = _edition_id_for_year($year);
    $actorId   = _actor_id_for_name($actor);
    $filmId    = _film_id_for($film, $filmYear);

    $stmt = db()->prepare(
        'INSERT INTO nominations (edition_id, category_id, actor_id, film_id, is_winner)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$editionId, $categoryId, $actorId, $filmId, $isWinner]);

    $newId = (int)db()->lastInsertId();
    get_nomination($newId);
}

function update_nomination(int $id): void {
    $stmt = db()->prepare('SELECT id FROM nominations WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetchColumn()) { not_found('Nomination not found'); return; }

    $data = read_json_body();

    $year      = v_int($data['year'] ?? null);
    $category  = v_str($data['category'] ?? null);
    $actor     = v_str($data['actor'] ?? null);
    $film      = v_str($data['film'] ?? null);
    $filmYear  = v_int($data['film_year'] ?? null);
    $isWinner  = v_bool01($data['is_winner'] ?? null);

    if ($year === null || $category === null || $actor === null || $film === null || $filmYear === null || $isWinner === null) {
        bad_request('year, category, actor, film, film_year, is_winner are required');
        return;
    }

    $categoryId = _category_id_for_name($category);
    if ($categoryId === null) { bad_request('Unknown category: ' . $category); return; }

    $editionId = _edition_id_for_year($year);
    $actorId   = _actor_id_for_name($actor);
    $filmId    = _film_id_for($film, $filmYear);

    $stmt = db()->prepare(
        'UPDATE nominations
            SET edition_id = ?, category_id = ?, actor_id = ?, film_id = ?, is_winner = ?
          WHERE id = ?'
    );
    $stmt->execute([$editionId, $categoryId, $actorId, $filmId, $isWinner, $id]);

    get_nomination($id);
}

function delete_nomination(int $id): void {
    $stmt = db()->prepare('SELECT id FROM nominations WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetchColumn()) { not_found('Nomination not found'); return; }

    $stmt = db()->prepare('DELETE FROM nominations WHERE id = ?');
    $stmt->execute([$id]);
    json_response(['deleted' => true, 'id' => $id]);
}
