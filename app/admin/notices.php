<?php
/**
 * admin/notices.php — Create, edit and delete notices visible to all employees.
 */
require_once dirname(__DIR__) . '/config.php';
require_role('Admin');

$page_title = 'Notices';
$perPage    = 8;

/* ---------------------- Handle POST actions ---------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'update') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $body  = trim((string) ($_POST['body'] ?? ''));
        $errors = [];

        if ($title === '' || mb_strlen($title) > 200) $errors[] = 'Title is required (max 200 chars).';
        if ($body === '') $errors[] = 'Notice body is required.';

        if (!$errors) {
            if ($action === 'add') {
                $stmt = $pdo->prepare('INSERT INTO notices (title, body) VALUES (?, ?)');
                $stmt->execute([$title, $body]);
                set_flash('success', 'Notice published. All employees can see it now.');
            } else {
                $noticeId = (int) ($_POST['notice_id'] ?? 0);
                $stmt = $pdo->prepare('UPDATE notices SET title = ?, body = ? WHERE notice_id = ?');
                $stmt->execute([$title, $body, $noticeId]);
                set_flash('success', 'Notice updated.');
            }
            redirect('admin/notices.php');
        }
        foreach ($errors as $err) set_flash('error', $err);
    }

    if ($action === 'delete') {
        $noticeId = (int) ($_POST['notice_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM notices WHERE notice_id = ?');
        $stmt->execute([$noticeId]);
        set_flash($stmt->rowCount() ? 'success' : 'error',
                  $stmt->rowCount() ? 'Notice deleted.' : 'Notice not found.');
        redirect('admin/notices.php');
    }
}

/* ---------------------- Edit target ---------------------- */
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM notices WHERE notice_id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}

/* ---------------------- List ---------------------- */
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalRows  = (int) $pdo->query('SELECT COUNT(*) FROM notices')->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$notices = $stmt->fetchAll();

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><?= $editing ? 'Edit Notice' : 'Publish New Notice' ?></h2>
        <?php if ($editing): ?>
            <a class="btn btn-secondary btn-sm" href="notices.php">Cancel Edit</a>
        <?php endif; ?>
    </div>
    <form method="post" action="notices.php" data-validate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="notice_id" value="<?= (int) $editing['notice_id'] ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" required maxlength="200"
                   value="<?= e($editing['title'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="body">Message *</label>
            <textarea id="body" name="body" required><?= e($editing['body'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Update Notice' : 'Publish Notice' ?></button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2>Published Notices (<?= $totalRows ?>)</h2>
    </div>
    <?php if (!$notices): ?>
        <p class="table-empty">No notices published yet.</p>
    <?php endif; ?>
    <?php foreach ($notices as $notice): ?>
        <div class="notice-item">
            <div class="card-header" style="margin-bottom:0.25rem;">
                <h3><?= e($notice['title']) ?></h3>
                <div class="btn-group">
                    <a class="btn btn-sm btn-secondary" href="notices.php?edit=<?= (int) $notice['notice_id'] ?>">Edit</a>
                    <form method="post" action="notices.php" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="notice_id" value="<?= (int) $notice['notice_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger"
                                data-confirm="Delete the notice “<?= e($notice['title']) ?>”?">Delete</button>
                    </form>
                </div>
            </div>
            <div class="notice-meta">Posted <?= e(date('d M Y, h:i A', strtotime($notice['created_at']))) ?></div>
            <div class="notice-body"><?= e($notice['body']) ?></div>
        </div>
    <?php endforeach; ?>

    <?= render_pagination($page, $totalPages) ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
