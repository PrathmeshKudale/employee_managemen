<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);

if (!is_string($path)) {
    $path = '/';
}

$path = '/' . ltrim(rawurldecode($path), '/');
if ($path === '/') {
    $path = '/index.php';
}

$relativePath = ltrim($path, '/');
$isPublicPage = in_array($relativePath, [
    'index.php',
    'login.php',
    'logout.php',
    'dashboard.php',
    '404.php',
], true)
    || preg_match('#^(admin|employee)/[A-Za-z0-9_-]+\.php$#', $relativePath) === 1;

$target = realpath($root . DIRECTORY_SEPARATOR . $relativePath);
$resolvedRoot = realpath($root);

if (
    !$isPublicPage
    || $target === false
    || $resolvedRoot === false
    || !str_starts_with($target, $resolvedRoot . DIRECTORY_SEPARATOR)
    || !is_file($target)
    || strtolower(pathinfo($target, PATHINFO_EXTENSION)) !== 'php'
) {
    http_response_code(404);
    require $root . '/404.php';
    exit;
}

$_SERVER['SCRIPT_NAME'] = $path;
$_SERVER['PHP_SELF'] = $path;
$_SERVER['SCRIPT_FILENAME'] = $target;

require $target;
