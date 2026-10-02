<?php
/**
 * employee/leave.php — Apply for leave and track own request statuses.
 */
require_once dirname(__DIR__) . '/config.php';
require_role('Employee');

$page_title = 'My Leave';
$empId      = (int) ($_SESSION['emp_id'] ?? 0);
$perPage    = 10;

/* ---------------------- Apply for leave ---------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $from   = (string) ($_POST['from_date'] ?? '');
    $to     = (string) ($_POST['to_date'] ?? '');
    $reason = trim((string) ($_POST['reason'] ?? ''));
    $errors = [];

    $validFrom = DateTime::createFromFormat('Y-m-d', $from);
    $validTo   = DateTime::createFromFormat('Y-m-d', $to);

    if (!$validFrom || $validFrom->format('Y-m-d') !== $from) $errors[] = 'Please choose a valid start date.';
    if (!$validTo || $validTo->format('Y-m-d') !== $to)       $errors[] = 'Please choose a valid end date.';
    if (!$errors && $to < $from) $errors[] = 'End date cannot be before the start date.';
    if ($reason === '') $errors[] = 'Please provide a reason for your leave.';

    if (!$errors) {
        $stmt = $pdo->prepare(
            "INSERT INTO leave_requests (emp_id, from_date, to_date, reason, status) VALUES (?, ?, ?, ?, 'Pending')"
        );
        $stmt->execute([$empId, $from, $to, $reason]);
        set_flash('success', 'Leave request submitted. You will see the decision here once reviewed.');
        redirect('employee/leave.php');
    }
    foreach ($errors as $err) set_flash('error', $err);
}

/* ---------------------- Own requests ---------------------- */
$page = max(1, (int) ($_GET['page'] ?? 1));

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM leave_requests WHERE emp_id = ?');
$countStmt->execute([$empId]);
$totalRows  = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT * FROM leave_requests WHERE emp_id = ?
     ORDER BY leave_id DESC LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute([$empId]);
$requests = $stmt->fetchAll();

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="card">
    <div class="card-header"><h2>Apply for Leave</h2></div>
    <form method="post" action="leave.php" data-validate>
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label for="from_date">From Date *</label>
                <input type="date" id="from_date" name="from_date" required data-date-from
                       value="<?= e($_POST['from_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="to_date">To Date *</label>
                <input type="date" id="to_date" name="to_date" required data-date-to
                       value="<?= e($_POST['to_date'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="reason">Reason *</label>
            <textarea id="reason" name="reason" required maxlength="1000"
                      placeholder="Briefly explain why you need leave…"><?= e($_POST['reason'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Submit Request</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header"><h2>My Leave Requests (<?= $totalRows ?>)</h2></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>From</th><th>To</th><th>Reason</th><th>Status</th></tr></thead>
            <tbody>
                <?php if (!$requests): ?>
                    <tr><td colspan="5" class="table-empty">You haven't applied for leave yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($requests as $row): ?>
                    <tr>
                        <td><?= (int) $row['leave_id'] ?></td>
                        <td><?= e($row['from_date']) ?></td>
                        <td><?= e($row['to_date']) ?></td>
                        <td style="white-space:normal; max-width:300px;"><?= e($row['reason']) ?></td>
                        <td><?= status_badge($row['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= render_pagination($page, $totalPages) ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
