<?php
session_start();
include '../../include/koneksi.php';

// protect admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$content_id = isset($_GET['content_id']) ? intval($_GET['content_id']) : 0;
if ($content_id <= 0) {
    header('Location: contents.php');
    exit;
}

// fetch content
$qc = "SELECT id_content, title FROM contents WHERE id_content = ? LIMIT 1";
$sc = mysqli_prepare($conn, $qc);
mysqli_stmt_bind_param($sc, 'i', $content_id);
mysqli_stmt_execute($sc);
$rc = mysqli_stmt_get_result($sc);
$content = mysqli_fetch_assoc($rc);
if (!$content) {
    header('Location: contents.php');
    exit;
}

// handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = isset($_POST['id_episode']) ? intval($_POST['id_episode']) : 0;
    if ($id > 0) {
        $del = "DELETE FROM episodes WHERE id_episode = ?";
        $sd = mysqli_prepare($conn, $del);
        mysqli_stmt_bind_param($sd, 'i', $id);
        if (mysqli_stmt_execute($sd)) {
            // update total_episode
            $q_update = "UPDATE content_stats SET total_episode = (
                SELECT COUNT(*) FROM episodes WHERE id_content = ?
            ) WHERE id_content = ?";
            $su = mysqli_prepare($conn, $q_update);
            mysqli_stmt_bind_param($su, 'ii', $content_id, $content_id);
            mysqli_stmt_execute($su);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Episode deleted.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to delete episode.'];
        }
    }
    header('Location: episodes.php?content_id=' . $content_id);
    exit;
}

// fetch episodes
$q = "SELECT * FROM episodes WHERE id_content = ? ORDER BY CAST(episode_number AS UNSIGNED) ASC, episode_number ASC";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'i', $content_id);
mysqli_stmt_execute($s);
$res = mysqli_stmt_get_result($s);

include 'sidebar.php';
?>

<main class="main-content">
    <header class="admin-header">
        <div>
            <h2 class="fw-bold m-0">Episodes for: <?php echo htmlspecialchars($content['title']); ?></h2>
            <p class="text-secondary m-0">Manage episodes untuk judul ini.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="contents.php" class="btn btn-outline-light btn-sm">Back</a>
            <a href="episodes-add.php?content_id=<?php echo $content_id; ?>" class="btn btn-primary btn-sm">Add Episode</a>
        </div>
    </header>

    <div class="card" style="background-color:var(--bg-card); border:1px solid var(--bg-element); border-radius:12px; padding:20px;">
        <?php if (!empty($_SESSION['flash']['message'])): $f = $_SESSION['flash'];
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
                        <th>Episode</th>
                        <th>Title</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    while ($ep = mysqli_fetch_assoc($res)): ?>
                        <tr>
                            <td class="text-secondary"><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($ep['episode_number']); ?></td>
                            <td><?php echo htmlspecialchars($ep['title']); ?></td>
                            <td class="text-secondary small"><?php echo date('d M Y', strtotime($ep['created_at'])); ?></td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="episodes-edit.php?id=<?php echo $ep['id_episode']; ?>" class="btn btn-outline-light btn-sm">Edit</a>
                                    <form method="post" class="d-inline-block" style="margin:0;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_episode" value="<?php echo $ep['id_episode']; ?>">
                                        <button type="button" class="btn btn-danger btn-sm btn-delete">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (!confirm('Hapus episode ini?')) return;
                    const form = this.closest('form');
                    form.submit();
                });
            });
        });
    </script>

</main>
</div>

<?php include 'footer.php'; ?>