<?php
/**
 * admin/dashboard.php — Admin overview: summary cards + latest notices.
 */
require_once dirname(__DIR__) . '/config.php';
require_role('Admin');

$page_title = 'Admin Dashboard';

$totalEmployees = (int) $pdo->query('SELECT COUNT(*) FROM employee')->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND status = 'Present'");
$stmt->execute();
$presentToday = (int) $stmt->fetchColumn();

$pendingLeave = (int) $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'")->fetchColumn();
$totalNotices = (int) $pdo->query('SELECT COUNT(*) FROM notices')->fetchColumn();

$latestNotices = $pdo->query('SELECT title, body, created_at FROM notices ORDER BY created_at DESC LIMIT 3')->fetchAll();
$recentLeaves  = $pdo->query(
    'SELECT l.leave_id, e.name, l.from_date, l.to_date, l.status
     FROM leave_requests l JOIN employee e ON e.emp_id = l.emp_id
     ORDER BY l.leave_id DESC LIMIT 5'
)->fetchAll();

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
            <div class="stat-value"><?= $totalEmployees ?></div>
            <div class="stat-label">Total Employees</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        </div>
        <div>
            <div class="stat-value"><?= $presentToday ?></div>
            <div class="stat-label">Present Today</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
        </div>
        <div>
            <div class="stat-value"><?= $pendingLeave ?></div>
            <div class="stat-label">Pending Leave Requests</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon indigo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/></svg>
        </div>
        <div>
            <div class="stat-value"><?= $totalNotices ?></div>
            <div class="stat-label">Notices Posted</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Recent Leave Requests</h2>
        <a class="btn btn-secondary btn-sm" href="leave.php">Manage Leave</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Employee</th><th>From</th><th>To</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php if (!$recentLeaves): ?>
                    <tr><td colspan="4" class="table-empty">No leave requests yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($recentLeaves as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['from_date']) ?></td>
                        <td><?= e($row['to_date']) ?></td>
                        <td><?= status_badge($row['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Latest Notices</h2>
        <a class="btn btn-secondary btn-sm" href="notices.php">Manage Notices</a>
    </div>
    <?php if (!$latestNotices): ?>
        <p class="table-empty">No notices posted yet.</p>
    <?php endif; ?>
    <?php foreach ($latestNotices as $notice): ?>
        <div class="notice-item">
            <h3><?= e($notice['title']) ?></h3>
            <div class="notice-meta"><?= e(date('d M Y, h:i A', strtotime($notice['created_at']))) ?></div>
            <div class="notice-body"><?= e(mb_strimwidth($notice['body'], 0, 220, '…')) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
