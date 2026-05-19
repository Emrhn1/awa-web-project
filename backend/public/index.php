<?php
if (php_sapi_name() === 'cli-server') {
    $reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $reqPath;
    if ($reqPath !== '/' && file_exists($file) && !is_dir($file)) {
        return false;
    }
}

require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/response.php';
require __DIR__ . '/../src/controllers/nominations.php';
require __DIR__ . '/../src/controllers/actors.php';
require __DIR__ . '/../src/controllers/films.php';
require __DIR__ . '/../src/controllers/editions.php';
require __DIR__ . '/../src/controllers/categories.php';

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

if ($method !== 'GET') {
    json_response(['error' => 'Method not allowed in Phase 1'], 405);
    exit;
}

if ($path === '/' || $path === '/api') {
    json_response([
        'name'      => 'AwA API',
        'version'   => '0.1.0',
        'phase'     => 1,
        'endpoints' => [
            'GET /api/nominations',
            'GET /api/nominations/{id}',
            'GET /api/actors',
            'GET /api/actors/{id}',
            'GET /api/films',
            'GET /api/films/{id}',
            'GET /api/awards-editions',
            'GET /api/categories',
        ],
    ]);
    exit;
}

if ($path === '/api/nominations') {
    list_nominations();
    exit;
}
if (preg_match('#^/api/nominations/(\d+)$#', $path, $m)) {
    get_nomination((int)$m[1]);
    exit;
}

if ($path === '/api/actors') {
    list_actors();
    exit;
}
if (preg_match('#^/api/actors/(\d+)$#', $path, $m)) {
    get_actor((int)$m[1]);
    exit;
}

if ($path === '/api/films') {
    list_films();
    exit;
}
if (preg_match('#^/api/films/(\d+)$#', $path, $m)) {
    get_film((int)$m[1]);
    exit;
}

if ($path === '/api/awards-editions') {
    list_editions();
    exit;
}

if ($path === '/api/categories') {
    list_categories();
    exit;
}

not_found('Unknown route: ' . $path);
