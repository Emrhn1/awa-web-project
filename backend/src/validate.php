<?php
// Tiny validation helpers. Each returns the cleaned value or null if invalid.

function v_int($value): ?int {
    if ($value === null || $value === '') return null;
    if (is_numeric($value)) return (int)$value;
    return null;
}

function v_bool01($value): ?int {
    if ($value === null || $value === '') return null;
    if ($value === '1' || $value === 1 || $value === true)  return 1;
    if ($value === '0' || $value === 0 || $value === false) return 0;
    return null;
}

function v_str($value, int $maxLen = 255): ?string {
    if (!is_string($value)) return null;
    $value = trim($value);
    $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    if ($value === '' || $length > $maxLen) return null;
    return $value;
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
