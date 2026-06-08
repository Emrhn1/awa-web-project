<?php

if (!function_exists('env') && file_exists(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
}

function db_path(): string {
    $configured = function_exists('env') ? env('AWA_DB_PATH') : null;
    return $configured ?: __DIR__ . '/../database/awa.sqlite';
}

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $path = db_path();
    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    return $pdo;
}
