PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS awards_editions (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    year         INTEGER NOT NULL UNIQUE,
    ceremony_no  INTEGER,
    held_on      TEXT
);

CREATE TABLE IF NOT EXISTS categories (
    id    INTEGER PRIMARY KEY AUTOINCREMENT,
    name  TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS actors (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    name            TEXT NOT NULL,
    tmdb_id         INTEGER,
    bio_cached_at   TEXT,
    UNIQUE (name)
);

CREATE TABLE IF NOT EXISTS films (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    title     TEXT NOT NULL,
    year      INTEGER,
    tmdb_id   INTEGER,
    UNIQUE (title, year)
);

CREATE TABLE IF NOT EXISTS nominations (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    edition_id    INTEGER NOT NULL REFERENCES awards_editions(id) ON DELETE CASCADE,
    category_id   INTEGER NOT NULL REFERENCES categories(id)      ON DELETE RESTRICT,
    actor_id      INTEGER NOT NULL REFERENCES actors(id)          ON DELETE CASCADE,
    film_id       INTEGER NOT NULL REFERENCES films(id)           ON DELETE CASCADE,
    is_winner     INTEGER NOT NULL DEFAULT 0 CHECK (is_winner IN (0, 1))
);

CREATE TABLE IF NOT EXISTS news_sources (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    name          TEXT NOT NULL UNIQUE,
    url_template  TEXT NOT NULL,
    is_enabled    INTEGER NOT NULL DEFAULT 1 CHECK (is_enabled IN (0, 1)),
    created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_nominations_edition  ON nominations(edition_id);
CREATE INDEX IF NOT EXISTS idx_nominations_category ON nominations(category_id);
CREATE INDEX IF NOT EXISTS idx_nominations_actor    ON nominations(actor_id);
CREATE INDEX IF NOT EXISTS idx_nominations_film     ON nominations(film_id);
CREATE INDEX IF NOT EXISTS idx_nominations_winner   ON nominations(is_winner);
CREATE INDEX IF NOT EXISTS idx_news_sources_enabled ON news_sources(is_enabled);
