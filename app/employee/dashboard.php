<?php
/**
 * employee/dashboard.php — Welcome, today's attendance, latest notice,
 * pending leave overview.
 */
require_once dirname(__DIR__) . '/config.php';
require_role('Employee');

$page_title = 'My Dashboard';
$empId      = (int) ($_SESSION['emp_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM employee WHERE emp_id = ?');
$stmt->execute([$empId]);
$me = $stmt->fetch();

$todayStatus = null;
$stmt = $pdo->prepare('SELECT status FROM attendance WHERE emp_id = ? AND attendance_date = CURDATE()');
$stmt->execute([$empId]);
$row = $stmt->fetch();
if ($row) $todayStatus = $row['status'];

$latestNotice = $pdo->query('SELECT title, body, created_at FROM notices ORDER BY created_at DESC LIMIT 1')->fetch();

$stmt = $pdo->prepare(
    "SELECT from_date, to_date, reason, status FROM leave_requests
     WHERE emp_id = ? AND status = 'Pending' ORDER BY leave_id DESC LIMIT 3"
);
$stmt->execute([$empId]);
$pendingLeaves = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM attendance WHERE emp_id = ? AND status = 'Present'
     AND DATE_FORMAT(attendance_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')"
);
$stmt->execute([$empId]);
$presentThisMonth = (int) $stmt->fetchColumn();

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="card">
    <h2>Welcome, <?= e($me['name'] ?? ($_SESSION['username'] ?? '')) ?> 👋</h2>
    <p style="color:var(--text-muted)">
        <?= e($me['position'] ?? '') ?><?= $me && $me['department'] ? ' · ' . e($me['department']) : '' ?>
        — here's your summary for <?= e(date('l, d F Y')) ?>.
    </p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon <?= $todayStatus === 'Present' ? 'green' : ($todayStatus ? 'yellow' : 'blue') ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        </div>
        <div>
            <div class="stat-value" style="font-size:1.15rem">
                <?= $todayStatus ? status_badge($todayStatus) : 'Not marked' ?>
            </div>
            <div class="stat-label">Today's Attendance</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4L12 14.01l-3-3"/></svg>
        </div>
        <div>
            <div class="stat-value"><?= $presentThisMonth ?></div>
            <div class="stat-label">Days Present This Month</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
        </div>
        <div>
            <div class="stat-value"><?= count($pendingLeaves) ?></div>
            <div class="stat-label">Pending Leave Requests</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Latest Notice</h2>
        <a class="btn btn-secondary btn-sm" href="notifications.php">All Notices</a>
    </div>
    <?php if (!$latestNotice): ?>
        <p class="table-empty">No notices posted yet.</p>
    <?php else: ?>
        <h3><?= e($latestNotice['title']) ?></h3>
        <div class="notice-meta"><?= e(date('d M Y, h:i A', strtotime($latestNotice['created_at']))) ?></div>
        <div class="notice-body"><?= e($latestNotice['body']) ?></div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h2>My Pending Leave</h2>
        <a class="btn btn-secondary btn-sm" href="leave.php">Apply for Leave</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>From</th><th>To</th><th>Reason</th><th>Status</th></tr></thead>
            <tbody>
                <?php if (!$pendingLeaves): ?>
                    <tr><td colspan="4" class="table-empty">No pending leave requests.</td></tr>
                <?php endif; ?>
                <?php foreach ($pendingLeaves as $row): ?>
                    <tr>
                        <td><?= e($row['from_date']) ?></td>
                        <td><?= e($row['to_date']) ?></td>
                        <td style="white-space:normal; max-width:280px;"><?= e($row['reason']) ?></td>
                        <td><?= status_badge($row['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
