<?php

require_once __DIR__ . '/../routes.php';
require_once __DIR__ . '/Response.php';

function dispatch(): void {
    $method = $_SERVER['REQUEST_METHOD'];
    $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Strip leading /api/ prefix
    $uri      = preg_replace('#^/api/?#', '', $uri);
    $segments = explode('/', trim($uri, '/'));
    $resource = array_shift($segments); // e.g. "accidents"

    if (!isset(ROUTES[$resource])) {
        jsonError('Not found', 404);
    }

    if (!isset(ROUTES[$resource][$method])) {
        jsonError('Method not allowed', 405);
    }

    call_user_func(ROUTES[$resource][$method], $segments);
}
