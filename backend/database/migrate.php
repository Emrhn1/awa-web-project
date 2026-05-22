<?php
// Rebuilds awa.sqlite from scratch:
//   1. delete old db file (if any)
//   2. apply schema.sql
//   3. insert seed data from seed.php
//
// Usage:  php backend/database/migrate.php

require __DIR__ . '/../src/db.php';

$dbPath = db_path();
if (file_exists($dbPath)) {
    unlink($dbPath);
    echo "Removed existing $dbPath\n";
}

$pdo = db();

// 1. schema
$schema = file_get_contents(__DIR__ . '/schema.sql');
$pdo->exec($schema);
echo "Schema applied.\n";

// 2. seed
$seed = require __DIR__ . '/seed.php';

$pdo->beginTransaction();

// editions
$stmt = $pdo->prepare(
    'INSERT INTO awards_editions (year, ceremony_no, held_on) VALUES (?, ?, ?)'
);
foreach ($seed['editions'] as [$year, $no, $date]) {
    $stmt->execute([$year, $no, $date]);
}
echo "Inserted " . count($seed['editions']) . " editions.\n";

// categories
$stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
foreach ($seed['categories'] as $name) {
    $stmt->execute([$name]);
}
echo "Inserted " . count($seed['categories']) . " categories.\n";

// news sources
$stmt = $pdo->prepare(
    'INSERT OR IGNORE INTO news_sources (name, url_template, is_enabled) VALUES (?, ?, ?)'
);
foreach ($seed['news_sources'] as [$name, $urlTemplate, $enabled]) {
    $stmt->execute([$name, $urlTemplate, $enabled]);
}
echo "Inserted " . count($seed['news_sources']) . " news sources.\n";

// gather unique actors + films from nominations
$actors = [];
$films = [];
foreach ($seed['nominations'] as [, , $actor, $film, $filmYear, ]) {
    $actors[$actor] = true;
    $films[$film . '|' . $filmYear] = [$film, $filmYear];
}

// actors
$stmt = $pdo->prepare('INSERT INTO actors (name) VALUES (?)');
foreach (array_keys($actors) as $name) {
    $stmt->execute([$name]);
}
echo "Inserted " . count($actors) . " actors.\n";

// films
$stmt = $pdo->prepare('INSERT INTO films (title, year) VALUES (?, ?)');
foreach ($films as [$title, $year]) {
    $stmt->execute([$title, $year]);
}
echo "Inserted " . count($films) . " films.\n";

// lookups
$editionId = [];
foreach ($pdo->query('SELECT id, year FROM awards_editions') as $r) {
    $editionId[(int)$r['year']] = (int)$r['id'];
}
$categoryId = [];
foreach ($pdo->query('SELECT id, name FROM categories') as $r) {
    $categoryId[$r['name']] = (int)$r['id'];
}
$actorId = [];
foreach ($pdo->query('SELECT id, name FROM actors') as $r) {
    $actorId[$r['name']] = (int)$r['id'];
}
$filmId = [];
foreach ($pdo->query('SELECT id, title, year FROM films') as $r) {
    $filmId[$r['title'] . '|' . $r['year']] = (int)$r['id'];
}

// nominations
$stmt = $pdo->prepare(
    'INSERT INTO nominations (edition_id, category_id, actor_id, film_id, is_winner)
     VALUES (?, ?, ?, ?, ?)'
);
foreach ($seed['nominations'] as [$year, $cat, $actor, $film, $filmYear, $win]) {
    $stmt->execute([
        $editionId[$year],
        $categoryId[$cat],
        $actorId[$actor],
        $filmId[$film . '|' . $filmYear],
        $win,
    ]);
}
echo "Inserted " . count($seed['nominations']) . " nominations.\n";

$pdo->commit();
echo "Done. DB at: $dbPath\n";
