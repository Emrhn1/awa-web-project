<?php

if (php_sapi_name() === 'cli-server') {
    $reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $reqPath;
    if ($reqPath !== '/' && file_exists($file) && !is_dir($file)) {
        return false;
    }
}

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/env.php';
require_once __DIR__ . '/../src/response.php';
require_once __DIR__ . '/../src/validate.php';
require_once __DIR__ . '/../src/controllers/nominations.php';
require_once __DIR__ . '/../src/controllers/actors.php';
require_once __DIR__ . '/../src/controllers/films.php';
require_once __DIR__ . '/../src/controllers/editions.php';
require_once __DIR__ . '/../src/controllers/categories.php';
require_once __DIR__ . '/../src/controllers/tmdb.php';
require_once __DIR__ . '/../src/controllers/news.php';
require_once __DIR__ . '/../src/controllers/export.php';
require_once __DIR__ . '/../src/controllers/import.php';

set_exception_handler(function (Throwable $e) {
    server_error($e->getMessage());
});

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path   = rtrim($path, '/');
if ($path === '') {
    $path = '/';
}

if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

if ($path === '/' || $path === '/api') {
    json_response([
        'name'      => 'AwA API',
        'version'   => '0.4.0',
        'phase'     => 4,
        'endpoints' => [
            'GET    /api/nominations',
            'GET    /api/nominations/{id}',
            'POST   /api/nominations',
            'PUT    /api/nominations/{id}',
            'DELETE /api/nominations/{id}',
            'GET    /api/actors',
            'GET    /api/actors/{id}',
            'GET    /api/films',
            'GET    /api/films/{id}',
            'GET    /api/awards-editions',
            'GET    /api/categories',
            'GET    /api/enrich/actor/{id}',
            'GET    /api/enrich/film/{id}',
            'GET    /api/news?actor=...',
            'GET    /api/news-sources',
            'POST   /api/news-sources',
            'PUT    /api/news-sources/{id}',
            'DELETE /api/news-sources/{id}',
            'GET    /api/export/csv',
            'GET    /api/export/json',
            'GET    /api/export/svg',
            'GET    /api/export/webp',
            'POST   /api/import/csv',
            'POST   /api/import/json',
        ],
    ]);
    exit;
}

if ($path === '/api/nominations') {
    if ($method === 'GET')  { list_nominations(); exit; }
    if ($method === 'POST') { create_nomination(); exit; }
    json_response(['error' => 'Method not allowed'], 405); exit;
}

if (preg_match('#^/api/nominations/(\d+)$#', $path, $m)) {
    $id = (int)$m[1];
    if ($method === 'GET')    { get_nomination($id);    exit; }
    if ($method === 'PUT')    { update_nomination($id); exit; }
    if ($method === 'DELETE') { delete_nomination($id); exit; }
    json_response(['error' => 'Method not allowed'], 405); exit;
}

if ($path === '/api/actors') {
    if ($method !== 'GET') { json_response(['error' => 'Method not allowed'], 405); exit; }
    list_actors(); exit;
}
if (preg_match('#^/api/actors/(\d+)$#', $path, $m)) {
    if ($method !== 'GET') { json_response(['error' => 'Method not allowed'], 405); exit; }
    get_actor((int)$m[1]); exit;
}

if ($path === '/api/films') {
    if ($method !== 'GET') { json_response(['error' => 'Method not allowed'], 405); exit; }
    list_films(); exit;
}

if (preg_match('#^/api/films/(\d+)$#', $path, $m)) {
    if ($method !== 'GET') { json_response(['error' => 'Method not allowed'], 405); exit; }
    get_film((int)$m[1]); exit;
}

if ($path === '/api/awards-editions') {
    if ($method !== 'GET') { json_response(['error' => 'Method not allowed'], 405); exit; }
    list_editions(); exit;
}
if ($path === '/api/categories') {
    if ($method !== 'GET') { json_response(['error' => 'Method not allowed'], 405); exit; }
    list_categories(); exit;
}

if (preg_match('#^/api/enrich/actor/(\d+)$#', $path, $m)) {
    if ($method !== 'GET') { json_response(['error' => 'Method not allowed'], 405); exit; }
    enrich_actor((int)$m[1]); exit;
}
if (preg_match('#^/api/enrich/film/(\d+)$#', $path, $m)) {
    if ($method !== 'GET') { json_response(['error' => 'Method not allowed'], 405); exit; }
    enrich_film((int)$m[1]); exit;
}

if ($path === '/api/news') {
    if ($method !== 'GET') { json_response(['error' => 'Method not allowed'], 405); exit; }
    list_news(); exit;
}

if (preg_match('#^/api/export/(csv|json|svg|webp)$#', $path, $m)) {
    if ($method !== 'GET') { json_response(['error' => 'Method not allowed'], 405); exit; }
    export_data($m[1]); exit;
}

if ($path === '/api/import/csv' && $method === 'POST') { import_csv(); exit; }
if ($path === '/api/import/json' && $method === 'POST') { import_json(); exit; }

if ($path === '/api/news-sources') {
    if ($method === 'GET')  { list_news_sources(); exit; }
    if ($method === 'POST') { create_news_source(); exit; }
    json_response(['error' => 'Method not allowed'], 405); exit;
}
if (preg_match('#^/api/news-sources/(\d+)$#', $path, $m)) {
    $id = (int)$m[1];
    if ($method === 'GET')    { get_news_source($id); exit; }
    if ($method === 'PUT')    { update_news_source($id); exit; }
    if ($method === 'DELETE') { delete_news_source($id); exit; }
    json_response(['error' => 'Method not allowed'], 405); exit;
}

not_found('Unknown route: ' . $path);
