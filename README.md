# AwA — Actor Awards Visualizer

A web application built for the **Web Technologies (UAIC)** course that visualizes **Screen Actors Guild (SAG) Awards** actor nominations.

## Requirements

- **PHP 8.0+** (PDO + SQLite extension included)
- A browser

No Composer, no framework, no npm.

## Setup

```bash
# 1. Create your .env file
cp .env.example .env
# Open .env and set TMDB_API_KEY=your_key_here
# (Get a free key at https://www.themoviedb.org/settings/api)

# 2. Build an empty database
php backend/database/migrate.php

# 3. Start the PHP built-in server
php -S localhost:8000 -t backend/public backend/public/router.php
```

The app starts without nominations. To load the sample SAG data, use the
Administration import controls and choose either `backend/database/seed.csv` or
`backend/database/seed.json`.

## Quick Test

Open in your browser or via curl:

- http://localhost:8000/api
- http://localhost:8000/api/nominations
- http://localhost:8000/api/nominations?year=2024&winner=1
- http://localhost:8000/api/nominations/1
- http://localhost:8000/api/actors
- http://localhost:8000/api/awards-editions
- http://localhost:8000/api/enrich/actor/1 *(requires TMDb key and imported data)*

## Running the Frontend

Open `frontend/index.html` in your browser while the PHP server is running.

> Note: The browser makes cross-origin requests from `file://` to `http://localhost:8000`. The API sets `Access-Control-Allow-Origin: *` so this works out of the box. If you have issues, serve the frontend through the PHP server instead.

## Folder Structure

```
awa-web-project/
├── backend/
│   ├── public/        # router.php (entry point)
│   ├── src/           # db.php, env.php, response.php, validate.php, controllers/
│   └── database/      # schema.sql, seed.csv, seed.json, migrate.php, awa.sqlite
├── frontend/          # index.html, css/main.css, js/main.js
└── docs/              # architecture.md, api.md, data-model.md
```
https://feeds.bbci.co.uk/news/rss.xml?q={query}
