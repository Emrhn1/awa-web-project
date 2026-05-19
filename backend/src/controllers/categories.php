<?php

function list_categories(): void {
    $sql = "
        SELECT
            c.id,
            c.name,
            COUNT(n.id) AS nomination_count
        FROM categories c
        LEFT JOIN nominations n ON n.category_id = c.id
        GROUP BY c.id, c.name
        ORDER BY c.name ASC
    ";
    json_response(db()->query($sql)->fetchAll());
}
