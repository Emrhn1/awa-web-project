<?php
if (php_sapi_name() === 'cli-server') {
    $reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $reqPath;
    if ($reqPath !== '/' && file_exists($file) && !is_dir($file)) {
        return false;
    }
}

require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/env.php';
require __DIR__ . '/../src/response.php';
require __DIR__ . '/../src/validate.php';
require __DIR__ . '/../src/controllers/nominations.php';
require __DIR__ . '/../src/controllers/actors.php';
require __DIR__ . '/../src/controllers/films.php';
require __DIR__ . '/../src/controllers/editions.php';
require __DIR__ . '/../src/controllers/categories.php';
require __DIR__ . '/../src/controllers/tmdb.php';

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
        'version'   => '0.2.0',
        'phase'     => 2,
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
            'GET    /api/tmdb/actor/{id}',
            'GET    /api/tmdb/movie/{id}',
            'GET    /api/tmdb/search/actor?q=...',
            'GET    /api/tmdb/search/movie?q=...',
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

if ($method === 'GET') {
    if (preg_match('#^/api/tmdb/actor/(\d+)$#', $path, $m)) {
        tmdb_actor((int)$m[1]); exit;
    }
    if (preg_match('#^/api/tmdb/movie/(\d+)$#', $path, $m)) {
        tmdb_movie((int)$m[1]); exit;
    }
    if ($path === '/api/tmdb/search/actor') { tmdb_search_actor(); exit; }
    if ($path === '/api/tmdb/search/movie') { tmdb_search_movie(); exit; }
}

not_found('Unknown route: ' . $path);
