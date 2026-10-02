<?php
/**
 * admin/attendance.php — Mark daily attendance and review records
 * filtered by date range and employee.
 */
require_once dirname(__DIR__) . '/config.php';
require_role('Admin');

$page_title = 'Attendance';
$perPage    = 10;

/* ---------------------- Mark attendance ---------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $date      = (string) ($_POST['attendance_date'] ?? '');
    $statuses  = $_POST['status'] ?? [];
    $validDate = DateTime::createFromFormat('Y-m-d', $date);

    if (!$validDate || $validDate->format('Y-m-d') !== $date) {
        set_flash('error', 'Please choose a valid attendance date.');
    } elseif (!is_array($statuses) || !$statuses) {
        set_flash('error', 'No attendance entries were submitted.');
    } else {
        $allowed = ['Present', 'Absent', 'Leave'];
        $stmt = $pdo->prepare(
            'INSERT INTO attendance (emp_id, attendance_date, status)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE status = VALUES(status)'
        );
        $saved = 0;
        foreach ($statuses as $empId => $status) {
            if (!in_array($status, $allowed, true)) continue;
            $stmt->execute([(int) $empId, $date, $status]);
            $saved++;
        }
        set_flash('success', "Attendance saved for {$saved} employee(s) on {$date}.");
    }
    redirect('admin/attendance.php?mark_date=' . urlencode($date ?: date('Y-m-d')));
}

/* ---------------------- Marking form data ---------------------- */
$markDate = (string) ($_GET['mark_date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $markDate)) $markDate = date('Y-m-d');

$allEmployees = $pdo->query('SELECT emp_id, name, department FROM employee ORDER BY name')->fetchAll();

$existingStmt = $pdo->prepare('SELECT emp_id, status FROM attendance WHERE attendance_date = ?');
$existingStmt->execute([$markDate]);
$existing = array_column($existingStmt->fetchAll(), 'status', 'emp_id');

/* ---------------------- Records view filters ---------------------- */
$empFilter = (int) ($_GET['emp_id'] ?? 0);
$dateFrom  = (string) ($_GET['from'] ?? '');
$dateTo    = (string) ($_GET['to'] ?? '');
$page      = max(1, (int) ($_GET['page'] ?? 1));

$where  = [];
$params = [];
if ($empFilter > 0) { $where[] = 'a.emp_id = ?';           $params[] = $empFilter; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) { $where[] = 'a.attendance_date >= ?'; $params[] = $dateFrom; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   { $where[] = 'a.attendance_date <= ?'; $params[] = $dateTo; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance a {$whereSql}");
$countStmt->execute($params);
$totalRows  = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$listStmt = $pdo->prepare(
    "SELECT a.*, e.name, e.department
     FROM attendance a JOIN employee e ON e.emp_id = a.emp_id
     {$whereSql}
     ORDER BY a.attendance_date DESC, e.name
     LIMIT {$perPage} OFFSET {$offset}"
);
$listStmt->execute($params);
$records = $listStmt->fetchAll();

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Mark Daily Attendance</h2>
    </div>
    <form method="get" action="attendance.php" class="filters">
        <div class="form-group">
            <label for="mark_date">Attendance date</label>
            <input type="date" id="mark_date" name="mark_date" value="<?= e($markDate) ?>" max="<?= e(date('Y-m-d')) ?>">
        </div>
        <button type="submit" class="btn btn-secondary">Load Date</button>
    </form>

    <form method="post" action="attendance.php">
        <?= csrf_field() ?>
        <input type="hidden" name="attendance_date" value="<?= e($markDate) ?>">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Employee</th><th>Department</th><th>Status for <?= e($markDate) ?></th></tr>
                </thead>
                <tbody>
                    <?php if (!$allEmployees): ?>
                        <tr><td colspan="3" class="table-empty">No employees yet. Add employees first.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($allEmployees as $emp): ?>
                        <?php $current = $existing[$emp['emp_id']] ?? 'Present'; ?>
                        <tr>
                            <td><?= e($emp['name']) ?></td>
                            <td><?= e($emp['department']) ?></td>
                            <td>
                                <div class="btn-group" role="radiogroup" aria-label="Attendance status">
                                    <?php foreach (['Present', 'Absent', 'Leave'] as $opt): ?>
                                        <label class="btn btn-sm <?= $current === $opt ? 'btn-primary' : 'btn-secondary' ?>" style="margin:0">
                                            <input type="radio" name="status[<?= (int) $emp['emp_id'] ?>]"
                                                   value="<?= $opt ?>" <?= $current === $opt ? 'checked' : '' ?>
                                                   style="position:absolute;opacity:0;pointer-events:none">
                                            <?= $opt ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($allEmployees): ?>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Attendance</button>
            </div>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2>Attendance Records (<?= $totalRows ?>)</h2>
    </div>

    <form class="filters" method="get" action="attendance.php">
        <div class="form-group">
            <label for="emp_id">Employee</label>
            <select id="emp_id" name="emp_id">
                <option value="0">All employees</option>
                <?php foreach ($allEmployees as $emp): ?>
                    <option value="<?= (int) $emp['emp_id'] ?>" <?= $empFilter === (int) $emp['emp_id'] ? 'selected' : '' ?>>
                        <?= e($emp['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="from">From date</label>
            <input type="date" id="from" name="from" value="<?= e($dateFrom) ?>" data-date-from>
        </div>
        <div class="form-group">
            <label for="to">To date</label>
            <input type="date" id="to" name="to" value="<?= e($dateTo) ?>" data-date-to>
        </div>
        <button type="submit" class="btn btn-secondary">Apply Filters</button>
        <a class="btn btn-secondary" href="attendance.php">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Date</th><th>Employee</th><th>Department</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php if (!$records): ?>
                    <tr><td colspan="4" class="table-empty">No attendance records match these filters.</td></tr>
                <?php endif; ?>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <td><?= e($row['attendance_date']) ?></td>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['department']) ?></td>
                        <td><?= status_badge($row['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?= render_pagination($page, $totalPages, array_filter([
        'emp_id' => $empFilter ?: null,
        'from'   => $dateFrom ?: null,
        'to'     => $dateTo ?: null,
    ])) ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
