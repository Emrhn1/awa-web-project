<?php
/**
 * Initialize the SQLite database from schema.sql.
 * Usage:  php scripts/init-db.php
 */

$projectRoot = dirname(__DIR__);
$schemaPath  = $projectRoot . '/database/schema.sql';
$dbPath      = $projectRoot . '/database/awa.sqlite';

if (!file_exists($schemaPath)) {
    fwrite(STDERR, "schema.sql not found at: {$schemaPath}\n");
    exit(1);
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    $sql = file_get_contents($schemaPath);
    $pdo->exec($sql);

    echo "OK: database created at {$dbPath}\n";
} catch (PDOException $e) {
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
