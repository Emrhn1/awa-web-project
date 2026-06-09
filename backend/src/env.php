<?php

function env(string $key, ?string $default = null): ?string {
    static $loaded = false;
    static $vars = [];

    if (!$loaded) {
        $loaded = true;
        $path = __DIR__ . '/../../.env';
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $i => $line) {
                if ($i === 0) $line = ltrim($line, "\xEF\xBB\xBF");
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                if (!str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $vars[trim($k)] = trim($v);
            }
        }
    }

    return $vars[$key] ?? $default;
}
