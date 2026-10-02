<?php
/**
 * employee/notifications.php — All admin notices, newest first.
 */
require_once dirname(__DIR__) . '/config.php';
require_role('Employee');

$page_title = 'Notifications';
$perPage    = 8;

$page = max(1, (int) ($_GET['page'] ?? 1));
$totalRows  = (int) $pdo->query('SELECT COUNT(*) FROM notices')->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$notices = $pdo->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}")->fetchAll();

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="card">
    <div class="card-header"><h2>Notice Board (<?= $totalRows ?>)</h2></div>
    <?php if (!$notices): ?>
        <p class="table-empty">No notices have been posted yet.</p>
    <?php endif; ?>
    <?php foreach ($notices as $i => $notice): ?>
        <div class="notice-item">
            <h3>
                <?= e($notice['title']) ?>
                <?php if ($page === 1 && $i === 0): ?>
                    <span class="badge badge-blue">Latest</span>
                <?php endif; ?>
            </h3>
            <div class="notice-meta"><?= e(date('d M Y, h:i A', strtotime($notice['created_at']))) ?></div>
            <div class="notice-body"><?= e($notice['body']) ?></div>
        </div>
    <?php endforeach; ?>

    <?= render_pagination($page, $totalPages) ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
