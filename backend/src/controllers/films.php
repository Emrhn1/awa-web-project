<?php

function list_films(): void {
    $sql = "
        SELECT
            f.id,
            f.title,
            f.year,
            f.tmdb_id,
            COUNT(n.id) AS nomination_count
        FROM films f
        LEFT JOIN nominations n ON n.film_id = f.id
        GROUP BY f.id, f.title, f.year, f.tmdb_id
        ORDER BY nomination_count DESC, f.title ASC
    ";
    json_response(db()->query($sql)->fetchAll());
}

function get_film(int $id): void {
    $stmt = db()->prepare('SELECT * FROM films WHERE id = ?');
    $stmt->execute([$id]);
    $film = $stmt->fetch();
    if (!$film) {
        not_found('Film not found');
        return;
    }

    $stmt = db()->prepare("
        SELECT
            n.id,
            n.is_winner,
            e.year,
            c.name  AS category,
            a.name  AS actor
        FROM nominations n
        JOIN awards_editions e ON e.id = n.edition_id
        JOIN categories      c ON c.id = n.category_id
        JOIN actors          a ON a.id = n.actor_id
        WHERE n.film_id = ?
        ORDER BY e.year DESC, c.name ASC
    ");
    $stmt->execute([$id]);
    $film['nominations'] = $stmt->fetchAll();

    json_response($film);
}
