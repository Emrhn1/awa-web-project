<?php
// Bu dosya, gelen HTTP isteklerini karşılamak
//  ve uygun controller fonksiyonlarını çağırmak için basit bir router görevi görür.
// Her http isteği bu dosyaya gelir, 
// Bu dosya bizim tüm controller fonksiyonlarımızı içe aktarır ve 
// gelen isteğin yoluna göre ilgili fonksiyonu çağırır.

// PHP'nin dahili web sunucusunu kullanırken, gerçek dosyalar varsa onları sunmasına izin vermek için bu kontrolü ekliyoruz.
if (php_sapi_name() === 'cli-server') {
    $reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $reqPath;
    if ($reqPath !== '/' && file_exists($file) && !is_dir($file)) {
        // eğer bu dosya gerçekten varsa, PHP'nin dahili sunucusının onu sunmasına izin veriyoruz. 
        // Yani tarayıcıdan /style.css gibi bir istek gelirse, bu dosya varsa doğrudan sunulacak, yoksa router işlemi devam edecek.
        return false;
    }
}

// Dosyaları bir kez yükle sonra tekrar yüklemeye gerek yok,
//  bu yüzden require_once kullanıyoruz. Sıralama önemli çünkü bazı dosyalar diğerlerine bağımlı olabilir.
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/env.php';
require_once __DIR__ . '/../src/response.php';
require_once __DIR__ . '/../src/validate.php';
require_once __DIR__ . '/../src/controllers/nominations.php';
require_once __DIR__ . '/../src/controllers/actors.php';
require_once __DIR__ . '/../src/controllers/films.php';
require_once __DIR__ . '/../src/controllers/editions.php';
require_once __DIR__ . '/../src/controllers/categories.php';
require_once __DIR__ . '/../src/controllers/tmdb.php';
require_once __DIR__ . '/../src/controllers/news.php';
require_once __DIR__ . '/../src/controllers/export.php';
require_once __DIR__ . '/../src/controllers/import.php';

set_exception_handler(function (Throwable $e) {
    server_error($e->getMessage());
});

// get post put delete options gibi http methodlarına göre route işlemleri yapıyoruz.
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
// $_SERVER['REQUEST_URI']'den yolu alıyoruz, örneğin /api/nominations?year=2024&winner=1
//  gibi bir istek geldiğinde, parse_url ile sadece /api/nominations kısmını alıyoruz.
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
// gelen yolun sonunda gereksiz bir / varsa onu kaldırıyoruz.
$path   = rtrim($path, '/');
if ($path === '') {
    $path = '/';
}

// OPTIONS istekleri genellikle CORS preflight istekleri için gönderilir. 
// Bu isteklerde, hangi yöntemlerin ve başlıkların izinli olduğunu belirtmemiz gerekir. 
// Eğer gelen istek OPTIONS ise, gerekli CORS başlıklarını ekleyip 204 No Content ile yanıt veriyoruz
//  ve işlemi sonlandırıyoruz.
// Bir tarayıcı, farklı bir kaynaktan (örneğin frontend uygulamamızdan) API'mize istek göndermek istediğinde,
// önce bir OPTIONS isteği gönderir ve bu istekte hangi yöntemlerin 
// ve başlıkların izinli olduğunu kontrol eder. Eğer bu başlıklar uygun değilse, tarayıcı gerçek isteği göndermez.
//  Bu yüzden burada gerekli CORS başlıklarını ekleyip 204 No Content ile yanıt veriyoruz ve işlemi sonlandırıyoruz.
if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

// Basit bir router işlemi yapıyoruz. Gelen isteklerin yoluna
//  ve yöntemine göre ilgili controller fonksiyonlarını çağırıyoruz.
// localhost:8000/api yazınca API hakkında bilgi veren bir JSON döndürüyoruz.
if ($path === '/' || $path === '/api') {
    json_response([
        'name'      => 'AwA API',
        'version'   => '0.4.0',
        'phase'     => 4,
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
            'GET    /api/enrich/actor/{id}',
            'GET    /api/enrich/film/{id}',
            'GET    /api/news?actor=...',
            'GET    /api/news-sources',
            'POST   /api/news-sources',
            'PUT    /api/news-sources/{id}',
            'DELETE /api/news-sources/{id}',
            'GET    /api/export/csv',
            'GET    /api/export/json',
            'GET    /api/export/svg',
            'GET    /api/export/webp',
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
// /api/films/{id} şeklinde bir istek atıyoruz. burada id 10 15 gibi bir sayı olabilir
// preg_match ile bu yolu kontrol ediyoruz. Eğer bu yol eşleşirse, id'yi alıp get_film fonksiyonunu çağırıyoruz.
// (\d+) ifadesi bir veya daha fazla rakamı temsil eder. Yani /api/films/10 veya /api/films/15 gibi yollar bu desene uyar.
// $m ise eşleşen parçaları buraya koyar. $m[0] = "/api/films/10", $m[1] = "10" gibi. Biz burada $m[1] ile id'yi alıyoruz.
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

// TMDb ve enrichment endpointleri sadece GET isteklerini kabul eder.
if ($method === 'GET') {
    if (preg_match('#^/api/tmdb/actor/(\d+)$#', $path, $m)) {
        tmdb_actor((int)$m[1]); exit;
    }
    if (preg_match('#^/api/tmdb/movie/(\d+)$#', $path, $m)) {
        tmdb_movie((int)$m[1]); exit;
    }
    if ($path === '/api/tmdb/search/actor') { tmdb_search_actor(); exit; }
    if ($path === '/api/tmdb/search/movie') { tmdb_search_movie(); exit; }
    if (preg_match('#^/api/enrich/actor/(\d+)$#', $path, $m)) {
        enrich_actor((int)$m[1]); exit;
    }
    if (preg_match('#^/api/enrich/film/(\d+)$#', $path, $m)) {
        enrich_film((int)$m[1]); exit;
    }
    if ($path === '/api/news') { list_news(); exit; }
    // csv, json, svg veya webp formatlarında veri ihracı yapmak için
    //  /api/export/{format} endpointini kullanıyoruz.

    if (preg_match('#^/api/export/(csv|json|svg|webp)$#', $path, $m)) {
        export_data($m[1]); exit;
    }
}

if ($path === '/api/import/csv' && $method === 'POST') { import_csv(); exit; }
if ($path === '/api/import/json' && $method === 'POST') { import_json(); exit; }

if ($path === '/api/news-sources') {
    if ($method === 'GET')  { list_news_sources(); exit; }
    if ($method === 'POST') { create_news_source(); exit; }
    json_response(['error' => 'Method not allowed'], 405); exit;
}
if (preg_match('#^/api/news-sources/(\d+)$#', $path, $m)) {
    $id = (int)$m[1];
    if ($method === 'GET')    { get_news_source($id); exit; }
    if ($method === 'PUT')    { update_news_source($id); exit; }
    if ($method === 'DELETE') { delete_news_source($id); exit; }
    json_response(['error' => 'Method not allowed'], 405); exit;
}

not_found('Unknown route: ' . $path);
