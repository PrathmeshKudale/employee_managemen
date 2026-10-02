<?php
/**
 * index.php — Entry point: send visitors to login or their dashboard.
 */
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}
redirect('login.php');
