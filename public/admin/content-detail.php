<?php
session_start();
include '../../include/koneksi.php';

// protect admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: contents.php');
    exit;
}

// Fetch content detail
$q = "SELECT c.*, cat.category_name, cs.average_rating, cs.total_bookmark, cs.view_count, cs.total_episode
      FROM contents c
      LEFT JOIN categories cat ON c.id_category = cat.id_category
      LEFT JOIN content_stats cs ON c.id_content = cs.id_content
      WHERE c.id_content = ? LIMIT 1";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'i', $id);
mysqli_stmt_execute($s);
$res = mysqli_stmt_get_result($s);
$content = mysqli_fetch_assoc($res);
if (!$content) {
    header('Location: contents.php');
    exit;
}

include 'sidebar.php';
?>

<main class="main-content">
    <header class="admin-header">
        <div>
            <h2 class="fw-bold m-0"><?php echo htmlspecialchars($content['title']); ?></h2>
            <p class="text-secondary m-0">Detail judul & manajemen episode.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="contents.php" class="btn btn-outline-light btn-sm">Back</a>
            <a href="episodes.php?content_id=<?php echo $id; ?>" class="btn btn-primary btn-sm">Manage Episodes</a>
        </div>
    </header>

    <div class="card bg-transparent border-0">
        <div style="background-color: var(--bg-card); border-radius: 16px; padding: 20px; border: 1px solid var(--bg-element);">
            <div class="row g-4">
                <div class="col-md-3">
                    <?php if (!empty($content['cover'])): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($content['cover']); ?>" class="img-fluid rounded" alt="cover">
                    <?php else: ?>
                        <div class="content-img"> <i class="bi bi-image"></i> </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <div class="mb-3">
                        <div class="d-flex gap-3 mb-2">
                            <div><i class="bi bi-star-fill text-warning"></i> <strong><?php echo number_format($content['average_rating'], 1); ?></strong></div>
                            <div><i class="bi bi-bookmark"></i> <?php echo number_format($content['total_bookmark']); ?> bookmarks</div>
                            <div><i class="bi bi-eye"></i> <?php echo number_format($content['view_count']); ?> views</div>
                            <div><i class="bi bi-layers-fill"></i> <?php echo number_format($content['total_episode']); ?> episodes</div>
                        </div>
                        <p class="text-secondary"><?php echo nl2br(htmlspecialchars($content['synopsis'])); ?></p>
                    </div>
                    <a href="episodes.php?content_id=<?php echo $id; ?>" class="btn btn-outline-light">Open Episodes</a>
                </div>
            </div>
        </div>
    </div>

</main>
</div>

<?php include 'footer.php'; ?>