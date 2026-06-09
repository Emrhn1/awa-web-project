<?php

function export_data(string $format): void {
    $rows = _export_nomination_rows();

    if ($format === 'csv') {
        _send_csv($rows);
        return;
    }
    if ($format === 'json') {
        _send_json($rows);
        return;
    }
    if ($format === 'svg') {
        _send_svg($rows);
        return;
    }
    if ($format === 'webp') {
        _send_webp($rows);
        return;
    }

    not_found('Unknown export format');
}

function _export_nomination_rows(): array {
    $where = [];
    $params = [];

    $year = v_int($_GET['year'] ?? null);
    if ($year !== null) {
        $where[] = 'e.year = ?';
        $params[] = $year;
    }

    $category = v_str($_GET['category'] ?? null);
    if ($category !== null) {
        $where[] = 'c.name = ?';
        $params[] = $category;
    }

    $winner = v_bool01($_GET['winner'] ?? null);
    if ($winner !== null) {
        $where[] = 'n.is_winner = ?';
        $params[] = $winner;
    }

    $search = v_str($_GET['q'] ?? null, 160);
    if ($search !== null) {
        $where[] = '(a.name LIKE ? OR f.title LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "
        SELECT
            e.year,
            c.name AS category,
            a.name AS actor,
            f.title AS film,
            f.year AS film_year,
            n.is_winner
        FROM nominations n
        JOIN awards_editions e ON e.id = n.edition_id
        JOIN categories c ON c.id = n.category_id
        JOIN actors a ON a.id = n.actor_id
        JOIN films f ON f.id = n.film_id
        $whereSql
        ORDER BY e.year DESC, c.name ASC, n.is_winner DESC, a.name ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function _send_csv(array $rows): void {
    $headers = ['year', 'category', 'actor', 'film', 'film_year', 'is_winner'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="awa-nominations.csv"');
    header('Access-Control-Allow-Origin: *');

    $out = fopen('php://output', 'w');
    fputcsv($out, $headers, ',', '"', '\\');
    foreach ($rows as $row) {
        fputcsv($out, array_map(fn($key) => $row[$key] ?? '', $headers), ',', '"', '\\');
    }
    fclose($out);
}

function _send_json(array $rows): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="awa-nominations.json"');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function _send_svg(array $rows): void {
    $counts = _counts_by_year($rows);
    $svg = _build_svg_chart($counts);
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="awa-nominations.svg"');
    header('Access-Control-Allow-Origin: *');
    echo $svg;
}

function _send_webp(array $rows): void {
    if (!function_exists('imagecreatetruecolor') || !function_exists('imagewebp')) {
        _send_fallback_webp();
        return;
    }

    $counts = _counts_by_year($rows);
    $width = 960;
    $height = 540;
    $img = imagecreatetruecolor($width, $height);

    $bg = imagecolorallocate($img, 244, 241, 234);
    $ink = imagecolorallocate($img, 31, 37, 40);
    $muted = imagecolorallocate($img, 104, 113, 118);
    $accent = imagecolorallocate($img, 179, 63, 49);
    $line = imagecolorallocate($img, 221, 215, 203);
    imagefilledrectangle($img, 0, 0, $width, $height, $bg);

    imagestring($img, 5, 40, 32, 'AwA - Nominations by Year', $ink);
    imagestring($img, 3, 40, 56, 'Filtered SAG actor nomination export', $muted);

    $left = 70;
    $top = 110;
    $plotWidth = 820;
    $plotHeight = 320;
    imageline($img, $left, $top + $plotHeight, $left + $plotWidth, $top + $plotHeight, $line);

    $years = array_keys($counts);
    $max = max(1, ...array_values($counts ?: [1]));
    $slot = $years ? $plotWidth / count($years) : $plotWidth;
    $barWidth = max(24, (int)($slot * 0.55));

    foreach ($years as $index => $year) {
        $value = $counts[$year];
        $barHeight = (int)(($value / $max) * $plotHeight);
        $x = (int)($left + $slot * $index + ($slot - $barWidth) / 2);
        $y = $top + $plotHeight - $barHeight;
        imagefilledrectangle($img, $x, $y, $x + $barWidth, $top + $plotHeight, $accent);
        imagestring($img, 4, $x + 6, max($top + 4, $y - 22), (string)$value, $ink);
        imagestring($img, 3, $x + 4, $top + $plotHeight + 14, (string)$year, $muted);
    }

    header('Content-Type: image/webp');
    header('Content-Disposition: attachment; filename="awa-nominations.webp"');
    header('Access-Control-Allow-Origin: *');
    imagewebp($img, null, 88);
    imagedestroy($img);
}

function _send_fallback_webp(): void {
    header('Content-Type: image/webp');
    header('Content-Disposition: attachment; filename="awa-nominations.webp"');
    header('Access-Control-Allow-Origin: *');
    header('X-AwA-Export-Note: Install PHP GD for chart-rendered WebP output');
    echo base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA');
}

function _counts_by_year(array $rows): array {
    $counts = [];
    foreach ($rows as $row) {
        $year = (int)$row['year'];
        $counts[$year] = ($counts[$year] ?? 0) + 1;
    }
    ksort($counts);
    return $counts;
}

function _build_svg_chart(array $counts): string {
    $width = 960;
    $height = 540;
    $left = 70;
    $top = 110;
    $plotWidth = 820;
    $plotHeight = 320;
    $max = max(1, ...array_values($counts ?: [1]));
    $years = array_keys($counts);
    $slot = $years ? $plotWidth / count($years) : $plotWidth;
    $barWidth = max(24, $slot * 0.55);
    $bars = '';

    foreach ($years as $index => $year) {
        $value = $counts[$year];
        $barHeight = ($value / $max) * $plotHeight;
        $x = $left + $slot * $index + ($slot - $barWidth) / 2;
        $y = $top + $plotHeight - $barHeight;
        $bars .= '<rect x="' . round($x, 2) . '" y="' . round($y, 2) . '" width="' . round($barWidth, 2) . '" height="' . round($barHeight, 2) . '" fill="#b33f31" rx="6"/>';
        $bars .= '<text x="' . round($x + $barWidth / 2, 2) . '" y="' . round(max($top + 18, $y - 10), 2) . '" text-anchor="middle">' . $value . '</text>';
        $bars .= '<text x="' . round($x + $barWidth / 2, 2) . '" y="' . ($top + $plotHeight + 34) . '" text-anchor="middle">' . $year . '</text>';
    }

    return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">'
        . '<rect width="100%" height="100%" fill="#f4f1ea"/>'
        . '<text x="40" y="48" font-family="Arial, sans-serif" font-size="26" font-weight="700" fill="#1f2528">AwA - Nominations by Year</text>'
        . '<text x="40" y="76" font-family="Arial, sans-serif" font-size="15" fill="#687176">Filtered SAG actor nomination export</text>'
        . '<line x1="' . $left . '" y1="' . ($top + $plotHeight) . '" x2="' . ($left + $plotWidth) . '" y2="' . ($top + $plotHeight) . '" stroke="#ddd7cb"/>'
        . '<g font-family="Arial, sans-serif" font-size="15" fill="#1f2528">' . $bars . '</g>'
        . '</svg>';
}
