<?php

function list_news_sources(): void {
    $stmt = db()->query(
        'SELECT id, name, url_template, is_enabled, created_at
           FROM news_sources
          ORDER BY is_enabled DESC, name ASC'
    );
    json_response($stmt->fetchAll());
}

function create_news_source(): void {
    $data = read_json_body();
    $name = v_str($data['name'] ?? null, 120);
    $urlTemplate = v_url_template($data['url_template'] ?? null);
    $enabled = v_bool01($data['is_enabled'] ?? 1);

    if ($name === null || $urlTemplate === null || $enabled === null) {
        bad_request('name, url_template with {query}, and is_enabled are required');
        return;
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO news_sources (name, url_template, is_enabled) VALUES (?, ?, ?)'
        );
        $stmt->execute([$name, $urlTemplate, $enabled]);
    } catch (PDOException $e) {
        bad_request('News source name must be unique');
        return;
    }

    get_news_source((int)db()->lastInsertId());
}

function get_news_source(int $id): void {
    $stmt = db()->prepare(
        'SELECT id, name, url_template, is_enabled, created_at FROM news_sources WHERE id = ?'
    );
    $stmt->execute([$id]);
    $source = $stmt->fetch();
    if (!$source) { not_found('News source not found'); return; }
    json_response($source);
}

function update_news_source(int $id): void {
    $stmt = db()->prepare('SELECT id FROM news_sources WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetchColumn()) { not_found('News source not found'); return; }

    $data = read_json_body();
    $name = v_str($data['name'] ?? null, 120);
    $urlTemplate = v_url_template($data['url_template'] ?? null);
    $enabled = v_bool01($data['is_enabled'] ?? null);

    if ($name === null || $urlTemplate === null || $enabled === null) {
        bad_request('name, url_template with {query}, and is_enabled are required');
        return;
    }

    try {
        $stmt = db()->prepare(
            'UPDATE news_sources SET name = ?, url_template = ?, is_enabled = ? WHERE id = ?'
        );
        $stmt->execute([$name, $urlTemplate, $enabled, $id]);
    } catch (PDOException $e) {
        bad_request('News source name must be unique');
        return;
    }

    get_news_source($id);
}

function delete_news_source(int $id): void {
    $stmt = db()->prepare('SELECT id FROM news_sources WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetchColumn()) { not_found('News source not found'); return; }

    $stmt = db()->prepare('DELETE FROM news_sources WHERE id = ?');
    $stmt->execute([$id]);
    json_response(['deleted' => true, 'id' => $id]);
}

function list_news(): void {
    $query = v_str($_GET['actor'] ?? $_GET['q'] ?? null, 160);
    if ($query === null) { bad_request('actor or q parameter required'); return; }

    $limit = v_int($_GET['limit'] ?? 8) ?? 8;
    $limit = max(1, min(20, $limit));

    $stmt = db()->query(
        'SELECT id, name, url_template
           FROM news_sources
          WHERE is_enabled = 1
          ORDER BY name ASC'
    );
    $sources = $stmt->fetchAll();
    $items = [];
    $errors = [];

    foreach ($sources as $source) {
        $url = str_replace('{query}', rawurlencode($query . ' actor SAG Awards'), $source['url_template']);
        [$sourceItems, $error] = _fetch_rss_items($url, $source['name'], $limit);
        if ($error !== null) {
            $errors[] = ['source' => $source['name'], 'error' => $error];
            continue;
        }
        $items = array_merge($items, $sourceItems);
    }

    usort($items, function ($a, $b) {
        return strcmp($b['published_at'] ?? '', $a['published_at'] ?? '');
    });

    json_response([
        'query' => $query,
        'sources' => $sources,
        'items' => array_slice($items, 0, $limit),
        'errors' => $errors,
    ]);
}

function _fetch_rss_items(string $url, string $sourceName, int $limit): array {
    $body = false;
    $curlError = null;

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/rss+xml, application/xml, text/xml\r\nUser-Agent: AwA-News-Reader/1.0\r\n",
            'timeout' => 8,
        ],
    ]);
    $body = @file_get_contents($url, false, $context);

    if ($body === false && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => ['Accept: application/rss+xml, application/xml, text/xml'],
            CURLOPT_USERAGENT => 'AwA-News-Reader/1.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $body = curl_exec($ch);
        $curlError = curl_error($ch);
    }

    if ($body === false) {
        return [[], 'RSS request failed' . ($curlError ? ': ' . $curlError : '')];
    }

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($body);
    if (!$xml) {
        return [[], 'Invalid RSS response'];
    }

    $items = [];
    foreach ($xml->channel->item ?? [] as $item) {
        if (count($items) >= $limit) break;
        $published = (string)($item->pubDate ?? '');
        $items[] = [
            'source' => $sourceName,
            'title' => html_entity_decode((string)$item->title, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'url' => (string)$item->link,
            'published_at' => $published ? date(DATE_ATOM, strtotime($published)) : null,
            'summary' => trim(strip_tags(html_entity_decode((string)$item->description, ENT_QUOTES | ENT_HTML5, 'UTF-8'))),
        ];
    }

    return [$items, null];
}
