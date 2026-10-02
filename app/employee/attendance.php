<?php
/**
 * employee/attendance.php — View own monthly attendance history.
 */
require_once dirname(__DIR__) . '/config.php';
require_role('Employee');

$page_title = 'My Attendance';
$empId      = (int) ($_SESSION['emp_id'] ?? 0);

$month = (string) ($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

$stmt = $pdo->prepare(
    "SELECT attendance_date, status FROM attendance
     WHERE emp_id = ? AND DATE_FORMAT(attendance_date, '%Y-%m') = ?
     ORDER BY attendance_date DESC"
);
$stmt->execute([$empId, $month]);
$records = $stmt->fetchAll();

$counts = ['Present' => 0, 'Absent' => 0, 'Leave' => 0];
foreach ($records as $r) {
    if (isset($counts[$r['status']])) $counts[$r['status']]++;
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Attendance — <?= e(date('F Y', strtotime($month . '-01'))) ?></h2>
    </div>

    <form class="filters" method="get" action="attendance.php">
        <div class="form-group">
            <label for="month">Select month</label>
            <input type="month" id="month" name="month" value="<?= e($month) ?>" max="<?= e(date('Y-m')) ?>">
        </div>
        <button type="submit" class="btn btn-secondary">View</button>
    </form>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4L12 14.01l-3-3"/></svg>
            </div>
            <div><div class="stat-value"><?= $counts['Present'] ?></div><div class="stat-label">Present</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            </div>
            <div><div class="stat-value"><?= $counts['Leave'] ?></div><div class="stat-label">On Leave</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow" style="background:var(--red-bg); color:var(--red)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
            </div>
            <div><div class="stat-value"><?= $counts['Absent'] ?></div><div class="stat-label">Absent</div></div>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Day</th><th>Status</th></tr></thead>
            <tbody>
                <?php if (!$records): ?>
                    <tr><td colspan="3" class="table-empty">No attendance records for this month.</td></tr>
                <?php endif; ?>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <td><?= e($row['attendance_date']) ?></td>
                        <td><?= e(date('l', strtotime($row['attendance_date']))) ?></td>
                        <td><?= status_badge($row['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
