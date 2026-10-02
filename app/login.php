<?php
/**
 * login.php — Username + password login with role-based redirect.
 */
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT u.user_id, u.username, u.password, r.role_name
             FROM users u
             JOIN roles r ON r.role_id = u.role_id
             WHERE u.username = ?
             LIMIT 1'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Find linked employee record (employees only).
            $empId = null;
            if ($user['role_name'] === 'Employee') {
                $stmt = $pdo->prepare('SELECT emp_id FROM employee WHERE user_id = ? LIMIT 1');
                $stmt->execute([$user['user_id']]);
                $emp = $stmt->fetch();
                $empId = $emp ? (int) $emp['emp_id'] : null;
            }

            session_regenerate_id(true);
            $_SESSION['user_id']   = (int) $user['user_id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['emp_id']    = $empId;
            unset($_SESSION['csrf_token']);

            set_flash('success', 'Welcome back, ' . $user['username'] . '!');
            redirect($user['role_name'] === 'Admin' ? 'admin/dashboard.php' : 'employee/dashboard.php');
        }

        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Sign In — Employee Management System</title>
    <link rel="stylesheet" href="<?= e(app_base()) ?>/assets/style.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <div class="auth-brand">
            <div class="auth-logo">EMS</div>
            <h1>Employee Management System</h1>
            <p>Sign in to continue to your workspace</p>
        </div>

        <?php foreach (get_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(app_base()) ?>/login.php" id="loginForm" novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required maxlength="80"
                       value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username" autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>

        <div class="auth-hint">
            <strong>Demo accounts</strong>
            <span>Admin — <code>admin / admin123</code></span>
            <span>Employee — <code>rsharma / emp123</code></span>
        </div>
    </main>
    <script src="<?= e(app_base()) ?>/assets/app.js"></script>
</body>
</html>
