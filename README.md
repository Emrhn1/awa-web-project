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

# 2. Build the database and load seed data
php backend/database/migrate.php

# 3. Start the PHP built-in server
php -S localhost:8000 -t backend/public backend/public/router.php
```

## Quick Test

Open in your browser or via curl:

- http://localhost:8000/api
- http://localhost:8000/api/nominations
- http://localhost:8000/api/nominations?year=2024&winner=1
- http://localhost:8000/api/nominations/1
- http://localhost:8000/api/actors
- http://localhost:8000/api/awards-editions
- http://localhost:8000/api/tmdb/search/actor?q=cillian+murphy *(requires TMDb key)*

## Running the Frontend

Open `frontend/index.html` in your browser while the PHP server is running.

> Note: The browser makes cross-origin requests from `file://` to `http://localhost:8000`. The API sets `Access-Control-Allow-Origin: *` so this works out of the box. If you have issues, serve the frontend through the PHP server instead.

## Folder Structure

```
awa-web-project/
├── backend/
│   ├── public/        # router.php (entry point)
│   ├── src/           # db.php, env.php, response.php, validate.php, controllers/
│   └── database/      # schema.sql, seed.php, migrate.php, awa.sqlite
├── frontend/          # index.html, css/main.css, js/main.js
└── docs/              # architecture.md, api.md, data-model.md
```
