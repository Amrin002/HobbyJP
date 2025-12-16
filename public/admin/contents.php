<?php
session_start();
include '../../include/koneksi.php';

// protect admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = isset($_POST['id_content']) ? intval($_POST['id_content']) : 0;
    if ($id > 0) {
        // delete content (cascade will remove stats, episodes, bookmarks, etc.)
        $del = "DELETE FROM contents WHERE id_content = ?";
        $sd = mysqli_prepare($conn, $del);
        mysqli_stmt_bind_param($sd, 'i', $id);
        if (mysqli_stmt_execute($sd)) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Content deleted successfully.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to delete content.'];
        }
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid content selected.'];
    }
    header('Location: contents.php');
    exit;
}

// Fetch contents with stats
$q = "SELECT c.*, cat.category_name, cs.view_count, cs.average_rating, cs.total_bookmark, cs.total_episode
      FROM contents c
      LEFT JOIN categories cat ON c.id_category = cat.id_category
      LEFT JOIN content_stats cs ON c.id_content = cs.id_content
      ORDER BY c.created_at DESC";
$res = mysqli_query($conn, $q);

include 'sidebar.php';
?>

<main class="main-content">
    <header class="admin-header">
        <div>
            <h2 class="fw-bold m-0">Content Management</h2>
            <p class="text-secondary m-0">Daftar semua judul yang tersedia.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="content-add.php" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Add Title
            </a>
        </div>
    </header>

    <div class="card bg-transparent border-0">
        <div style="background-color: var(--bg-card); border-radius: 16px; padding: 20px; border: 1px solid var(--bg-element);">
            <?php if (!empty($_SESSION['flash']['message'])): ?>
                <?php $f = $_SESSION['flash'];
                $_SESSION['flash'] = []; ?>
                <div class="alert <?php echo ($f['type'] === 'success') ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo htmlspecialchars($f['message']); ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table-custom" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width:60px">#</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Stats</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        while ($row = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td class="text-secondary"><?php echo $no++; ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($row['cover'])): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($row['cover']); ?>" class="content-thumb-sm" alt="cover">
                                        <?php else: ?>
                                            <div class="content-thumb-sm d-flex align-items-center justify-content-center text-secondary">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($row['title']); ?></div>
                                            <small class="text-secondary"><?php echo htmlspecialchars(mb_strimwidth($row['synopsis'], 0, 80, '...')); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $row['status'] == 'ONGOING' ? 'status-ongoing' : 'status-completed'; ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-secondary small">
                                        <i class="bi bi-eye"></i> <?php echo number_format($row['view_count']); ?> •
                                        <i class="bi bi-star-fill text-warning"></i> <?php echo number_format($row['average_rating'], 1); ?> •
                                        <i class="bi bi-bookmark"></i> <?php echo number_format($row['total_bookmark']); ?>
                                    </div>
                                </td>
                                <td class="text-secondary small"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="content-edit.php?id=<?php echo $row['id_content']; ?>" class="btn btn-icon" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="content-detail.php?id=<?php echo $row['id_content']; ?>" class="btn btn-icon" title="Episodes">
                                            <i class="bi bi-list-task"></i>
                                        </a>
                                        <form method="post" class="d-inline-block form-delete" style="margin:0">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_content" value="<?php echo $row['id_content']; ?>">
                                            <button type="button" class="btn btn-icon text-danger btn-delete" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (!confirm('Hapus konten ini? Tindakan ini akan menghapus semua episode dan data terkait.')) return;
                    const form = this.closest('.form-delete');
                    form.submit();
                });
            });
        });
    </script>

</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>