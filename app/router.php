<?php
$root = __DIR__;
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = parse_url($uri, PHP_URL_PATH);
$uri = rawurldecode($uri);
$uri = '/' . ltrim($uri, '/');

if ($uri === '/') {
    $uri = '/index.php';
}

$target = $root . $uri;

if ($uri === '/404.php') {
    http_response_code(404);
    require $root . '/404.php';
    return;
}

if (file_exists($target) && !is_dir($target)) {
    return false;
}

if (is_dir($target) && file_exists($target . '/index.php')) {
    return false;
}

http_response_code(404);
require $root . '/404.php';
