<?php
// TMDb proxy. API key stays server-side, read from .env.

function _tmdb_call(string $path): void {
    $key = env('TMDB_API_KEY');
    if (!$key) {
        server_error('TMDB_API_KEY not configured (set it in .env)');
        return;
    }

    $url = 'https://api.themoviedb.org/3' . $path
         . (str_contains($path, '?') ? '&' : '?')
         . 'api_key=' . urlencode($key);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        server_error('TMDb request failed: ' . $err);
        return;
    }

    // TMDb returns gzip-compressed responses; decompress if needed.
    if (str_starts_with($body, "\x1f\x8b")) {
        $body = gzdecode($body);
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        server_error('Invalid response from TMDb');
        return;
    }

    if ($code >= 400) {
        json_response($data, (int)$code);
        return;
    }

    json_response($data);
}

function tmdb_actor(int $id): void {
    _tmdb_call("/person/{$id}");
}

function tmdb_movie(int $id): void {
    _tmdb_call("/movie/{$id}");
}

// Bonus: name-based search (useful for matching seed data → TMDb ids).
function tmdb_search_actor(): void {
    $q = v_str($_GET['q'] ?? null);
    if ($q === null) { bad_request('q parameter required'); return; }
    _tmdb_call('/search/person?query=' . urlencode($q));
}

function tmdb_search_movie(): void {
    $q = v_str($_GET['q'] ?? null);
    if ($q === null) { bad_request('q parameter required'); return; }
    _tmdb_call('/search/movie?query=' . urlencode($q));
}
