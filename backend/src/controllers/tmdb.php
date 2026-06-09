<?php

function _tmdb_request(string $path): array {
    $key = env('TMDB_API_KEY');
    if (!$key) {
        return [null, 500, 'TMDB_API_KEY not configured (set it in .env)'];
    }

    $url = 'http://api.themoviedb.org/3' . $path
         . (str_contains($path, '?') ? '&' : '?')
         . 'api_key=' . urlencode($key);

    $body = false;
    $code = 200;
    $curlError = null;

    $context = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => "Accept: application/json\r\n",
            'timeout' => 8,
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    $streamError = error_get_last()['message'] ?? null;
    $headers = function_exists('http_get_last_response_headers')
        ? http_get_last_response_headers()
        : ($http_response_header ?? []);
    if (isset($headers[0]) && preg_match('#\s(\d{3})\s#', $headers[0], $m)) {
        $code = (int)$m[1];
    }

    if ($body === false && function_exists('curl_init')) {
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
        $curlError = curl_error($ch);

        if ($body === false) {
            return [null, 500, 'TMDb request failed: ' . $curlError];
        }
    }

    if ($body === false) {
        return [null, 500, 'TMDb request failed' . ($streamError ? ': ' . $streamError : '')];
    }

    if (str_starts_with($body, "\x1f\x8b")) {
        $body = gzdecode($body);
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return [null, 500, 'Invalid response from TMDb'];
    }

    return [$data, (int)$code, null];
}

function _tmdb_best_result(array $results): ?array {
    if (!$results) return null;
    usort($results, function ($a, $b) {
        return ($b['popularity'] ?? 0) <=> ($a['popularity'] ?? 0);
    });
    return $results[0] ?? null;
}

function enrich_actor(int $id): void {
    $stmt = db()->prepare('SELECT id, name, tmdb_id FROM actors WHERE id = ?');
    $stmt->execute([$id]);
    $actor = $stmt->fetch();
    if (!$actor) { not_found('Actor not found'); return; }

    $tmdbId = $actor['tmdb_id'] ? (int)$actor['tmdb_id'] : null;
    $matched = null;

    if ($tmdbId === null) {
        [$search, $searchCode, $searchError] = _tmdb_request('/search/person?query=' . urlencode($actor['name']));
        if ($searchError !== null) { server_error($searchError); return; }
        if ($searchCode >= 400) { json_response($search, $searchCode); return; }

        $matched = _tmdb_best_result($search['results'] ?? []);
        if (!$matched || empty($matched['id'])) {
            not_found('No TMDb actor match found');
            return;
        }

        $tmdbId = (int)$matched['id'];
        $stmt = db()->prepare('UPDATE actors SET tmdb_id = ?, bio_cached_at = datetime("now") WHERE id = ?');
        $stmt->execute([$tmdbId, $id]);
    }

    [$details, $detailCode, $detailError] = _tmdb_request('/person/' . $tmdbId . '?append_to_response=movie_credits');
    if ($detailError !== null) { server_error($detailError); return; }
    if ($detailCode >= 400) { json_response($details, $detailCode); return; }

    json_response([
        'local' => $actor,
        'matched' => $matched,
        'tmdb' => $details,
    ]);
}

function enrich_film(int $id): void {
    $stmt = db()->prepare('SELECT id, title, year, tmdb_id FROM films WHERE id = ?');
    $stmt->execute([$id]);
    $film = $stmt->fetch();
    if (!$film) { not_found('Film not found'); return; }

    $tmdbId = $film['tmdb_id'] ? (int)$film['tmdb_id'] : null;
    $matched = null;

    if ($tmdbId === null) {
        $query = '/search/movie?query=' . urlencode($film['title']);
        if (!empty($film['year'])) {
            $query .= '&year=' . urlencode((string)$film['year']);
        }

        [$search, $searchCode, $searchError] = _tmdb_request($query);
        if ($searchError !== null) { server_error($searchError); return; }
        if ($searchCode >= 400) { json_response($search, $searchCode); return; }

        $matched = _tmdb_best_result($search['results'] ?? []);
        if (!$matched || empty($matched['id'])) {
            not_found('No TMDb movie match found');
            return;
        }

        $tmdbId = (int)$matched['id'];
        $stmt = db()->prepare('UPDATE films SET tmdb_id = ? WHERE id = ?');
        $stmt->execute([$tmdbId, $id]);
    }

    [$details, $detailCode, $detailError] = _tmdb_request('/movie/' . $tmdbId);
    if ($detailError !== null) { server_error($detailError); return; }
    if ($detailCode >= 400) { json_response($details, $detailCode); return; }

    json_response([
        'local' => $film,
        'matched' => $matched,
        'tmdb' => $details,
    ]);
}
