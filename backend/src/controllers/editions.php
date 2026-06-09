<?php

function list_editions(): void {
    $sql = "
        SELECT
            e.id,
            e.year,
            COUNT(n.id) AS nomination_count
        FROM awards_editions e
        LEFT JOIN nominations n ON n.edition_id = e.id
        GROUP BY e.id, e.year
        ORDER BY e.year DESC
    ";
    json_response(db()->query($sql)->fetchAll());
}
