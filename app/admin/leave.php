<?php
/**
 * admin/leave.php — Review leave requests, approve or reject them.
 */
require_once dirname(__DIR__) . '/config.php';
require_role('Admin');

$page_title = 'Leave Requests';
$perPage    = 10;

/* ---------------------- Approve / reject ---------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $leaveId = (int) ($_POST['leave_id'] ?? 0);
    $decision = (string) ($_POST['decision'] ?? '');

    if (!in_array($decision, ['Approved', 'Rejected'], true)) {
        set_flash('error', 'Invalid decision.');
    } else {
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE leave_id = ? AND status = 'Pending'");
        $stmt->execute([$decision, $leaveId]);
        set_flash($stmt->rowCount() ? 'success' : 'error',
                  $stmt->rowCount() ? "Leave request {$decision}." : 'Request not found or already decided.');
    }
    redirect('admin/leave.php');
}

/* ---------------------- Lists ---------------------- */
$pendingCount = (int) $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'")->fetchColumn();

$pending = $pdo->query(
    "SELECT l.*, e.name, e.department
     FROM leave_requests l JOIN employee e ON e.emp_id = l.emp_id
     WHERE l.status = 'Pending'
     ORDER BY l.leave_id ASC"
)->fetchAll();

$statusFilter = (string) ($_GET['status'] ?? '');
$page         = max(1, (int) ($_GET['page'] ?? 1));
$where        = in_array($statusFilter, ['Pending', 'Approved', 'Rejected'], true) ? 'WHERE l.status = ?' : '';
$params       = $where ? [$statusFilter] : [];

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM leave_requests l {$where}");
$countStmt->execute($params);
$totalRows  = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$listStmt = $pdo->prepare(
    "SELECT l.*, e.name
     FROM leave_requests l JOIN employee e ON e.emp_id = l.emp_id
     {$where}
     ORDER BY l.leave_id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$listStmt->execute($params);
$history = $listStmt->fetchAll();

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Pending Requests (<?= $pendingCount ?>)</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Employee</th><th>Department</th><th>From</th><th>To</th><th>Reason</th><th>Decision</th></tr>
            </thead>
            <tbody>
                <?php if (!$pending): ?>
                    <tr><td colspan="6" class="table-empty">No pending leave requests. You're all caught up.</td></tr>
                <?php endif; ?>
                <?php foreach ($pending as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['department']) ?></td>
                        <td><?= e($row['from_date']) ?></td>
                        <td><?= e($row['to_date']) ?></td>
                        <td style="white-space:normal; max-width:260px;"><?= e($row['reason']) ?></td>
                        <td>
                            <div class="btn-group">
                                <form method="post" action="leave.php" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="leave_id" value="<?= (int) $row['leave_id'] ?>">
                                    <input type="hidden" name="decision" value="Approved">
                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                </form>
                                <form method="post" action="leave.php" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="leave_id" value="<?= (int) $row['leave_id'] ?>">
                                    <input type="hidden" name="decision" value="Rejected">
                                    <button type="submit" class="btn btn-sm btn-danger"
                                            data-confirm="Reject this leave request from <?= e($row['name']) ?>?">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>All Requests (<?= $totalRows ?>)</h2>
    </div>

    <form class="filters" method="get" action="leave.php">
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                <?php foreach (['Pending', 'Approved', 'Rejected'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $statusFilter === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a class="btn btn-secondary" href="leave.php">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Employee</th><th>From</th><th>To</th><th>Reason</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php if (!$history): ?>
                    <tr><td colspan="6" class="table-empty">No leave requests found.</td></tr>
                <?php endif; ?>
                <?php foreach ($history as $row): ?>
                    <tr>
                        <td><?= (int) $row['leave_id'] ?></td>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['from_date']) ?></td>
                        <td><?= e($row['to_date']) ?></td>
                        <td style="white-space:normal; max-width:300px;"><?= e($row['reason']) ?></td>
                        <td><?= status_badge($row['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?= render_pagination($page, $totalPages, $statusFilter !== '' ? ['status' => $statusFilter] : []) ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
