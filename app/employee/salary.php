<?php
/**
 * employee/salary.php — View own salary slips.
 */
require_once dirname(__DIR__) . '/config.php';
require_role('Employee');

$page_title = 'My Salary';
$empId      = (int) ($_SESSION['emp_id'] ?? 0);
$perPage    = 12;

$page = max(1, (int) ($_GET['page'] ?? 1));

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM salary WHERE emp_id = ?');
$countStmt->execute([$empId]);
$totalRows  = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT * FROM salary WHERE emp_id = ?
     ORDER BY salary_month DESC LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute([$empId]);
$slips = $stmt->fetchAll();

$latest = $slips[0] ?? null;

require dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($latest): ?>
<div class="card">
    <div class="card-header"><h2>Latest Salary Slip — <?= e(date('F Y', strtotime($latest['salary_month']))) ?></h2></div>
    <div class="detail-grid">
        <div class="detail-item">
            <div class="detail-label">Basic Salary</div>
            <div class="detail-value"><?= number_format((float) $latest['basic_salary'], 2) ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Allowance</div>
            <div class="detail-value"><?= number_format((float) $latest['allowance'], 2) ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Deduction</div>
            <div class="detail-value"><?= number_format((float) $latest['deduction'], 2) ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Net Salary</div>
            <div class="detail-value" style="color:var(--green)"><?= number_format((float) $latest['net_salary'], 2) ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h2>Salary History (<?= $totalRows ?>)</h2></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Month</th><th>Basic</th><th>Allowance</th><th>Deduction</th><th>Net Salary</th></tr>
            </thead>
            <tbody>
                <?php if (!$slips): ?>
                    <tr><td colspan="5" class="table-empty">No salary records available yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($slips as $row): ?>
                    <tr>
                        <td><?= e(date('M Y', strtotime($row['salary_month']))) ?></td>
                        <td><?= number_format((float) $row['basic_salary'], 2) ?></td>
                        <td><?= number_format((float) $row['allowance'], 2) ?></td>
                        <td><?= number_format((float) $row['deduction'], 2) ?></td>
                        <td><strong><?= number_format((float) $row['net_salary'], 2) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= render_pagination($page, $totalPages) ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
