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
    ensure_phase4_schema($pdo);

    return $pdo;
}

function ensure_phase4_schema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS news_sources (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            name          TEXT NOT NULL UNIQUE,
            url_template  TEXT NOT NULL,
            is_enabled    INTEGER NOT NULL DEFAULT 1 CHECK (is_enabled IN (0, 1)),
            created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_news_sources_enabled ON news_sources(is_enabled)');
}
