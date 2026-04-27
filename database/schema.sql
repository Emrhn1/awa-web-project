-- Accident Web Visualizer (AWe) - Database Schema
-- SQLite

CREATE TABLE IF NOT EXISTS accidents (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    date            TEXT    NOT NULL,                          -- YYYY-MM-DD
    time            TEXT    NOT NULL,                          -- HH:MM
    latitude        REAL    NOT NULL,
    longitude       REAL    NOT NULL,
    city            TEXT    NOT NULL,
    district        TEXT,
    severity        TEXT    NOT NULL CHECK (severity IN ('minor', 'major', 'fatal')),
    vehicle_count   INTEGER,
    injury_count    INTEGER,
    death_count     INTEGER,
    description     TEXT,
    created_at      TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Indexes 
CREATE INDEX IF NOT EXISTS idx_accidents_date     ON accidents(date);
CREATE INDEX IF NOT EXISTS idx_accidents_city     ON accidents(city);
CREATE INDEX IF NOT EXISTS idx_accidents_severity ON accidents(severity);
