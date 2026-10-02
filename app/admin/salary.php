<?php
/**
 * admin/salary.php — Add / edit / delete monthly salary records.
 * net_salary is auto-calculated as basic + allowance - deduction.
 */
require_once dirname(__DIR__) . '/config.php';
require_role('Admin');

$page_title = 'Salary Management';
$perPage    = 10;

/* ---------------------- Handle POST actions ---------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'update') {
        $empId     = (int) ($_POST['emp_id'] ?? 0);
        $month     = (string) ($_POST['salary_month'] ?? '');   // "YYYY-MM" from <input type="month">
        $basic     = (float) ($_POST['basic_salary'] ?? 0);
        $allowance = (float) ($_POST['allowance'] ?? 0);
        $deduction = (float) ($_POST['deduction'] ?? 0);
        $errors    = [];

        $stmt = $pdo->prepare('SELECT emp_id FROM employee WHERE emp_id = ?');
        $stmt->execute([$empId]);
        if (!$stmt->fetch()) $errors[] = 'Please choose a valid employee.';

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) $errors[] = 'Please choose a valid salary month.';
        if ($basic < 0 || $allowance < 0 || $deduction < 0) $errors[] = 'Amounts cannot be negative.';

        $salaryMonth = $month !== '' ? $month . '-01' : '';
        $net         = round($basic + $allowance - $deduction, 2);

        if (!$errors) {
            if ($action === 'add') {
                $stmt = $pdo->prepare(
                    'INSERT INTO salary (emp_id, salary_month, basic_salary, allowance, deduction, net_salary)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$empId, $salaryMonth, $basic, $allowance, $deduction, $net]);
                set_flash('success', 'Salary record added. Net salary: ' . number_format($net, 2));
            } else {
                $salaryId = (int) ($_POST['salary_id'] ?? 0);
                $stmt = $pdo->prepare(
                    'UPDATE salary SET emp_id = ?, salary_month = ?, basic_salary = ?, allowance = ?, deduction = ?, net_salary = ?
                     WHERE salary_id = ?'
                );
                $stmt->execute([$empId, $salaryMonth, $basic, $allowance, $deduction, $net, $salaryId]);
                set_flash('success', 'Salary record updated. Net salary: ' . number_format($net, 2));
            }
            redirect('admin/salary.php');
        }
        foreach ($errors as $err) set_flash('error', $err);
    }

    if ($action === 'delete') {
        $salaryId = (int) ($_POST['salary_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM salary WHERE salary_id = ?');
        $stmt->execute([$salaryId]);
        set_flash($stmt->rowCount() ? 'success' : 'error',
                  $stmt->rowCount() ? 'Salary record deleted.' : 'Salary record not found.');
        redirect('admin/salary.php');
    }
}

/* ---------------------- Edit target ---------------------- */
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM salary WHERE salary_id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}

/* ---------------------- List ---------------------- */
$allEmployees = $pdo->query('SELECT emp_id, name FROM employee ORDER BY name')->fetchAll();
$empFilter    = (int) ($_GET['emp_id'] ?? 0);
$page         = max(1, (int) ($_GET['page'] ?? 1));

$where  = $empFilter > 0 ? 'WHERE s.emp_id = ?' : '';
$params = $empFilter > 0 ? [$empFilter] : [];

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM salary s {$where}");
$countStmt->execute($params);
$totalRows  = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$listStmt = $pdo->prepare(
    "SELECT s.*, e.name
     FROM salary s JOIN employee e ON e.emp_id = s.emp_id
     {$where}
     ORDER BY s.salary_month DESC, e.name
     LIMIT {$perPage} OFFSET {$offset}"
);
$listStmt->execute($params);
$records = $listStmt->fetchAll();

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><?= $editing ? 'Edit Salary Record' : 'Add Salary Record' ?></h2>
        <?php if ($editing): ?>
            <a class="btn btn-secondary btn-sm" href="salary.php">Cancel Edit</a>
        <?php endif; ?>
    </div>
    <form method="post" action="salary.php" data-validate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="salary_id" value="<?= (int) $editing['salary_id'] ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label for="emp_id">Employee *</label>
                <select id="emp_id" name="emp_id" required>
                    <option value="">— Select employee —</option>
                    <?php foreach ($allEmployees as $emp): ?>
                        <option value="<?= (int) $emp['emp_id'] ?>"
                            <?= ($editing && (int) $editing['emp_id'] === (int) $emp['emp_id']) ? 'selected' : '' ?>>
                            <?= e($emp['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="salary_month">Salary Month *</label>
                <input type="month" id="salary_month" name="salary_month" required
                       value="<?= e($editing ? substr($editing['salary_month'], 0, 7) : date('Y-m')) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="basic_salary">Basic Salary *</label>
                <input type="number" id="basic_salary" name="basic_salary" required min="0" step="0.01"
                       value="<?= e($editing['basic_salary'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="allowance">Allowance</label>
                <input type="number" id="allowance" name="allowance" min="0" step="0.01"
                       value="<?= e($editing['allowance'] ?? '0') ?>">
            </div>
            <div class="form-group">
                <label for="deduction">Deduction</label>
                <input type="number" id="deduction" name="deduction" min="0" step="0.01"
                       value="<?= e($editing['deduction'] ?? '0') ?>">
            </div>
        </div>
        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:0.5rem;">
            Net salary is calculated automatically: <strong>basic + allowance − deduction</strong>.
        </p>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Update Record' : 'Add Record' ?></button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2>Salary Records (<?= $totalRows ?>)</h2>
    </div>

    <form class="filters" method="get" action="salary.php">
        <div class="form-group">
            <label for="filter_emp">Employee</label>
            <select id="filter_emp" name="emp_id">
                <option value="0">All employees</option>
                <?php foreach ($allEmployees as $emp): ?>
                    <option value="<?= (int) $emp['emp_id'] ?>" <?= $empFilter === (int) $emp['emp_id'] ? 'selected' : '' ?>>
                        <?= e($emp['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a class="btn btn-secondary" href="salary.php">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Month</th><th>Employee</th><th>Basic</th><th>Allowance</th>
                    <th>Deduction</th><th>Net Salary</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$records): ?>
                    <tr><td colspan="7" class="table-empty">No salary records found.</td></tr>
                <?php endif; ?>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <td><?= e(date('M Y', strtotime($row['salary_month']))) ?></td>
                        <td><?= e($row['name']) ?></td>
                        <td><?= number_format((float) $row['basic_salary'], 2) ?></td>
                        <td><?= number_format((float) $row['allowance'], 2) ?></td>
                        <td><?= number_format((float) $row['deduction'], 2) ?></td>
                        <td><strong><?= number_format((float) $row['net_salary'], 2) ?></strong></td>
                        <td>
                            <div class="btn-group">
                                <a class="btn btn-sm btn-secondary" href="salary.php?edit=<?= (int) $row['salary_id'] ?>">Edit</a>
                                <form method="post" action="salary.php" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="salary_id" value="<?= (int) $row['salary_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"
                                            data-confirm="Delete this salary record for <?= e($row['name']) ?>?">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?= render_pagination($page, $totalPages, $empFilter ? ['emp_id' => $empFilter] : []) ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
