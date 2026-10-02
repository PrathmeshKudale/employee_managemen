<?php
/**
 * employee/profile.php — View own details, update own email/contact.
 * Employees can only ever touch their own record (emp_id from session).
 */
require_once dirname(__DIR__) . '/config.php';
require_role('Employee');

$page_title = 'My Profile';
$empId      = (int) ($_SESSION['emp_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email   = trim((string) ($_POST['email'] ?? ''));
    $contact = trim((string) ($_POST['contact'] ?? ''));
    $errors  = [];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (mb_strlen($contact) > 30) $errors[] = 'Contact number is too long (max 30 chars).';

    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE employee SET email = ?, contact = ? WHERE emp_id = ?');
        $stmt->execute([$email, $contact, $empId]);
        set_flash('success', 'Your contact details were updated.');
        redirect('employee/profile.php');
    }
    foreach ($errors as $err) set_flash('error', $err);
}

$stmt = $pdo->prepare(
    'SELECT e.*, u.username FROM employee e JOIN users u ON u.user_id = e.user_id WHERE e.emp_id = ?'
);
$stmt->execute([$empId]);
$me = $stmt->fetch();

if (!$me) {
    set_flash('error', 'Profile not found. Please contact your administrator.');
    redirect('employee/dashboard.php');
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="card">
    <div class="card-header"><h2>Profile Details</h2></div>
    <div class="detail-grid">
        <div class="detail-item">
            <div class="detail-label">Full Name</div>
            <div class="detail-value"><?= e($me['name']) ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Username</div>
            <div class="detail-value"><?= e($me['username']) ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Department</div>
            <div class="detail-value"><?= e($me['department'] ?: '—') ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Position</div>
            <div class="detail-value"><?= e($me['position'] ?: '—') ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h2>Update Contact Details</h2></div>
    <form method="post" action="profile.php" data-validate>
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required maxlength="150" value="<?= e($me['email']) ?>">
            </div>
            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="text" id="contact" name="contact" maxlength="30" value="<?= e($me['contact'] ?? '') ?>">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
