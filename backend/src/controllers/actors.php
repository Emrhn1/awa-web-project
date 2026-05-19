<?php

function list_actors(): void {
    $sql = "
        SELECT
            a.id,
            a.name,
            a.tmdb_id,
            COUNT(n.id)                                    AS nomination_count,
            SUM(CASE WHEN n.is_winner = 1 THEN 1 ELSE 0 END) AS win_count
        FROM actors a
        LEFT JOIN nominations n ON n.actor_id = a.id
        GROUP BY a.id, a.name, a.tmdb_id
        ORDER BY nomination_count DESC, a.name ASC
    ";
    json_response(db()->query($sql)->fetchAll());
}

function get_actor(int $id): void {
    $stmt = db()->prepare('SELECT * FROM actors WHERE id = ?');
    $stmt->execute([$id]);
    $actor = $stmt->fetch();
    if (!$actor) {
        not_found('Actor not found');
        return;
    }

    $stmt = db()->prepare("
        SELECT
            n.id,
            n.is_winner,
            e.year,
            c.name  AS category,
            f.title AS film,
            f.year  AS film_year
        FROM nominations n
        JOIN awards_editions e ON e.id = n.edition_id
        JOIN categories      c ON c.id = n.category_id
        JOIN films           f ON f.id = n.film_id
        WHERE n.actor_id = ?
        ORDER BY e.year DESC
    ");
    $stmt->execute([$id]);
    $actor['nominations'] = $stmt->fetchAll();

    json_response($actor);
}
