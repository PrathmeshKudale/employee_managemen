<?php
/**
 * dashboard.php — Role-based redirect to the correct dashboard.
 */
require_once __DIR__ . '/config.php';
require_login();

redirect(current_role() === 'Admin' ? 'admin/dashboard.php' : 'employee/dashboard.php');
