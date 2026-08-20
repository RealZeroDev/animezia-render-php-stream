<?php
declare(strict_types=1);

/**
 * A minimal PHP endpoint for one authorized test video.
 * Replace this fixed catalog with authenticated database-backed access before production.
 */
function allowCors(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = array_filter(array_map(
        'trim',
        explode(',', getenv('ALLOWED_ORIGINS') ?: '')
    ));

    if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
        header("Access-Control-Allow-Origin: {$origin}");
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
        header('Access-Control-Allow-Headers: Range, Content-Type');
        header('Access-Control-Expose-Headers: Content-Length, Content-Range, Accept-Ranges');
    }
}

allowCors();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD, OPTIONS');
    http_response_code(405);
    exit;
}

$anime = $_GET['anime'] ?? '';
$episode = filter_input(INPUT_GET, 'episode', FILTER_VALIDATE_INT);

$catalog = [
    'villager-level-999' => [
        1 => __DIR__ . '/episode-01.mp4',
    ],
];

$file = $catalog[$anime][$episode] ?? null;
if (!is_string($file) || !is_file($file) || !is_readable($file)) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Episode not found']);
    exit;
}

$size = filesize($file);
if ($size === false || $size === 0) {
    http_response_code(500);
    exit('Unable to read media file.');
}

$start = 0;
$end = $size - 1;
$status = 200;
$range = $_SERVER['HTTP_RANGE'] ?? '';

if ($range !== '' && preg_match('/^bytes=(\d*)-(\d*)$/', $range, $matches) === 1) {
    $start = $matches[1] === '' ? 0 : (int) $matches[1];
    $end = $matches[2] === '' ? $end : min((int) $matches[2], $end);

    if ($start > $end || $start >= $size) {
        header("Content-Range: bytes */{$size}");
        http_response_code(416);
        exit;
    }

    $status = 206;
}

$length = $end - $start + 1;
http_response_code($status);
header('Content-Type: video/mp4');
header('Accept-Ranges: bytes');
header("Content-Length: {$length}");

if ($status === 206) {
    header("Content-Range: bytes {$start}-{$end}/{$size}");
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
    exit;
}

$handle = fopen($file, 'rb');
if ($handle === false) {
    http_response_code(500);
    exit('Unable to open media file.');
}

fseek($handle, $start);
while (!feof($handle) && $length > 0) {
    $chunk = fread($handle, min(8192, $length));
    if ($chunk === false) {
        break;
    }
    echo $chunk;
    $length -= strlen($chunk);
    flush();
}

fclose($handle);
