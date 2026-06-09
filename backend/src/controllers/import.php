<?php

function import_csv(): void {
    $raw = file_get_contents('php://input');
    if (!$raw || !trim($raw)) {
        bad_request('Empty CSV body');
        return;
    }

    $tmp = tmpfile();
    fwrite($tmp, $raw);
    rewind($tmp);

    $headers = fgetcsv($tmp, null, ',', '"', '\\');
    if (!$headers) {
        fclose($tmp);
        bad_request('Could not parse CSV headers');
        return;
    }

    $headers = array_map('trim', $headers);
    $required = ['year', 'category', 'actor', 'film', 'film_year', 'is_winner'];
    foreach ($required as $col) {
        if (!in_array($col, $headers, true)) {
            fclose($tmp);
            bad_request("Missing required column: $col");
            return;
        }
    }

    $imported = 0;
    $skipped  = 0;
    $errors   = [];
    $lineNum  = 1;

    while (($values = fgetcsv($tmp, null, ',', '"', '\\')) !== false) {
        $lineNum++;
        if (count($values) < count($headers)) {
            continue;
        }
        $row    = array_combine($headers, array_slice($values, 0, count($headers)));
        $result = _import_row($row, $lineNum);
        if ($result === true) {
            $imported++;
        } elseif ($result === 'duplicate') {
            $skipped++;
        } else {
            $errors[] = $result;
        }
    }

    fclose($tmp);
    json_response(['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors]);
}

function import_json(): void {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        bad_request('JSON body must be an array of nomination objects');
        return;
    }

    $imported = 0;
    $skipped  = 0;
    $errors   = [];

    foreach ($data as $i => $row) {
        if (!is_array($row)) {
            $errors[] = 'Item ' . ($i + 1) . ': not an object';
            continue;
        }
        $result = _import_row($row, $i + 1);
        if ($result === true) {
            $imported++;
        } elseif ($result === 'duplicate') {
            $skipped++;
        } else {
            $errors[] = $result;
        }
    }

    json_response(['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors]);
}

function _import_row(array $row, int $lineNum) {
    $year = v_int($row['year'] ?? null);
    if ($year === null || $year < 1900 || $year > 2100) {
        return "Row $lineNum: invalid year";
    }

    $category = v_str($row['category'] ?? null, 120);
    if ($category === null) {
        return "Row $lineNum: missing category";
    }

    $actor = v_str($row['actor'] ?? null, 255);
    if ($actor === null) {
        return "Row $lineNum: missing actor";
    }

    $film = v_str($row['film'] ?? null, 255);
    if ($film === null) {
        return "Row $lineNum: missing film";
    }

    $filmYear = v_int($row['film_year'] ?? null);
    if ($filmYear === null || $filmYear < 1900 || $filmYear > 2100) {
        return "Row $lineNum: invalid film_year";
    }

    $isWinner = v_bool01($row['is_winner'] ?? null);
    if ($isWinner === null) {
        return "Row $lineNum: is_winner must be 0 or 1";
    }

    $editionId  = _edition_id_for_year($year);
    $categoryId = _import_category_id($category);
    $actorId    = _actor_id_for_name($actor);
    $filmId     = _film_id_for($film, $filmYear);

    $stmt = db()->prepare(
        'SELECT id FROM nominations WHERE edition_id=? AND category_id=? AND actor_id=? AND film_id=?'
    );
    $stmt->execute([$editionId, $categoryId, $actorId, $filmId]);
    if ($stmt->fetch()) {
        return 'duplicate';
    }

    $stmt = db()->prepare(
        'INSERT INTO nominations (edition_id, category_id, actor_id, film_id, is_winner) VALUES (?,?,?,?,?)'
    );
    $stmt->execute([$editionId, $categoryId, $actorId, $filmId, $isWinner]);
    return true;
}

function _import_category_id(string $name): int {
    $pdo  = db();
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = ?');
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();
    if ($id) return (int)$id;

    $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
    $stmt->execute([$name]);
    return (int)$pdo->lastInsertId();
}
