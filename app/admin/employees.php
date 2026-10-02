<?php
/**
 * admin/employees.php — Add / update / delete / search employees.
 * Adding an employee automatically creates the linked user account.
 */
require_once dirname(__DIR__) . '/config.php';
require_role('Admin');

$page_title = 'Employee Management';
$perPage    = 8;

/* ---------------------- Handle POST actions ---------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'update') {
        $name       = trim((string) ($_POST['name'] ?? ''));
        $email      = trim((string) ($_POST['email'] ?? ''));
        $contact    = trim((string) ($_POST['contact'] ?? ''));
        $department = trim((string) ($_POST['department'] ?? ''));
        $position   = trim((string) ($_POST['position'] ?? ''));

        $errors = [];
        if ($name === '' || mb_strlen($name) > 120)  $errors[] = 'Name is required (max 120 chars).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';

        if ($action === 'add') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            if ($username === '' || mb_strlen($username) > 80 || !preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
                $errors[] = 'Username is required (letters, numbers, _ . - only).';
            }
            if (mb_strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

            if (!$errors) {
                $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
                $check->execute([$username]);
                if ($check->fetchColumn() > 0) $errors[] = 'That username is already taken.';
            }

            if (!$errors) {
                $pdo->beginTransaction();
                try {
                    $roleId = (int) $pdo->query("SELECT role_id FROM roles WHERE role_name = 'Employee'")->fetchColumn();
                    $stmt = $pdo->prepare('INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)');
                    $stmt->execute([$username, password_hash($password, PASSWORD_BCRYPT), $roleId]);
                    $userId = (int) $pdo->lastInsertId();

                    $stmt = $pdo->prepare(
                        'INSERT INTO employee (name, email, contact, department, position, user_id) VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([$name, $email, $contact, $department, $position, $userId]);
                    $pdo->commit();
                    set_flash('success', "Employee “{$name}” added with login account “{$username}”.");
                    redirect('admin/employees.php');
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    $errors[] = 'Could not add employee. Please try again.';
                }
            }
        } else { // update
            $empId = (int) ($_POST['emp_id'] ?? 0);
            $stmt = $pdo->prepare('SELECT emp_id FROM employee WHERE emp_id = ?');
            $stmt->execute([$empId]);
            if (!$stmt->fetch()) $errors[] = 'Employee not found.';

            if (!$errors) {
                $stmt = $pdo->prepare(
                    'UPDATE employee SET name = ?, email = ?, contact = ?, department = ?, position = ? WHERE emp_id = ?'
                );
                $stmt->execute([$name, $email, $contact, $department, $position, $empId]);
                set_flash('success', 'Employee details updated.');
                redirect('admin/employees.php');
            }
        }

        foreach ($errors as $err) set_flash('error', $err);
    }

    if ($action === 'delete') {
        $empId = (int) ($_POST['emp_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT user_id, name FROM employee WHERE emp_id = ?');
        $stmt->execute([$empId]);
        $emp = $stmt->fetch();

        if ($emp) {
            // Deleting the user cascades to employee, attendance, salary and leave records.
            $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
            $stmt->execute([(int) $emp['user_id']]);
            set_flash('success', "Employee “{$emp['name']}” and all linked records were deleted.");
        } else {
            set_flash('error', 'Employee not found.');
        }
        redirect('admin/employees.php');
    }
}

/* ---------------------- Edit target (if any) ---------------------- */
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM employee WHERE emp_id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}

/* ---------------------- List with search + pagination ---------------------- */
$search = trim((string) ($_GET['q'] ?? ''));
$page   = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$where  = '';
$params = [];
if ($search !== '') {
    $where  = 'WHERE e.name LIKE ? OR e.email LIKE ? OR e.department LIKE ? OR e.position LIKE ?';
    $like   = '%' . $search . '%';
    $params = [$like, $like, $like, $like];
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM employee e {$where}");
$countStmt->execute($params);
$totalRows  = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$listStmt = $pdo->prepare(
    "SELECT e.*, u.username
     FROM employee e JOIN users u ON u.user_id = e.user_id
     {$where}
     ORDER BY e.emp_id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$listStmt->execute($params);
$employees = $listStmt->fetchAll();

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><?= $editing ? 'Edit Employee' : 'Add New Employee' ?></h2>
        <?php if ($editing): ?>
            <a class="btn btn-secondary btn-sm" href="employees.php">Cancel Edit</a>
        <?php endif; ?>
    </div>
    <form method="post" action="employees.php" data-validate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="emp_id" value="<?= (int) $editing['emp_id'] ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" required maxlength="120"
                       value="<?= e($editing['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required maxlength="150"
                       value="<?= e($editing['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="contact">Contact</label>
                <input type="text" id="contact" name="contact" maxlength="30"
                       value="<?= e($editing['contact'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="department">Department</label>
                <input type="text" id="department" name="department" maxlength="100"
                       value="<?= e($editing['department'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="position">Position</label>
                <input type="text" id="position" name="position" maxlength="100"
                       value="<?= e($editing['position'] ?? '') ?>">
            </div>
        </div>

        <?php if (!$editing): ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Login Username *</label>
                    <input type="text" id="username" name="username" required maxlength="80"
                           pattern="[A-Za-z0-9_.\-]+" value="<?= e($_POST['username'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Login Password *</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Update Employee' : 'Add Employee' ?></button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2>Employees (<?= $totalRows ?>)</h2>
        <div class="form-group" style="margin:0; min-width:220px;">
            <input type="search" placeholder="Search table…" data-table-search="#employeeTable" aria-label="Search employees">
        </div>
    </div>

    <form class="filters" method="get" action="employees.php">
        <div class="form-group">
            <label for="q">Filter by name, email, department or position</label>
            <input type="text" id="q" name="q" value="<?= e($search) ?>" maxlength="80">
        </div>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <?php if ($search !== ''): ?>
            <a class="btn btn-secondary" href="employees.php">Clear</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table id="employeeTable">
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>Email</th><th>Contact</th>
                    <th>Department</th><th>Position</th><th>Username</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$employees): ?>
                    <tr><td colspan="8" class="table-empty">No employees found.</td></tr>
                <?php endif; ?>
                <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td><?= (int) $emp['emp_id'] ?></td>
                        <td><?= e($emp['name']) ?></td>
                        <td><?= e($emp['email']) ?></td>
                        <td><?= e($emp['contact']) ?></td>
                        <td><?= e($emp['department']) ?></td>
                        <td><?= e($emp['position']) ?></td>
                        <td><span class="badge badge-blue"><?= e($emp['username']) ?></span></td>
                        <td>
                            <div class="btn-group">
                                <a class="btn btn-sm btn-secondary" href="employees.php?edit=<?= (int) $emp['emp_id'] ?>">Edit</a>
                                <form method="post" action="employees.php" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="emp_id" value="<?= (int) $emp['emp_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"
                                            data-confirm="Delete <?= e($emp['name']) ?>? This also removes their login, attendance, salary and leave records.">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?= render_pagination($page, $totalPages, $search !== '' ? ['q' => $search] : []) ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
