<?php
/**
 * includes/header.php — Shared layout: sidebar nav (per role), topbar, flashes.
 * Expects: $page_title (string). Call after the appropriate require_role() guard.
 */
if (!defined('DB_NAME')) {
    exit('Direct access not allowed.');
}

$page_title  = $page_title ?? 'Dashboard';
$base        = app_base();
$role        = current_role();
$currentPage = basename($_SERVER['SCRIPT_NAME']);

$nav = $role === 'Admin'
    ? [
        ['dashboard.php',  'Dashboard',     'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
        ['employees.php',  'Employees',     'M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75'],
        ['attendance.php', 'Attendance',    'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11'],
        ['salary.php',     'Salary',        'M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'],
        ['leave.php',      'Leave Requests','M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'],
        ['notices.php',    'Notices',       'M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0'],
      ]
    : [
        ['dashboard.php',     'Dashboard',     'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
        ['profile.php',       'My Profile',    'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8'],
        ['attendance.php',    'My Attendance', 'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11'],
        ['salary.php',        'My Salary',     'M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'],
        ['leave.php',         'My Leave',      'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'],
        ['notifications.php', 'Notifications', 'M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0'],
      ];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= e($page_title) ?> — EMS</title>
    <link rel="stylesheet" href="<?= e($base) ?>/assets/style.css">
</head>
<body>
<div class="layout">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span class="brand-mark">EMS</span>
            <span class="brand-text">Employee MS</span>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($nav as [$file, $label, $icon]): ?>
                <a href="<?= e($base . '/' . strtolower($role) . '/' . $file) ?>"
                   class="nav-link <?= $currentPage === $file ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="<?= e($icon) ?>"/>
                    </svg>
                    <span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= e($base) ?>/logout.php" class="nav-link nav-logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="hamburger" id="hamburger" aria-label="Toggle navigation" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
            <h1 class="topbar-title"><?= e($page_title) ?></h1>
            <div class="topbar-user">
                <span class="user-badge"><?= e($role) ?></span>
                <span class="user-name"><?= e($_SESSION['username'] ?? '') ?></span>
            </div>
        </header>

        <main class="content">
            <?php foreach (get_flashes() as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="alert-close" aria-label="Dismiss">&times;</button>
                </div>
            <?php endforeach; ?>
