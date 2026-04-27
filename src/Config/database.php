<?php

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dbPath = dirname(__DIR__, 2) . '/database/awa.sqlite';
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON;');
    }
    return $pdo;
}
