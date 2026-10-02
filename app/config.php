<?php
/**
 * config.php — Database connection (PDO), session bootstrap, shared helpers.
 * Defaults match a fresh XAMPP install (root / no password). Override with
 * DB_HOST, DB_PORT, DB_NAME, DB_USER, and DB_PASS when deploying. Set
 * DB_SSL_CA to a CA certificate path when the database requires TLS.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'employee_management');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$sslCa = getenv('DB_SSL_CA');
if ($sslCa !== false && $sslCa !== '') {
    $sslCaPath = str_starts_with($sslCa, DIRECTORY_SEPARATOR)
        ? $sslCa
        : __DIR__ . DIRECTORY_SEPARATOR . $sslCa;

    if (!is_file($sslCaPath) || !class_exists(PDO::class . '\\Mysql')) {
        http_response_code(500);
        exit('Database TLS is configured, but its CA file is missing or PDO MySQL TLS support is unavailable.');
    }

    $pdoOptions[\Pdo\Mysql::ATTR_SSL_CA] = $sslCaPath;
    if (defined('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')) {
        $pdoOptions[\Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        $pdoOptions
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Verify DB_HOST, DB_PORT, DB_NAME, DB_USER, and DB_PASS in your deployment environment, configure DB_SSL_CA if required by your provider, and make sure the database schema has been imported.');
}

/* ----------------------------------------------------------------
 * Output escaping (XSS protection)
 * --------------------------------------------------------------*/
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/* ----------------------------------------------------------------
 * CSRF protection
 * --------------------------------------------------------------*/
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Invalid or missing CSRF token. Please go back and try again.');
    }
}

/* ----------------------------------------------------------------
 * Flash messages
 * --------------------------------------------------------------*/
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/* ----------------------------------------------------------------
 * Authentication / authorization guards
 * --------------------------------------------------------------*/
function is_logged_in(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role_name']);
}

function current_role(): string
{
    return $_SESSION['role_name'] ?? '';
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . app_base() . '/login.php');
        exit;
    }
}

function require_role(string $role): void
{
    require_login();
    if (current_role() !== $role) {
        // Logged in with the wrong role: send them to their own area.
        header('Location: ' . app_base() . '/dashboard.php');
        exit;
    }
}

/* ----------------------------------------------------------------
 * URL helper — resolves the app base path (works from /admin, /employee, root)
 * --------------------------------------------------------------*/
function app_base(): string
{
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    // Strip trailing /admin or /employee so links work from any subfolder.
    $base = preg_replace('#/(admin|employee)$#', '', $script);
    return rtrim($base, '/') === '' ? '' : rtrim($base, '/');
}

/**
 * Redirect helper that respects the app base path.
 */
function redirect(string $path): void
{
    header('Location: ' . app_base() . '/' . ltrim($path, '/'));
    exit;
}

/* ----------------------------------------------------------------
 * Pagination helper — renders page links preserving query params
 * --------------------------------------------------------------*/
function render_pagination(int $page, int $totalPages, array $params = []): string
{
    if ($totalPages <= 1) {
        return '';
    }
    $html = '<nav class="pagination" aria-label="Pagination">';
    $link = static function (int $p) use ($params): string {
        $params['page'] = $p;
        return '?' . e(http_build_query($params));
    };

    $html .= $page > 1
        ? '<a href="' . $link($page - 1) . '">&laquo; Prev</a>'
        : '<span class="disabled">&laquo; Prev</span>';

    for ($p = 1; $p <= $totalPages; $p++) {
        $html .= $p === $page
            ? '<span class="current">' . $p . '</span>'
            : '<a href="' . $link($p) . '">' . $p . '</a>';
    }

    $html .= $page < $totalPages
        ? '<a href="' . $link($page + 1) . '">Next &raquo;</a>'
        : '<span class="disabled">Next &raquo;</span>';

    return $html . '</nav>';
}

/**
 * Map a status word to a badge CSS class.
 */
function status_badge(string $status): string
{
    return match ($status) {
        'Present', 'Approved' => '<span class="badge badge-green">'  . e($status) . '</span>',
        'Absent', 'Rejected'  => '<span class="badge badge-red">'    . e($status) . '</span>',
        default               => '<span class="badge badge-yellow">' . e($status) . '</span>',
    };
}
