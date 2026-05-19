<?php

function list_nominations(): void {
    $sql = "
        SELECT
            n.id,
            n.is_winner,
            e.year,
            e.ceremony_no,
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
        ORDER BY e.year DESC, c.name ASC, n.is_winner DESC, a.name ASC
    ";
    json_response(db()->query($sql)->fetchAll());
}

function get_nomination(int $id): void {
    $sql = "
        SELECT
            n.id,
            n.is_winner,
            e.year,
            e.ceremony_no,
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
        WHERE n.id = ?
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        not_found('Nomination not found');
        return;
    }
    json_response($row);
}
