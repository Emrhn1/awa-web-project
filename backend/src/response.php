<?php

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function not_found(string $msg = 'Not found'): void {
    json_response(['error' => $msg], 404);
}

function bad_request(string $msg = 'Bad request'): void {
    json_response(['error' => $msg], 400);
}

function server_error(string $msg = 'Server error'): void {
    json_response(['error' => $msg], 500);
}
