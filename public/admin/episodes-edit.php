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

// fetch episode
$q = "SELECT * FROM episodes WHERE id_episode = ? LIMIT 1";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'i', $id);
mysqli_stmt_execute($s);
$r = mysqli_stmt_get_result($s);
$episode = mysqli_fetch_assoc($r);
if (!$episode) {
    header('Location: contents.php');
    exit;
}

$content_id = $episode['id_content'];

// fetch content title
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

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $episode_number = isset($_POST['episode_number']) ? trim($_POST['episode_number']) : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $body = isset($_POST['content']) ? trim($_POST['content']) : '';

    if ($episode_number === '' || $title === '' || $body === '') {
        $errors[] = 'All fields are required.';
    }

    if (empty($errors)) {
        $upd = "UPDATE episodes SET episode_number = ?, title = ?, content = ? WHERE id_episode = ?";
        $su = mysqli_prepare($conn, $upd);
        mysqli_stmt_bind_param($su, 'sssi', $episode_number, $title, $body, $id);
        if (mysqli_stmt_execute($su)) {
            // update total episode count (safe)
            $q_update = "UPDATE content_stats SET total_episode = (
                SELECT COUNT(*) FROM episodes WHERE id_content = ?
            ) WHERE id_content = ?";
            $scu = mysqli_prepare($conn, $q_update);
            mysqli_stmt_bind_param($scu, 'ii', $content_id, $content_id);
            mysqli_stmt_execute($scu);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Episode updated.'];
            header('Location: episodes.php?content_id=' . $content_id);
            exit;
        } else {
            $errors[] = 'Failed to update episode.';
        }
    }
}

include 'sidebar.php';
?>

<main class="main-content">
    <header class="admin-header">
        <div>
            <h2 class="fw-bold m-0">Edit Episode for: <?php echo htmlspecialchars($content['title']); ?></h2>
            <p class="text-secondary m-0">Ubah konten episode.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="episodes.php?content_id=<?php echo $content_id; ?>" class="btn btn-outline-light btn-sm">Back</a>
        </div>
    </header>

    <div style="background:var(--bg-card); padding:20px; border:1px solid var(--bg-element); border-radius:12px; max-width:900px;">
        <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?></div><?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label>Episode Number</label>
                <input type="text" name="episode_number" class="form-control" required value="<?php echo htmlspecialchars($episode['episode_number']); ?>">
            </div>
            <div class="mb-3">
                <label>Title</label>
                <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($episode['title']); ?>">
            </div>
            <div class="mb-3">
                <label>Content</label>
                <textarea name="content" class="form-control" rows="12" required><?php echo htmlspecialchars($episode['content']); ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <a href="episodes.php?content_id=<?php echo $content_id; ?>" class="btn btn-outline-light">Cancel</a>
                <button class="btn btn-primary" type="submit">Update Episode</button>
            </div>
        </form>
    </div>

</main>

<?php include 'footer.php'; ?>