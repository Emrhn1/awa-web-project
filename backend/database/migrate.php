<?php

require __DIR__ . '/../src/db.php';

$dbPath = db_path();
if (file_exists($dbPath)) {
    unlink($dbPath);
    echo "Removed existing $dbPath\n";
}
$journalPath = $dbPath . '-journal';
if (file_exists($journalPath)) {
    unlink($journalPath);
    echo "Removed existing $journalPath\n";
}

$pdo = db();

$schema = file_get_contents(__DIR__ . '/schema.sql');
$pdo->exec($schema);
echo "Schema applied.\n";

echo "No seed data inserted. Use backend/database/seed.csv or seed.json with the import UI.\n";
echo "Done. Empty DB at: $dbPath\n";
